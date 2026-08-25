<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * start_at: kapan peserta pertama kali sampai di step Pertanyaan (dikirim
     * dari JS, murni informasi tambahan buat menghitung "Durasi Pengerjaan" di
     * laporan admin -- BUKAN bagian dari penilaian lolos/gagal apa pun). Nullable
     * karena submission lama (sebelum kolom ini ada) tidak akan pernah punya
     * nilainya, dan submission yang gagal mengirim nilainya (JS error, dsb)
     * tetap harus bisa tersimpan seperti biasa.
     */
    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('form_submissions', 'start_at')) {
                $table->timestamp('start_at')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            if (Schema::hasColumn('form_submissions', 'start_at')) {
                $table->dropColumn('start_at');
            }
        });
    }
};
