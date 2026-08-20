<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * scope_level menentukan seberapa luas data yang boleh dilihat pemegang
     * role ini (terlepas dari nama role-nya, yang tetap bebas diketik admin):
     * - company  : semua data, lintas branch & divisi (mis. superadmin utama).
     * - branch   : hanya data di branch tempat role ini di-assign (lihat kolom
     *              baru role_user.company_branch_id), termasuk semua divisi
     *              di branch itu.
     * - division : hanya data di satu divisi tempat role ini di-assign (lihat
     *              role_user.company_division_id).
     * - self     : hanya data yang ditangani/dibuat user itu sendiri (mis.
     *              marketing/pengajar).
     *
     * Default 'company' (bukan yang paling ketat) supaya role 'superadmin'
     * yang sudah ada tetap berperilaku sama persis sebelum kolom ini ada —
     * lihat juga migration seed di akhir batch ini yang meng-grant seluruh
     * permission ke role 'superadmin'.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->enum('scope_level', ['company', 'branch', 'division', 'self'])
                ->default('company')
                ->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('scope_level');
        });
    }
};
