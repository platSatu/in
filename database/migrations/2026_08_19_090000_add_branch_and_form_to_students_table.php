<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * === STUDENT BRANCH/FORM TRACKING ===
     * Setiap kali student submit quiz apapun lewat wizard publik
     * (FrontendController::formWizardSubmit), branch & form yang baru dia isi
     * disimpan di sini. Karena satu baris `students` dipakai bareng untuk semua
     * form (dicocokkan lewat handphone), kolom ini merepresentasikan
     * "singgahan terakhir" student itu — dipakai untuk filter cepat di halaman
     * admin Student (index). Riwayat LENGKAP tiap submission (termasuk histori
     * form/branch sebelumnya & status pembayaran) tetap utuh lewat relasi
     * Student::formSubmissions() -> FormSubmission -> Form/FormPayment, tidak
     * hilang meskipun kolom ini cuma nyimpan yang terakhir.
     *
     * Nullable karena data student lama (sebelum fitur ini ada) belum tentu
     * pernah submit form manapun.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->char('branch_id', 36)->nullable();
            $table->char('form_id', 36)->nullable();

            $table->index('branch_id');
            $table->index('form_id');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['branch_id']);
            $table->dropIndex(['form_id']);
            $table->dropColumn(['branch_id', 'form_id']);
        });
    }
};
