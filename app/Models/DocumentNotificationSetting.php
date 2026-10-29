<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pengaturan notifikasi dokumen student (tab "Notifikasi" di Students > File Student).
 * Satu baris untuk seluruh sistem, ambil lewat DocumentNotificationSetting::current().
 *
 *  - admin_*        : info ke admin saat student mengunggah dokumen (Card 1)
 *  - reminder_*     : pengingat ke student yang dokumennya belum lengkap (Card 2)
 *  - complete_*     : pesan saat semua dokumen wajib sudah lengkap (Card 3)
 */
class DocumentNotificationSetting extends Model
{
    use HasUuids;

    /** Jadwal pengingat awal: H+n hari sejak student selesai mengisi Formulir (bisa diubah admin). */
    public const DEFAULT_REMINDER_DAYS = [1, 5, 7, 10];

    public const MAX_REMINDER_DAY = 60;

    /** Pilihan jam kirim pengingat (WIB). */
    public const REMINDER_HOURS = [7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20];

    protected $table = 'document_notification_settings';

    protected $fillable = [
        'admin_whatsapp_enabled',
        'admin_whatsapp_numbers',
        'admin_email_enabled',
        'admin_emails',
        'reminder_enabled',
        'reminder_whatsapp',
        'reminder_email',
        'reminder_days',
        'reminder_hour',
        'complete_notify_student',
        'complete_notify_admin',
        'updated_by',
    ];

    protected $casts = [
        'admin_whatsapp_enabled' => 'boolean',
        'admin_whatsapp_numbers' => 'array',
        'admin_email_enabled' => 'boolean',
        'admin_emails' => 'array',
        'reminder_enabled' => 'boolean',
        'reminder_whatsapp' => 'boolean',
        'reminder_email' => 'boolean',
        'reminder_days' => 'array',
        'reminder_hour' => 'integer',
        'complete_notify_student' => 'boolean',
        'complete_notify_admin' => 'boolean',
    ];

    public static function current(): self
    {
        return static::query()->oldest('created_at')->first()
            ?? static::create(['reminder_days' => self::DEFAULT_REMINDER_DAYS, 'reminder_hour' => 10]);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** Hari pengingat terurut, mis. [1, 5, 7, 10]. */
    public function reminderDays(): array
    {
        return collect($this->reminder_days ?? [])->map(fn ($d) => (int) $d)->filter(fn ($d) => $d >= 1)->unique()->sort()->values()->all();
    }

    /** Nomor WA admin yang aktif (kosong kalau saklar WhatsApp admin mati). */
    public function adminWhatsappTargets(): array
    {
        return $this->admin_whatsapp_enabled ? array_values(array_filter($this->admin_whatsapp_numbers ?? [])) : [];
    }

    /** Email admin yang aktif (kosong kalau saklar email admin mati). */
    public function adminEmailTargets(): array
    {
        return $this->admin_email_enabled ? array_values(array_filter($this->admin_emails ?? [])) : [];
    }
}
