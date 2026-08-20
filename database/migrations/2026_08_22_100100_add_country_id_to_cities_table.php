<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Tabel `cities` sudah ada di database live (dikonfirmasi user, meski migration
| pembuatannya tidak ada di history repo ini) sehingga di sini hanya menambah
| kolom `country_id` (ALTER), bukan membuat ulang tabelnya.
| Nullable supaya data city lama yang belum punya country tetap valid.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->char('country_id', 36)->nullable()->index()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn('country_id');
        });
    }
};
