<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Catatan setiap notifikasi dokumen yang dikirim (29 Oktober 2026): info upload
| ke admin, pengingat ke student, dan pesan dokumen lengkap. Dipakai untuk
| mencegah pesan dobel (pengingat H+n hanya sekali) dan untuk dicek admin.
*/
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_notification_logs')) {
            return;
        }

        Schema::create('document_notification_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('application_id', 36)->nullable()->index();
            // uploaded | reminder_d1 / reminder_d5 / ... | complete_student | complete_admin | test
            $table->string('type', 40);
            $table->string('channel', 20); // whatsapp | email
            $table->string('recipient', 150);
            $table->string('status', 20); // sent | failed
            $table->string('error')->nullable();
            $table->timestamps();

            $table->index(['application_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_notification_logs');
    }
};
