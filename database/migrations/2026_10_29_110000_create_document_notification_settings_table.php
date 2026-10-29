<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Pengaturan notifikasi dokumen student (29 Oktober 2026) -- tab "Notifikasi" di
| menu Students > File Student. Satu baris saja untuk seluruh sistem
| (lihat App\Models\DocumentNotificationSetting::current()).
*/
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_notification_settings')) {
            return;
        }

        Schema::create('document_notification_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Card 1: info ke admin saat student upload dokumen.
            $table->boolean('admin_whatsapp_enabled')->default(false);
            $table->json('admin_whatsapp_numbers')->nullable();
            $table->boolean('admin_email_enabled')->default(false);
            $table->json('admin_emails')->nullable();

            // Card 2: pengingat ke student yang dokumennya belum lengkap.
            $table->boolean('reminder_enabled')->default(false);
            $table->boolean('reminder_whatsapp')->default(true);
            $table->boolean('reminder_email')->default(true);
            $table->json('reminder_days')->nullable();

            // Card 3: saat dokumen sudah lengkap.
            $table->boolean('complete_notify_student')->default(false);
            $table->boolean('complete_notify_admin')->default(false);

            $table->char('updated_by', 36)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_notification_settings');
    }
};
