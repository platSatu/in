<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
| BUGFIX: kolom `forms`.`result_mode` masih ENUM('none','auto','manual') dari
| migration 2026_08_19_130000_add_stage_settings_to_forms_table.php --
| migration 2026_08_24_120200_add_section_threshold_fields_to_forms_table.php
| menambahkan section_fail_threshold/section_pass_threshold tapi LUPA
| melebarkan enum result_mode itu sendiri supaya bisa menerima value baru
| 'section_threshold'. Akibatnya menyimpan result_mode='section_threshold'
| gagal di level database dengan error "Data truncated for column
| 'result_mode'" (MySQL menolak/memotong value enum yang tidak dikenal),
| meski validasi Laravel di FormController::store()/update() sudah
| mengizinkan value itu.
|
| Pakai raw SQL (bukan Blueprint::enum()->change()) karena Laravel Schema
| Builder tidak punya cara native mengubah daftar value ENUM yang sudah ada
| tanpa dependency doctrine/dbal -- ALTER TABLE...MODIFY langsung lebih
| aman & tidak menambah dependency baru ke project.
*/
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `forms` MODIFY `result_mode` ENUM('none', 'auto', 'manual', 'section_threshold') NOT NULL DEFAULT 'none'");
    }

    public function down(): void
    {
        // Turunkan lagi ke daftar lama -- HATI-HATI: kalau sudah ada baris
        // dengan result_mode='section_threshold', downgrade ini akan GAGAL
        // (MySQL menolak enum value yang tidak ada di daftar baru) kecuali
        // baris itu diubah dulu manual. Sengaja tidak dipaksa auto-convert
        // di sini supaya rollback tidak diam-diam mengubah data.
        DB::statement("ALTER TABLE `forms` MODIFY `result_mode` ENUM('none', 'auto', 'manual') NOT NULL DEFAULT 'none'");
    }
};
