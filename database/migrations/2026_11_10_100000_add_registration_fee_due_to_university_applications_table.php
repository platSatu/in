<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Upgrade kampus/jurusan (10 November 2026): kekurangan Registration Fee yang harus dibayar siswa setelah
     * admin memindahkan aplikasinya ke jurusan yang lebih mahal (Ubah Kampus/Jurusan). Selama terisi,
     * Formulir & Upload Dokumen terkunci; dikosongkan lagi begitu kekurangan lunas (webhook gateway).
     * NULL = tidak ada kekurangan (semua aplikasi lama).
     */
    public function up(): void
    {
        Schema::table('university_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('university_applications', 'registration_fee_due')) {
                $table->unsignedBigInteger('registration_fee_due')->nullable()->after('registration_fee_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('university_applications', function (Blueprint $table) {
            if (Schema::hasColumn('university_applications', 'registration_fee_due')) {
                $table->dropColumn('registration_fee_due');
            }
        });
    }
};
