<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pertanyaan bisa (opsional) dikelompokkan ke dalam satu FormSection —
     * lihat migration create_form_sections_table. NULL berarti pertanyaan ini
     * belum dikelompokkan (perilaku default, sama seperti sebelum fitur ini
     * ada — lihat catatan di FrontendController::buildFormWizardView()).
     *
     * Sengaja char(36) + index biasa (pola yang sama dengan parent_option_id
     * di migration add_parent_option_id_to_form_questions_table), BUKAN
     * foreign key asli — konsisten dengan seluruh relasi form_id/question_id
     * lain di project ini yang memang tidak pernah pakai FK constraint asli
     * di database (lihat catatan di FormController::resetSubmissions()).
     */
    public function up(): void
    {
        if (Schema::hasColumn('form_questions', 'section_id')) {
            return;
        }

        Schema::table('form_questions', function (Blueprint $table) {
            $table->char('section_id', 36)->nullable()->after('stage_group')->index();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('form_questions', 'section_id')) {
            return;
        }

        Schema::table('form_questions', function (Blueprint $table) {
            $table->dropColumn('section_id');
        });
    }
};
