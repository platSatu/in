<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Fase 1 -- fitur "Alur Pembayaran 2 Arah Apply Kampus" (10 September 2026).
|
| Kolom BARU 'admission_status' -- status UNDER REVIEW / PROCESSING /
| ACCEPTED yang diminta user, SENGAJA DIPISAH dari kolom 'status' yang
| sudah ada (submitted/documents_review/registered/dst) supaya fitur
| stepper/filter yang sudah jalan berdasarkan 'status' TIDAK terganggu
| sama sekali.
|
| Defaultnya NULL (belum relevan) -- baru diisi 'under_review' secara
| otomatis oleh kode di Fase 2 begitu pembayaran Registration Fee sukses
| dikonfirmasi webhook (Step 1 & Step 2 terbuka). 'processing' & 'accepted'
| diubah manual oleh admin dari halaman detail aplikasi (Fase 4).
|
| Kolom 'deposit_fee_china_amount' yang sudah ada DIPAKAI ULANG sebagai
| nominal "Departure Fee" (cuma beda label tampilan di UI, lihat Fase 5) --
| TIDAK ditambah kolom baru supaya tidak ada data terduplikasi.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('university_applications', function (Blueprint $table) {
            $table->string('admission_status')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('university_applications', function (Blueprint $table) {
            $table->dropColumn('admission_status');
        });
    }
};
