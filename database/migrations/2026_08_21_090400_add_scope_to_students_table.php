<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom skema untuk fase penerapan scope ke modul Student (menyusul di
     * fase berikutnya, belum diterapkan ke StudentController pada batch ini):
     *
     * - handled_by_user_id: marketing/pengajar yang menangani student ini.
     *   Bisa diisi manual saat create/edit, atau di-assign ulang oleh manager
     *   belakangan — dipakai utk filter scope 'self'. BUKAN sales_id (yang
     *   tetap teks bebas/kode referral dari wizard publik, tidak diubah).
     * - company_division_id: divisi student ini, dipakai utk filter scope
     *   'division'. Nullable, sama seperti branch_id yang sudah ada (student
     *   boleh belum terhubung ke divisi manapun).
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->char('handled_by_user_id', 36)->nullable()->after('sales_id');
            $table->char('company_division_id', 36)->nullable()->after('branch_id');

            $table->index('handled_by_user_id');
            $table->index('company_division_id');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['handled_by_user_id']);
            $table->dropIndex(['company_division_id']);
            $table->dropColumn(['handled_by_user_id', 'company_division_id']);
        });
    }
};
