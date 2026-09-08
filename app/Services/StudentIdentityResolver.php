<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Resolusi identitas Student (CRM lead) dari data name/email/handphone --
 * dipakai di titik manapun sistem perlu "cari Student yang cocok, atau
 * buat baru kalau belum ada" (quiz-wizard publik & registrasi Apply
 * Kampus). Lihat docblock findOrCreate() di bawah untuk riwayat lengkap.
 */
class StudentIdentityResolver
{
    /**
     * Cari Student berdasarkan handphone ATAU email, atau buat baru kalau
     * belum ada sama sekali -- lihat catatan BUGFIX di dalam method ini.
     *
     * DIPINDAH ke sini (8 September 2026) dari
     * App\Http\Controllers\FrontendController::findOrCreateStudent()
     * SUPAYA BISA DIPAKAI BARENG oleh alur registrasi Apply Kampus
     * (App\Http\Controllers\Auth\RegisteredUserController) juga --
     * PERILAKU PERSIS SAMA, cuma lokasinya yang dipindah (jadi public
     * service, bukan private method controller) supaya tidak ada 2 versi
     * logic pencarian identitas Student yang bisa berbeda/divergen.
     *
     * Dipakai bareng oleh FrontendController::formWizardSubmit() (submit
     * lengkap), FrontendController::formWizardTimeoutSave() (auto-save
     * saat timer habis), RegisteredUserController::store() (registrasi
     * lewat alur Apply Kampus), dan GoogleAuthController::callback()
     * (registrasi otomatis saat login pakai akun Google).
     *
     * $validated wajib punya key: 'name', 'email', 'handphone'.
     */
    public function findOrCreate(array $validated): Student
    {
        Log::info('[FORM-WIZARD] Cek DB connection aktif', [
            'connection' => config('database.default'),
            'database' => DB::connection()->getDatabaseName(),
        ]);

        // GUARD (ditambahkan 8 September 2026 untuk alur Google Login, yang
        // tidak pernah punya nomor HP sama sekali -- handphone dikirim
        // sebagai string kosong ''): kondisi "where handphone" HANYA
        // diikutkan kalau nilainya benar-benar terisi, supaya request yang
        // handphone-nya kosong TIDAK salah "nyambung" ke Student lain mana
        // pun yang kebetulan juga punya handphone kosong di DB (data lama/
        // hasil import). Untuk caller yang sudah ada (quiz-wizard,
        // registrasi Apply Kampus) handphone SELALU wajib diisi non-kosong,
        // jadi perilaku untuk mereka SAMA SEKALI TIDAK BERUBAH.
        $handphone = trim((string) ($validated['handphone'] ?? ''));
        $email = trim((string) ($validated['email'] ?? ''));

        try {
            $existingStudent = $this->matchQuery($handphone, $email)->first();

            Log::info('[FORM-WIZARD] Hasil cek Student existing', [
                'found' => $existingStudent ? true : false,
                'existing_student_id' => $existingStudent->id ?? null,
            ]);

            $nameParts = preg_split('/\s+/', trim($validated['name']), 2);

            $payload = [
                'first_name' => $nameParts[0],
                'last_name' => $nameParts[1] ?? '',
                'email' => $validated['email'],
                'handphone' => $validated['handphone'],
                'status' => 'active',
            ];

            if ($existingStudent) {
                // BUGFIX: sebelumnya baris Student lama langsung dipakai apa
                // adanya tanpa update nama/email sama sekali -- jadi kalau
                // nomor WhatsApp yang sama pernah dipakai sebelumnya (submit
                // form lain, testing, atau nomor keluarga/orang lain), nama
                // yang BARU SAJA diketik peserta di step ini diam-diam
                // dibuang, dan admin lihat nama LAMA dari submission
                // sebelumnya -- padahal peserta yakin sudah isi nama yang
                // benar. Nomor HP dipakai sebagai "kunci" identitas Student
                // (biar tidak dobel baris per orang), TAPI nama/email harus
                // tetap ikut yang terbaru diketik tiap kali submit.
                //
                // Kekecualian: kalau handphone di payload ini KOSONG (alur
                // Google Login), JANGAN timpa nomor HP yang sudah tersimpan
                // di Student itu (kemungkinan hasil isi quiz-wizard
                // sebelumnya) dengan string kosong -- itu akan menghapus data
                // yang justru lebih lengkap.
                $existingStudent->update([
                    'first_name' => $payload['first_name'],
                    'last_name' => $payload['last_name'],
                    'email' => $payload['email'],
                    'handphone' => $handphone !== '' ? $payload['handphone'] : $existingStudent->handphone,
                ]);

                Log::info('[FORM-WIZARD] Pakai Student yang sudah ada (cocok lewat HP atau email), update data ke yang terbaru', [
                    'student_id' => $existingStudent->id,
                ]);

                return $existingStudent;
            }

            Log::info('[FORM-WIZARD] Akan create Student baru dengan payload', $payload);

            try {
                $student = Student::create($payload);
            } catch (\Illuminate\Database\QueryException $raceException) {
                // Jaring pengaman untuk race condition yang sangat jarang: dua
                // submission yang BENAR-BENAR baru (HP & email dua-duanya
                // belum pernah ada) masuk nyaris bersamaan, keduanya
                // lolos pengecekan "belum ada" di atas SEBELUM salah satunya
                // benar-benar tersimpan -- yang kedua nabrak constraint unique
                // (handphone atau email). Daripada submission ini ikut hilang
                // gara-gara race murni (bukan salah datanya), cari ulang baris
                // yang barusan berhasil disimpan oleh request satunya, lalu
                // pakai itu -- konsisten dengan alur "Student sudah ada" di atas.
                if ($raceException->getCode() !== '23000') {
                    throw $raceException;
                }

                Log::warning('[FORM-WIZARD] Race condition saat create Student baru, pakai baris yang barusan tersimpan oleh request lain', [
                    'handphone' => $payload['handphone'],
                    'email' => $payload['email'],
                ]);

                $student = $this->matchQuery($handphone, $email)->firstOrFail();
            }

            Log::info('[FORM-WIZARD] Student::create selesai dieksekusi', [
                'student_id' => $student->id ?? null,
                'student_exists_flag' => $student->exists,
                'was_recently_created' => $student->wasRecentlyCreated,
            ]);

            // Cek ulang langsung ke DB (bukan dari memory object) untuk memastikan
            // baris ini SUNGGUH ada di tabel, bukan cuma ada di object PHP-nya.
            $recheck = DB::table('students')->where('id', $student->id)->first();

            Log::info('[FORM-WIZARD] Recheck langsung ke tabel students via query builder', [
                'ketemu_di_db' => $recheck ? true : false,
                'data' => $recheck,
            ]);

            return $student;
        } catch (\Illuminate\Database\QueryException $e) {
            // Ini bakal ke-catch kalau errornya soal SQL (constraint, kolom NOT NULL, dsb)
            Log::error('[FORM-WIZARD] QueryException saat proses Student', [
                'message' => $e->getMessage(),
                'sql' => $e->getSql() ?? null,
                'bindings' => $e->getBindings() ?? null,
            ]);

            throw $e;
        } catch (\Throwable $e) {
            // Tangkap SEMUA jenis error lain (termasuk yang biasanya bikin whoops page)
            Log::error('[FORM-WIZARD] Exception tak terduga saat proses Student', [
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Query pencocokan Student lewat handphone ATAU email, hanya
     * mengikutkan kondisi yang nilainya benar-benar terisi (lihat catatan
     * GUARD di findOrCreate()). Dipakai untuk pengecekan awal maupun
     * re-query setelah race condition, supaya keduanya selalu konsisten.
     */
    private function matchQuery(string $handphone, string $email): Builder
    {
        $query = Student::query();

        if ($handphone !== '' && $email !== '') {
            $query->where('handphone', $handphone)->orWhere('email', $email);
        } elseif ($handphone !== '') {
            $query->where('handphone', $handphone);
        } elseif ($email !== '') {
            $query->where('email', $email);
        } else {
            // Tidak ada satupun identitas terisi -- jangan cocokkan ke
            // Student manapun (harusnya tidak pernah terjadi karena email
            // selalu wajib di semua alur yang ada saat ini).
            $query->whereRaw('1 = 0');
        }

        return $query;
    }
}
