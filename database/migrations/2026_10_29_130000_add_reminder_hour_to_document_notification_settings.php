<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Jam kirim pengingat dokumen (WIB) bisa diatur di tab Notifikasi (29 Oktober 2026). */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('document_notification_settings', 'reminder_hour')) {
            Schema::table('document_notification_settings', function (Blueprint $table) {
                $table->unsignedTinyInteger('reminder_hour')->default(10)->after('reminder_days');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('document_notification_settings', 'reminder_hour')) {
            Schema::table('document_notification_settings', fn (Blueprint $table) => $table->dropColumn('reminder_hour'));
        }
    }
};
