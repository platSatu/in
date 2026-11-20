<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Card 4 di tab Notifikasi (20 November 2026): info ke student (WhatsApp & email) saat admin
 * mengunggah dokumen dari INA. Default aktif sesuai permintaan; bisa dimatikan per kanal di tab Notifikasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_notification_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('document_notification_settings', 'ina_document_notify_whatsapp')) {
                $table->boolean('ina_document_notify_whatsapp')->default(true);
            }
            if (! Schema::hasColumn('document_notification_settings', 'ina_document_notify_email')) {
                $table->boolean('ina_document_notify_email')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('document_notification_settings', function (Blueprint $table) {
            foreach (['ina_document_notify_whatsapp', 'ina_document_notify_email'] as $column) {
                if (Schema::hasColumn('document_notification_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
