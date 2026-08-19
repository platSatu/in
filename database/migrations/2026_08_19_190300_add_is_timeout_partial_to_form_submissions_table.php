<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penanda submission yang tersimpan otomatis karena timer placement test
     * habis (form_id.timer_auto_save aktif), BUKAN karena peserta menekan
     * tombol Submit sendiri. Dipisah dari kolom `status` yang sudah ada
     * (enum active/inactive, dikelola manual oleh admin lewat CRUD submission)
     * supaya tidak tabrakan makna — ini murni penanda asal-usul data, dipakai
     * FrontendController::formWizardTimeoutSave() saat membuat baris ini, dan
     * boleh dipakai nanti di halaman admin untuk membedakan submission
     * "lengkap" vs "kepotong waktu habis".
     */
    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->boolean('is_timeout_partial')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropColumn('is_timeout_partial');
        });
    }
};
