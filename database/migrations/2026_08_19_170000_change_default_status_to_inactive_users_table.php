<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ubah default kolom users.status dari 'active' menjadi 'inactive'.
     * Pakai raw SQL (bukan Blueprint::change()) supaya tidak butuh
     * dependency doctrine/dbal yang belum terpasang di project ini.
     * Guard dulu supaya migration ini aman dijalankan berulang kali.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'status')) {
            return;
        }

        DB::statement("ALTER TABLE `users` MODIFY `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'inactive'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('users', 'status')) {
            return;
        }

        DB::statement("ALTER TABLE `users` MODIFY `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active'");
    }
};
