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

    /** Pilihan jadwal pengingat: H+n hari sejak student selesai mengisi Formulir. */
    public const REMINDER_DAY_OPTIONS = [1, 5, 7, 10];

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
        'complete_notify_student' => 'boolean',
        'complete_notify_admin' => 'boolean',
    ];

    public static function current(): self
    {
        return static::query()->oldest('created_at')->first()
            ?? static::create(['reminder_days' => self::REMINDER_DAY_OPTIONS]);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
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
