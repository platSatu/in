<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Kolom `type` di form_questions itu ENUM (dibatasi daftar tetap oleh
     * MySQL), tapi validasi di FormQuestionController (store & update, lihat
     * "questions.*.type" / "type" => 'required|in:...') dan pilihan di
     * form-question/create.blade.php & edit.blade.php sudah lama mengizinkan
     * type 'file' (File Upload jpg/jpeg/png/pdf) — cuma ENUM di database-nya
     * yang belum pernah disusulkan waktu fitur itu ditambahkan. Akibatnya
     * insert/update pertanyaan bertype 'file' gagal dengan error MySQL
     * "Data truncated for column 'type'" (SQLSTATE 01000 / strict mode jadi
     * error, bukan cuma warning).
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE form_questions MODIFY type ENUM('text','textarea','number','date','single_choice','multiple_choice','dropdown','major','file') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE form_questions MODIFY type ENUM('text','textarea','number','date','single_choice','multiple_choice','dropdown','major') NOT NULL");
    }
};
