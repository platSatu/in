<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use App\Models\HistoryUserLogin;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

/**
 * Pencatat histori/aktivitas admin, dipanggil OTOMATIS lewat 3 event Eloquent
 * global yang didaftarkan di App\Providers\AppServiceProvider::boot():
 * 'eloquent.created: *', 'eloquent.updated: *', 'eloquent.deleted: *'.
 *
 * KENAPA pakai event global (bukan menambah kode di tiap controller satu-satu):
 * banyak modul di aplikasi ini menyimpan data langsung lewat Eloquent (mis.
 * FormQuestionController::store() yang insert banyak pertanyaan sekaligus
 * dalam satu loop, atau AuthenticatedSessionController yang bikin
 * HistoryUserLogin langsung), BUKAN selalu lewat App\Helpers\AdminCrud. Event
 * model global ini menangkap SEMUA create/update/delete di mana pun titiknya
 * dipanggil, termasuk yang lupa/belum sempat dites — supaya histori benar-benar
 * akurat & tidak ada yang "lolos" hanya karena beda pola penulisan kode.
 *
 * SENGAJA cuma mencatat aksi yang punya "pelaku" jelas (admin yang login lewat
 * panel), BUKAN aksi peserta quiz publik (Student/FormSubmission/FormAnswer/
 * FormPayment yang dibuat orang mengisi form tanpa login) — data peserta itu
 * sudah punya menunya sendiri (Form Submission/Form Answer), dan permintaan
 * fitur ini ("user yang login... ngapain aja") memang soal aktivitas ADMIN.
 */
class ActivityLogger
{
    /**
     * Model yang TIDAK ikut dicatat sama sekali:
     * - ActivityLog sendiri (mencegah mencatat pencatatan-nya sendiri).
     * - Permission: katalog modul/menu yang cuma berubah lewat sync registry
     *   (console) atau seed, bukan sesuatu yang admin "kerjakan" secara aktif.
     */
    private const EXCLUDED_MODELS = [
        ActivityLog::class,
        Permission::class,
    ];

    /**
     * Nama tabel level-infrastruktur yang tidak relevan sebagai "aktivitas
     * admin" walau kebetulan ada Model Eloquent yang menunjuk ke sana.
     */
    private const EXCLUDED_TABLES = [
        'sessions',
        'jobs',
        'failed_jobs',
        'job_batches',
        'cache',
        'cache_locks',
        'password_reset_tokens',
        'personal_access_tokens',
        'migrations',
        'activity_logs',
    ];

    /**
     * Nama kolom yang nilainya SELALU disamarkan sebelum disimpan ke
     * old_values/new_values, apa pun modelnya — jaga-jaga kalau suatu saat ada
     * model baru yang punya kolom sensitif serupa, tidak perlu diingat manual
     * satu-satu di sini.
     */
    private const REDACTED_KEY_PATTERN = '/password|token|secret|api[_-]?key/i';

    /**
     * Kolom yang dicoba berurutan untuk jadi "label" ringkas baris ini di
     * tabel index (mis. isi pertanyaan, nama form, email student) — berhenti
     * di kolom pertama yang kebetulan ada & tidak kosong di model ybs.
     */
    private const LABEL_ATTRIBUTE_PRIORITY = [
        'name', 'title', 'question_text', 'label', 'subject',
        'question', 'option_text', 'email', 'key', 'no_booth',
    ];

    public static function recordCreated(Model $model): void
    {
        if (!self::shouldLog($model)) {
            return;
        }

        if ($model instanceof HistoryUserLogin) {
            self::write($model, 'login', 'Login ke sistem', null, self::snapshot($model));
            return;
        }

        self::write(
            $model,
            'created',
            'Membuat ' . self::moduleLabel($model),
            null,
            self::snapshot($model)
        );
    }

    public static function recordUpdated(Model $model): void
    {
        if (!self::shouldLog($model)) {
            return;
        }

        // 'updated_at' hampir selalu ikut berubah tiap save() (auto-touch
        // Eloquent), termasuk untuk touch() murni yang tidak mengubah kolom
        // lain sama sekali (mis. dari relasi $touches). Kalau SATU-SATUNYA
        // perubahan cuma itu, tidak ada "aktivitas" yang benar-benar berarti
        // buat dicatat — daripada mencatat baris kosong yang cuma bikin
        // halaman Activity Log penuh noise, dilewati saja di sini.
        $meaningfulChanges = array_diff(array_keys($model->getChanges()), ['updated_at']);
        if (empty($meaningfulChanges)) {
            return;
        }

        // getOriginal() di titik event 'updated' MASIH mencerminkan nilai
        // SEBELUM update ini (Eloquent baru sync originalnya setelah event ini
        // selesai, lihat Illuminate\Database\Eloquent\Model::finishSave()),
        // jadi aman dipakai sebagai snapshot "sebelum" di sini.
        $old = self::snapshot($model, $model->getOriginal());
        $new = self::snapshot($model);

        if ($model instanceof HistoryUserLogin && array_key_exists('last_logout', $model->getChanges()) && $model->last_logout !== null) {
            self::write($model, 'logout', 'Logout dari sistem', $old, $new);
            return;
        }

        self::write(
            $model,
            'updated',
            'Mengupdate ' . self::moduleLabel($model),
            $old,
            $new
        );
    }

    public static function recordDeleted(Model $model): void
    {
        if (!self::shouldLog($model)) {
            return;
        }

        self::write(
            $model,
            'deleted',
            'Menghapus ' . self::moduleLabel($model),
            self::snapshot($model),
            null
        );
    }

    private static function shouldLog(Model $model): bool
    {
        // Tidak ada admin yang sedang login di request ini -> ini aksi peserta
        // quiz publik (atau proses console/queue), bukan "aktivitas admin"
        // yang diminta fitur ini. Lihat docblock class di atas.
        if (!Auth::check()) {
            return false;
        }

        foreach (self::EXCLUDED_MODELS as $excluded) {
            if ($model instanceof $excluded) {
                return false;
            }
        }

        try {
            if (in_array($model->getTable(), self::EXCLUDED_TABLES, true)) {
                return false;
            }
        } catch (Throwable $e) {
            // Model tanpa tabel yang jelas (mis. pivot tanpa model sendiri) —
            // aman diabaikan saja, bukan sesuatu yang perlu dicatat.
            return false;
        }

        return true;
    }

    /**
     * Tulis satu baris log. Dibungkus try/catch supaya kalau ada apa pun yang
     * tidak terduga di sini (mis. kolom JSON gagal di-encode), TIDAK PERNAH
     * ikut menggagalkan aksi CRUD yang sedang dicatat — mencatat histori
     * adalah fitur pendukung, bukan boleh sampai mengganggu fungsi utama yang
     * sudah berjalan.
     */
    private static function write(Model $model, string $event, string $description, ?array $old, ?array $new): void
    {
        try {
            $user = Auth::user();

            ActivityLog::create([
                'actor_id' => $user?->id,
                'actor_name' => $user?->name,
                'actor_email' => $user?->email,
                'event' => $event,
                'subject_type' => class_basename($model),
                // (string) di sini SENGAJA menutup dua kemungkinan tipe primary
                // key yang sama-sama ada di aplikasi ini: UUID (string) di
                // modul-modul baru, ATAU auto-increment integer di modul lama
                // (mis. Country/City/Major) — keduanya harus tetap tersimpan
                // sebagai referensi, bukan cuma yang UUID.
                'subject_id' => $model->getKey() !== null ? (string) $model->getKey() : null,
                'subject_label' => self::resolveLabel($model),
                'description' => $description,
                'old_values' => $old,
                'new_values' => $new,
                'ip_address' => request()?->ip(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Nama modul yang enak dibaca dari nama class model, mis. "FormQuestion"
     * -> "Form Question", "HistoryUserLogin" -> "History User Login" — generik
     * untuk SEMUA model tanpa perlu daftar nama manual satu-satu per modul.
     */
    private static function moduleLabel(Model $model): string
    {
        return Str::headline(class_basename($model));
    }

    private static function resolveLabel(Model $model): ?string
    {
        foreach (self::LABEL_ATTRIBUTE_PRIORITY as $attribute) {
            $value = $model->getAttribute($attribute);

            if (is_string($value) && trim($value) !== '') {
                return Str::limit(trim($value), 80);
            }
        }

        return $model->getKey() !== null ? ('#' . Str::limit((string) $model->getKey(), 8, '')) : null;
    }

    /**
     * Snapshot atribut model, dengan kolom sensitif disamarkan. $attributes
     * dioper eksplisit untuk kasus 'updated' (supaya bisa dikasih
     * getOriginal()) — default null berarti pakai atribut model saat ini.
     *
     * @param array<string, mixed>|null $attributes
     * @return array<string, mixed>
     */
    private static function snapshot(Model $model, ?array $attributes = null): array
    {
        $attributes = $attributes ?? $model->getAttributes();

        $redacted = [];
        foreach ($attributes as $key => $value) {
            $redacted[$key] = preg_match(self::REDACTED_KEY_PATTERN, $key) === 1
                ? '••••••••'
                : $value;
        }

        return $redacted;
    }
}
