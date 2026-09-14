<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kode resmi sales (format SLS-001, SLS-002, dst -- lihat
     * RoleUserController::nextSalesCode()), dibuat OTOMATIS oleh sistem
     * begitu role "sales" (dicocokkan lewat slug) di-assign aktif ke user
     * lewat halaman Role to User, dan bisa diedit manual oleh superadmin di
     * halaman Users kalau perlu diganti.
     *
     * Dipakai untuk superadmin mencocokkan "Kode Sales" yang diisi student
     * (Student::sales_id, tetap teks bebas/referral dari wizard publik,
     * TIDAK diubah) ke akun sales resminya lewat dropdown "Assign ke Sales"
     * di form edit Student (mengisi Student::handled_by_user_id).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('sales_code')->nullable()->unique()->after('handphone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['sales_code']);
            $table->dropColumn('sales_code');
        });
    }
};
