<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hierarki 2 level untuk FormSection (mis. HSK-style test): Section
     * "induk" (mis. "HSK 1") membungkus beberapa Sub Section (mis. "HSK 1 -
     * Listening", "HSK 1 - Reading"). NULL berarti section ini TOP-LEVEL
     * (baik section biasa 1-level seperti sebelum fitur ini ada, maupun
     * section induk di hierarki 2 level) — section lama yang sudah ada semua
     * otomatis tetap top-level, tidak ada perilaku yang berubah untuk form
     * yang belum memakai hierarki ini.
     *
     * Sengaja HANYA 2 level yang didukung (validasi "parent tidak boleh
     * punya parent lagi" ada di FormSectionController), bukan dibangun
     * sebagai tree tak terbatas — sesuai kebutuhan fitur HSK-style saat ini.
     * Sengaja char(36) + index biasa (BUKAN foreign key asli), konsisten
     * dengan seluruh relasi form_id/question_id/section_id lain di project
     * ini yang memang tidak pernah pakai FK constraint asli di database.
     */
    public function up(): void
    {
        if (Schema::hasColumn('form_sections', 'parent_section_id')) {
            return;
        }

        Schema::table('form_sections', function (Blueprint $table) {
            $table->char('parent_section_id', 36)->nullable()->after('form_id')->index();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('form_sections', 'parent_section_id')) {
            return;
        }

        Schema::table('form_sections', function (Blueprint $table) {
            $table->dropColumn('parent_section_id');
        });
    }
};
