<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * === JENIS FORM ===
     * form_type: 'general' (bawaan, semua form lama tetap 'general') atau 'ina_visa'.
     * Form berjenis Ina Visa tetap memakai seluruh fitur quiz (pertanyaan, upload file,
     * pembayaran, dll.) dan datanya tetap di form_submissions/form_answers -- jenis ini
     * hanya menentukan submission-nya ikut tampil di menu Students > Ina Visa.
     */
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            if (!Schema::hasColumn('forms', 'form_type')) {
                $table->string('form_type', 30)->default('general')->after('name')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            if (Schema::hasColumn('forms', 'form_type')) {
                $table->dropIndex(['form_type']);
                $table->dropColumn('form_type');
            }
        });
    }
};
