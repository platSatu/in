<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batas waktu bayar Registration Fee (26 November 2026): diisi HANYA untuk aplikasi yang dibuat dari checkout
 * siswa (ApplyController::checkout), = waktu checkout + 1 jam. Lewat dari itu tanpa pembayaran lunas dan tanpa
 * transaksi yang masih berjalan, aplikasi dibatalkan otomatis (UniversityApplication::cancelOverdue).
 *
 * NULL = tidak pernah dibatalkan otomatis: semua aplikasi lama, dan aplikasi yang didaftarkan admin
 * (ManualApplicationRegistrar), jadi data yang sudah berjalan tidak tersentuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('university_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('university_applications', 'payment_deadline_at')) {
                $table->timestamp('payment_deadline_at')->nullable()->after('submitted_at')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('university_applications', function (Blueprint $table) {
            if (Schema::hasColumn('university_applications', 'payment_deadline_at')) {
                $table->dropIndex(['payment_deadline_at']);
                $table->dropColumn('payment_deadline_at');
            }
        });
    }
};
