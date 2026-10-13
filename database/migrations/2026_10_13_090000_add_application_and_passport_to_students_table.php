<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nomor aplikasi & data paspor siswa (13 Oktober 2026), semuanya boleh
 * kosong. Baru kolomnya saja -- tampilan index/create/edit menyusul.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('application_no')->nullable()->after('current_school');
            $table->string('name_pasport')->nullable()->after('application_no');
            $table->string('number_pasport')->nullable()->after('name_pasport');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['application_no', 'name_pasport', 'number_pasport']);
        });
    }
};
