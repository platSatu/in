<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Diisi saat assign role ke user (halaman Role User) kalau role yang
     * dipilih scope_level-nya 'branch' (isi company_branch_id) atau
     * 'division' (isi company_division_id). Nullable karena role dengan
     * scope_level 'company'/'self' tidak butuh keduanya.
     *
     * Satu user boleh punya banyak baris role_user dengan scope berbeda-beda
     * (multi-branch/multi-divisi), tabelnya sudah mendukung itu secara alami.
     */
    public function up(): void
    {
        Schema::table('role_user', function (Blueprint $table) {
            $table->char('company_branch_id', 36)->nullable()->after('role_id');
            $table->char('company_division_id', 36)->nullable()->after('company_branch_id');

            $table->index('company_branch_id');
            $table->index('company_division_id');
        });
    }

    public function down(): void
    {
        Schema::table('role_user', function (Blueprint $table) {
            $table->dropIndex(['company_branch_id']);
            $table->dropIndex(['company_division_id']);
            $table->dropColumn(['company_branch_id', 'company_division_id']);
        });
    }
};
