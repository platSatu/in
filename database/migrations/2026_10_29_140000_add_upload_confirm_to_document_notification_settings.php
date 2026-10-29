<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Saklar "Konfirmasi ke student setiap kali upload" di tab Notifikasi (29 Oktober 2026). */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('document_notification_settings', 'upload_confirm_student')) {
            Schema::table('document_notification_settings', function (Blueprint $table) {
                $table->boolean('upload_confirm_student')->default(false)->after('complete_notify_admin');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('document_notification_settings', 'upload_confirm_student')) {
            Schema::table('document_notification_settings', fn (Blueprint $table) => $table->dropColumn('upload_confirm_student'));
        }
    }
};
