<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Section pembungkus pertanyaan (mis. "HSK 1 - Section A"). Satu form bisa
     * dibagi jadi beberapa section (tergantung admin, sifatnya opsional —
     * lihat kolom `section_id` nullable di migration
     * add_section_id_to_form_questions_table). Kalau satu form belum punya
     * section sama sekali, tampilan wizard publik TIDAK berubah dibanding
     * sebelum fitur ini ada (lihat FrontendController::buildFormWizardView()
     * & frontend/form-wizard.blade.php).
     */
    public function up(): void
    {
        Schema::create('form_sections', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('user_id', 36)->nullable();
            $table->char('form_id', 36);

            $table->string('name');
            $table->text('description')->nullable();

            $table->integer('order')->default(0);

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->timestamps();

            $table->index('form_id');
            $table->index('user_id');
            $table->index(['form_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_sections');
    }
};
