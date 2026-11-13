<?php

namespace App\Services\InaYule;

use App\Helpers\MoneyMath;
use App\Models\CourseCredit;
use App\Models\CoursePackage;
use App\Models\CoursePackagePurchase;
use App\Models\Deposit;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Import murid InaYule dari sistem lama (13 November 2026, Course > Student InaYule): nama, email, HP, pengajar tetap,
 * sisa credit per paket, dan sisa saldo deposit.
 *
 * Akun dibuat dengan pola yang sama seperti daftar sendiri (RegisteredUserController): role student, status inactive,
 * email belum terverifikasi, password acak. Murid cukup memakai "Lupa Password": NewPasswordController lalu
 * memverifikasi email, mengaktifkan akun, dan menautkan Student lewat StudentIdentityResolver.
 *
 * Aman untuk data InaStudy: email yang sudah ada TIDAK membuat akun/murid baru dan data lamanya tidak ditimpa
 * (hanya HP kosong diisi, akun ditautkan, pengajar tetap diisi). Email milik akun staf/pengajar ditolak.
 * Aman diulang: paket yang sudah pernah diimport untuk murid itu dan saldo awal yang sudah pernah diimport dilewati.
 */
class StudentImportService
{
    public const MAX_ROWS = 2000;

    public const COLUMNS = ['nama_murid', 'email', 'no_hp', 'pengajar', 'paket', 'sisa_credit', 'sisa_saldo'];

    private const SALDO_METHOD = 'import';

    /**
     * Baca baris Excel (sheet pertama, baris 1 = judul kolom).
     *
     * @return array<int, array<string, string>>|null nomor baris Excel => isi kolom; null = kolom nama_murid/email tidak ada
     */
    public function read(UploadedFile $file): ?array
    {
        $sheet = IOFactory::load($file->getRealPath())->getSheet(0)->toArray(null, true, false, false);
        $header = array_map(fn ($h) => Str::snake(strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h)))), array_shift($sheet) ?? []);
        if (! in_array('nama_murid', $header, true) || ! in_array('email', $header, true)) {
            return null;
        }
        $rows = [];

        foreach (array_slice($sheet, 0, self::MAX_ROWS) as $index => $line) {
            $row = [];
            foreach (self::COLUMNS as $column) {
                $i = array_search($column, $header, true);
                $value = $i === false ? '' : ($line[$i] ?? '');
                // Angka dari Excel (mis. 8.12E+10 atau 6.5) dikembalikan ke teks apa adanya.
                $row[$column] = trim(is_float($value) && floor($value) == $value ? number_format($value, 0, '', '') : (string) $value);
            }
            if (array_filter($row, fn ($v) => $v !== '')) {
                $rows[$index + 2] = $row;
            }
        }

        return $rows;
    }

    /**
     * Periksa semua baris & susun rencana per murid (tanpa menulis apa pun).
     *
     * @param  array<int, array<string, string>>  $rows
     * @return array{rows: array<int, array>, students: array<string, array>}
     */
    public function plan(array $rows): array
    {
        $packages = CoursePackage::get(['id', 'name'])->groupBy(fn ($p) => mb_strtolower(trim($p->name)));
        $teachers = $this->teachers();
        $teacherByKey = $teachers->groupBy(fn ($t) => mb_strtolower(trim($t->name)))
            ->union($teachers->groupBy(fn ($t) => mb_strtolower(trim((string) $t->email))));
        $result = [];
        $students = [];

        foreach ($rows as $line => $row) {
            $errors = [];
            $notes = [];
            $email = mb_strtolower($row['email']);

            if ($row['nama_murid'] === '' || mb_strlen($row['nama_murid']) > 255) {
                $errors[] = 'Nama murid wajib diisi.';
            }
            if (! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
                $errors[] = 'Email tidak valid.';
            }

            $phone = $this->phone($row['no_hp']);
            if ($row['no_hp'] !== '' && $phone === null) {
                $notes[] = 'No. HP tidak dikenali, dikosongkan.';
            }

            $teacherId = null;
            if ($row['pengajar'] !== '') {
                $match = $teacherByKey->get(mb_strtolower($row['pengajar']), collect());
                if ($match->count() === 1) {
                    $teacherId = $match->first()->id;
                } else {
                    $errors[] = $match->isEmpty() ? "Pengajar \"{$row['pengajar']}\" tidak ditemukan." : "Nama pengajar \"{$row['pengajar']}\" ada lebih dari satu, pakai email pengajar.";
                }
            }

            $credit = $this->number($row['sisa_credit'], false);
            $packageId = null;
            if ($row['paket'] !== '' || $row['sisa_credit'] !== '') {
                $match = $packages->get(mb_strtolower($row['paket']), collect());
                if ($credit === null || $credit < 0 || $credit > 10000) {
                    $errors[] = 'Sisa credit harus angka 0 atau lebih.';
                } elseif ($row['paket'] === '') {
                    $errors[] = 'Paket wajib diisi bila ada sisa credit.';
                } elseif ($match->count() !== 1) {
                    $errors[] = $match->isEmpty() ? "Paket \"{$row['paket']}\" tidak ditemukan." : "Nama paket \"{$row['paket']}\" ada lebih dari satu.";
                } elseif ($credit == 0) {
                    $notes[] = 'Sisa credit 0, paket tidak dicatat.';
                } else {
                    $packageId = $match->first()->id;
                }
            }

            $saldo = $this->number($row['sisa_saldo'], true);
            if ($row['sisa_saldo'] !== '' && ($saldo === null || $saldo < 0)) {
                $errors[] = 'Sisa saldo harus angka 0 atau lebih (tanpa titik/Rp).';
            }

            if (! $errors && isset($students[$email])) {
                $group = &$students[$email];
                if ($packageId && isset($group['packages'][$packageId])) {
                    $errors[] = 'Paket yang sama untuk murid ini sudah ada di baris sebelumnya.';
                }
                if ($saldo > 0 && $group['saldo'] > 0) {
                    $errors[] = 'Sisa saldo murid ini sudah diisi di baris sebelumnya (isi sekali saja).';
                }
                unset($group);
            }

            if (! $errors) {
                $students[$email] ??= ['email' => $email, 'name' => $row['nama_murid'], 'phone' => $phone, 'teacher_id' => null, 'packages' => [], 'saldo' => 0.0, 'lines' => []];
                $students[$email]['phone'] ??= $phone;
                $students[$email]['teacher_id'] ??= $teacherId;
                $students[$email]['saldo'] += (float) $saldo;
                if ($packageId) {
                    $students[$email]['packages'][$packageId] = ['name' => $packages->get(mb_strtolower($row['paket']))->first()->name, 'credit' => MoneyMath::floorToScale($credit, 2)];
                }
                $students[$email]['lines'][] = $line;
            }

            $result[$line] = $row + ['errors' => $errors, 'notes' => $notes, 'email_key' => $email];
        }

        // Cek terhadap data yang sudah ada (InaStudy/InaYule): akun, murid, import sebelumnya.
        foreach ($students as $email => &$student) {
            $check = $this->existing($email, array_keys($student['packages']));

            foreach ($student['lines'] as $line) {
                if ($check['error']) {
                    $result[$line]['errors'][] = $check['error'];
                    continue;
                }
                $result[$line]['notes'] = array_merge($result[$line]['notes'], $check['notes']);
            }
        }
        unset($student);

        foreach ($result as $line => $row) {
            if ($row['errors'] && isset($students[$row['email_key']]) && in_array($line, $students[$row['email_key']]['lines'], true)) {
                // Error dari data yang sudah ada berlaku untuk murid itu seluruhnya.
                unset($students[$row['email_key']]);
            }
        }

        return ['rows' => $result, 'students' => $students];
    }

    /**
     * Jalankan rencana: 1 transaksi per murid supaya satu murid gagal tidak membatalkan yang lain.
     *
     * @return array{created: int, linked: int, packages: int, saldo: int, failed: array<int, string>}
     */
    public function import(array $students, ?string $branchId, string $adminId): array
    {
        $summary = ['created' => 0, 'linked' => 0, 'packages' => 0, 'saldo' => 0, 'failed' => []];
        $studentRoleId = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student', 'status' => Role::STATUS_ACTIVE])->id;

        foreach ($students as $email => $plan) {
            try {
                DB::transaction(function () use ($email, $plan, $branchId, $studentRoleId, &$summary) {
                    $check = $this->existing($email, array_keys($plan['packages']), true);
                    if ($check['error']) {
                        throw new \RuntimeException($check['error']);
                    }

                    $user = $check['user'];
                    if (! $user) {
                        $user = User::create([
                            'name' => $plan['name'],
                            'email' => $email,
                            'handphone' => $plan['phone'] && ! User::where('handphone', $plan['phone'])->exists() ? $plan['phone'] : null,
                            'password' => Hash::make(Str::random(40)),
                            'status' => User::STATUS_INACTIVE,
                        ]);
                        RoleUser::create(['user_id' => $user->id, 'role_id' => $studentRoleId, 'status' => RoleUser::STATUS_ACTIVE]);
                        $summary['created']++;
                    } else {
                        $summary['linked']++;
                    }

                    $student = $check['student'];
                    if (! $student) {
                        $parts = preg_split('/\s+/', trim($plan['name']), 2);
                        $student = Student::create([
                            'user_id' => $user->id, 'first_name' => $parts[0], 'last_name' => $parts[1] ?? '',
                            'email' => $email, 'handphone' => $this->freePhone($plan['phone']), 'status' => 'active',
                            'images' => null, 'branch_id' => $branchId,
                        ]);
                    } else {
                        $student->user_id = $student->user_id ?: $user->id;
                        if (blank($student->handphone)) {
                            $student->handphone = $this->freePhone($plan['phone'], $student->id);
                        }
                    }
                    if ($plan['teacher_id']) {
                        $student->teacher_user_id = $plan['teacher_id'];
                    }
                    $student->save();

                    // Baris ledger 1 murid diberi jeda 1 detik supaya urutan "baris terakhir = saldo terkini" tetap pasti.
                    $at = now();
                    $balance = CourseCredit::currentBalanceFor($student->id);
                    foreach ($plan['packages'] as $packageId => $package) {
                        if (in_array($packageId, $check['imported_packages'], true)) {
                            continue;
                        }
                        $purchase = CoursePackagePurchase::create([
                            'student_id' => $student->id, 'course_package_id' => $packageId, 'price_paid' => 0,
                            'credits_granted' => $package['credit'], 'source' => CoursePackagePurchase::SOURCE_IMPORT,
                            'status' => CoursePackagePurchase::STATUS_COMPLETED,
                        ]);
                        $balance = MoneyMath::floorToScale($balance + $package['credit'], 2);
                        $credit = new CourseCredit([
                            'student_id' => $student->id, 'course_package_purchase_id' => $purchase->id,
                            'source_type' => CourseCredit::SOURCE_PURCHASE, 'debit' => 0, 'kredit' => $package['credit'],
                            'balance' => $balance, 'description' => 'Sisa credit dari data lama (import)',
                        ]);
                        $credit->created_at = $credit->updated_at = $at->copy();
                        $credit->save();
                        $at->addSecond();
                        $summary['packages']++;
                    }

                    if ($plan['saldo'] > 0 && ! $check['saldo_imported']) {
                        Deposit::create([
                            'user_id' => $user->id, 'debit' => 0, 'kredit' => $plan['saldo'],
                            'balance' => Deposit::currentBalanceFor($user->id) + $plan['saldo'],
                            'description' => 'Sisa saldo dari data lama (import)', 'payment_status' => 'success',
                            'payment_method' => self::SALDO_METHOD, 'payment_date' => now(),
                        ]);
                        $summary['saldo']++;
                    }
                });
            } catch (Throwable $e) {
                Log::warning('[INAYULE-IMPORT] Murid gagal diimport', ['email' => $email, 'error' => $e->getMessage()]);
                $summary['failed'][] = $email.': '.($e instanceof \RuntimeException ? $e->getMessage() : 'gagal disimpan, coba lagi.');
            }
        }

        Log::info('[INAYULE-IMPORT] Selesai', $summary + ['admin_id' => $adminId]);

        return $summary;
    }

    /** Contoh Excel: sheet isian + daftar paket & pengajar dari sistem supaya nama yang ditulis pasti cocok. */
    public function template(): StreamedResponse
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet()->setTitle('Import Murid');
        $sheet->fromArray([self::COLUMNS, ['Budi Santoso', 'budi@contoh.com', '081234567890', '', '', 6, 150000]]);
        $sheet->getStyle('C:C')->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        foreach (['A' => 24, 'B' => 28, 'C' => 16, 'D' => 20, 'E' => 38, 'F' => 12, 'G' => 14] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $lists = [
            'Daftar Paket' => CoursePackage::orderBy('name')->pluck('name'),
            'Daftar Pengajar' => $this->teachers()->pluck('name'),
        ];
        foreach ($lists as $title => $names) {
            $list = $book->createSheet()->setTitle($title);
            $list->fromArray(array_merge([[$title === 'Daftar Paket' ? 'paket (salin persis ke kolom paket)' : 'pengajar (salin persis ke kolom pengajar)']], $names->map(fn ($n) => [$n])->all()));
            $list->getColumnDimension('A')->setWidth(50);
            $list->getStyle('A1')->getFont()->setBold(true);
        }

        $help = $book->createSheet()->setTitle('Petunjuk');
        $help->fromArray([
            ['Kolom', 'Keterangan'],
            ['nama_murid', 'Wajib. Nama lengkap murid.'],
            ['email', 'Wajib. Untuk login & "Lupa Password". Email yang sudah terdaftar memakai akun itu (data lama tidak diubah).'],
            ['no_hp', 'Opsional. 08xx atau 628xx.'],
            ['pengajar', 'Opsional. Salin dari sheet Daftar Pengajar (atau email pengajar).'],
            ['paket', 'Wajib bila ada sisa credit. Salin dari sheet Daftar Paket. 2 paket = 2 baris dengan email sama.'],
            ['sisa_credit', 'Sisa credit paket itu, angka (boleh desimal, mis. 1.5).'],
            ['sisa_saldo', 'Opsional. Sisa saldo dalam Rupiah, angka saja. Isi SEKALI per murid.'],
            ['', 'Hapus baris contoh sebelum upload. Sebelum disimpan, sistem menampilkan pratinjau & baris yang bermasalah.'],
        ]);
        $help->getColumnDimension('A')->setWidth(14);
        $help->getColumnDimension('B')->setWidth(110);
        $help->getStyle('A1:B1')->getFont()->setBold(true);
        $book->setActiveSheetIndex(0);

        return response()->streamDownload(fn () => (new Xlsx($book))->save('php://output'), 'contoh-import-inayule.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** Pengajar aktif (role teacher). */
    public function teachers(): Collection
    {
        return User::whereHas('roles', fn ($q) => $q->where('slug', 'teacher')->where('roles.status', Role::STATUS_ACTIVE))
            ->orderBy('name')->get(['id', 'name', 'email']);
    }

    /**
     * Akun & murid yang sudah ada untuk email ini (InaStudy/InaYule) dan import sebelumnya.
     *
     * @return array{user: ?User, student: ?Student, error: ?string, notes: array, imported_packages: array, saldo_imported: bool}
     */
    private function existing(string $email, array $packageIds, bool $lock = false): array
    {
        $user = User::whereRaw('LOWER(email) = ?', [$email])->when($lock, fn ($q) => $q->lockForUpdate())->first();
        $student = $user ? Student::where('user_id', $user->id)->first() : null;
        $student ??= Student::whereRaw('LOWER(email) = ?', [$email])->when($lock, fn ($q) => $q->lockForUpdate())->orderBy('created_at')->first();
        $out = ['user' => $user, 'student' => $student, 'error' => null, 'notes' => [], 'imported_packages' => [], 'saldo_imported' => false];

        if ($user && ! $user->hasRole('student')) {
            $out['error'] = 'Email ini dipakai akun staf/pengajar, bukan murid.';

            return $out;
        }
        if ($user && $student && $student->user_id && $student->user_id !== $user->id) {
            $out['error'] = 'Data murid dengan email ini tertaut ke akun lain, cek manual di Data Student.';

            return $out;
        }

        if ($user) {
            $out['notes'][] = 'Akun sudah ada (dipakai, tidak dibuat baru).';
            $out['saldo_imported'] = Deposit::where('user_id', $user->id)->where('payment_method', self::SALDO_METHOD)->exists();
        } elseif ($student) {
            $out['notes'][] = 'Data murid sudah ada (InaStudy/InaYule), dibuatkan akun login.';
        } else {
            $out['notes'][] = 'Murid & akun baru.';
        }

        if ($student && $packageIds) {
            $out['imported_packages'] = CoursePackagePurchase::where('student_id', $student->id)
                ->where('source', CoursePackagePurchase::SOURCE_IMPORT)->whereIn('course_package_id', $packageIds)
                ->pluck('course_package_id')->all();
            if ($out['imported_packages']) {
                $out['notes'][] = count($out['imported_packages']).' paket sudah pernah diimport, dilewati.';
            }
        }
        if ($out['saldo_imported']) {
            $out['notes'][] = 'Saldo awal sudah pernah diimport, dilewati.';
        }

        return $out;
    }

    /** 0812.., 62812.., +62 812.., 812.. -> 0812..; selain nomor HP Indonesia -> null. */
    private function phone(string $value): ?string
    {
        $digits = preg_replace('/^(62|0)/', '', preg_replace('/\D/', '', $value));

        return preg_match('/^8\d{7,12}$/', $digits) ? '0'.$digits : null;
    }

    /** HP dipakai murid lain -> dikosongkan (kolom HP murid unik), supaya import tidak gagal. */
    private function freePhone(?string $phone, ?string $exceptStudentId = null): ?string
    {
        if ($phone === null) {
            return null;
        }

        return Student::where('handphone', $phone)->when($exceptStudentId, fn ($q) => $q->whereKeyNot($exceptStudentId))->exists() ? null : $phone;
    }

    /** Angka dari Excel. Rupiah: "Rp 150.000" / "150,000" -> 150000. Credit: "1,5" / "1.5" -> 1.5. */
    private function number(string $value, bool $money): ?float
    {
        $value = trim(preg_replace('/^rp\.?\s*/i', '', trim($value)));
        if ($money && preg_match('/^\d{1,3}([.,]\d{3})+$/', $value)) {
            $value = preg_replace('/[.,]/', '', $value);
        }
        $value = str_replace(',', '.', $value);

        return $value === '' ? 0.0 : (is_numeric($value) ? (float) $value : null);
    }
}
