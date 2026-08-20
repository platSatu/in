<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Katalog modul/menu yang bisa diberi akses ke role (mis. 'student.student',
     * 'company.division'). Baris di tabel ini disinkronkan dari config/menu.php
     * lewat App\Models\Permission::syncFromRegistry() — lihat migration seed di
     * akhir batch ini dan app/Console/Commands/SyncPermissions.php untuk sync
     * ulang kalau config/menu.php ditambah entry baru di kemudian hari.
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('key')->unique();
            $table->string('label');
            $table->string('icon')->nullable();
            $table->string('group_label')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
