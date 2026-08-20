<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot role <-> permission. can_edit=false berarti role itu cuma boleh
     * LIHAT modul tsb (index/show); can_edit=true berarti boleh juga
     * create/update/delete (lihat App\Http\Middleware\PermissionMiddleware
     * dan pembagian route view/edit di routes/web.php).
     *
     * SENGAJA TIDAK pakai kolom 'id' UUID terpisah seperti tabel lain di app
     * ini: baris tabel ini ditulis lewat $role->permissions()->sync(...)
     * (App\Http\Controllers\RoleController), yang melakukan bulk insert lewat
     * query builder dan TIDAK memicu event 'creating' Eloquent — jadi kolom id
     * ber-trait HasUuids tidak akan pernah keisi otomatis di situ (beda dgn
     * baris yg dibuat via Model::create()/updateOrCreate()). Primary key
     * komposit (role_id, permission_id) sudah cukup & ini pola standar
     * Laravel untuk pivot table yang diakses lewat sync()/attach().
     */
    public function up(): void
    {
        Schema::create('role_permission', function (Blueprint $table) {
            $table->char('role_id', 36);
            $table->char('permission_id', 36);
            $table->boolean('can_edit')->default(false);

            $table->timestamps();

            $table->primary(['role_id', 'permission_id']);
            $table->index('permission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permission');
    }
};
