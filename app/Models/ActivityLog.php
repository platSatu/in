<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris = satu aksi admin (login/logout, atau create/update/delete di
 * modul mana pun). Ditulis OTOMATIS oleh App\Helpers\ActivityLogger lewat
 * event Eloquent global — tidak ada tempat lain di aplikasi yang boleh
 * menulis ke tabel ini secara manual, supaya isinya selalu bisa dipercaya
 * sebagai jejak audit yang apa adanya (karena itu juga sengaja TIDAK ada
 * fillable untuk field yang bisa diedit manual lewat form admin — lihat
 * ActivityLogController yang cuma punya index()/detail(), tanpa
 * create/update/destroy).
 */
class ActivityLog extends Model
{
    use HasUuids;

    protected $table = 'activity_logs';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'actor_id',
        'actor_name',
        'actor_email',
        'event',
        'subject_type',
        'subject_id',
        'subject_label',
        'description',
        'old_values',
        'new_values',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Relasi ke user yang melakukan aksi ini — BOLEH null kalau user-nya sudah
     * dihapus (actor_name/actor_email di atas tetap ada sebagai snapshot,
     * jadi baris log ini tidak kehilangan makna walau relasi ini null).
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Daftar key yang berubah antara old_values & new_values — dipakai
     * halaman detail supaya cuma menyorot field yang benar-benar berubah,
     * bukan menampilkan seluruh kolom (termasuk yang tidak berubah) yang
     * cuma bikin bingung untuk aksi 'updated'.
     *
     * @return array<int, string>
     */
    public function changedKeys(): array
    {
        if (!is_array($this->old_values) || !is_array($this->new_values)) {
            return [];
        }

        $keys = array_unique(array_merge(array_keys($this->old_values), array_keys($this->new_values)));

        // 'updated_at' HAMPIR PASTI selalu berubah tiap update (di-touch otomatis
        // oleh Eloquent), jadi kalau ikut disorot di sini cuma bikin noise —
        // tetap tersimpan utuh di old_values/new_values (lihat kolom itu di
        // migration create_activity_logs_table), cuma tidak ikut "disorot"
        // sebagai perubahan yang relevan buat admin baca di halaman detail.
        $keys = array_diff($keys, ['updated_at']);

        return array_values(array_filter($keys, function ($key) {
            $old = $this->old_values[$key] ?? null;
            $new = $this->new_values[$key] ?? null;

            return $old !== $new;
        }));
    }
}
