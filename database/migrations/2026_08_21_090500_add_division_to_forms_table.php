<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sama seperti company_division_id di tabel students — kolom skema utk
     * fase penerapan scope 'division' ke modul Form/Quiz, menyusul di fase
     * berikutnya. Belum diterapkan ke FormController pada batch ini.
     */
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->char('company_division_id', 36)->nullable()->after('branch_id');

            $table->index('company_division_id');
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropIndex(['company_division_id']);
            $table->dropColumn('company_division_id');
        });
    }
};
