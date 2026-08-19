<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom `required` dan pilihan `type` yang lebih lengkap (textarea, date,
     * dropdown, major) sudah dipakai di controller & view form-question,
     * tapi belum pernah ditambahkan ke migration tabel `form_questions`.
     * Migration ini menyusulkan skema-nya supaya sesuai dan tidak error saat submit.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('form_questions', 'required')) {
            Schema::table('form_questions', function (Blueprint $table) {
                $table->boolean('required')->default(false)->after('type');
            });
        }

        DB::statement("ALTER TABLE form_questions MODIFY type ENUM('text','textarea','number','date','single_choice','multiple_choice','dropdown','major') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE form_questions MODIFY type ENUM('single_choice','multiple_choice','text','number') NOT NULL");

        if (Schema::hasColumn('form_questions', 'required')) {
            Schema::table('form_questions', function (Blueprint $table) {
                $table->dropColumn('required');
            });
        }
    }
};
