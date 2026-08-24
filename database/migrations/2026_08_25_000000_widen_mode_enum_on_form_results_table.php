<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
| BUGFIX (sibling dari 2026_08_24_150000_widen_result_mode_enum_for_section_threshold.php):
| kolom `form_results`.`mode` masih ENUM('auto','manual') dari migration
| 2026_08_19_150000_create_form_results_table.php -- pas fitur HSK Section
| Threshold ditambahkan, FrontendController::formWizardSubmit() ikut nulis
| FormResult dengan mode='section_threshold' (lihat baris ~866), tapi enum
| kolom ini lupa ikut dilebarkan (sama seperti bug result_mode di tabel
| forms yang sudah lebih dulu di-fix). Akibatnya submit quiz dengan
| result_mode=section_threshold gagal di INSERT form_results dengan error
| "Data truncated for column 'mode'".
|
| Raw SQL (bukan Blueprint::enum()->change()) dengan alasan sama seperti
| fix result_mode sebelumnya: Laravel Schema Builder tidak bisa native
| ubah daftar value ENUM tanpa dependency doctrine/dbal.
*/
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `form_results` MODIFY `mode` ENUM('auto', 'manual', 'section_threshold') NOT NULL");
    }

    public function down(): void
    {
        // HATI-HATI: kalau sudah ada baris dengan mode='section_threshold',
        // downgrade ini akan GAGAL (MySQL menolak enum value yang tidak ada
        // di daftar baru) kecuali baris itu diubah dulu manual. Sengaja
        // tidak dipaksa auto-convert supaya rollback tidak diam-diam
        // mengubah data.
        DB::statement("ALTER TABLE `form_results` MODIFY `mode` ENUM('auto', 'manual') NOT NULL");
    }
};
