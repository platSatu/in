<?php

use Illuminate\Database\Migrations\Migration;

/**
 * DIBATALKAN (25 November 2026): kolom students.inayule_registered_at tidak jadi dipakai.
 * "Tambah Student" di Student INA YULE memakai kolom join_by_date yang sudah ada.
 * File ini sengaja dibiarkan kosong (tidak mengubah database) dan boleh dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
    }

    public function down(): void
    {
    }
};
