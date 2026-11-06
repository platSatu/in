<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 6 November 2026: gateway "bb" (App\Services\Payment\Gateways\BbGateway) untuk pendaftaran & quiz.
 * Kolom gateway di payment_gateways & form_payments masih ENUM tanpa "bb", jadi simpan gateway BB gagal.
 * Hanya MENAMBAH nilai di akhir daftar ENUM: data lama tidak berubah (di MySQL ini cukup ubah metadata).
 * application_payments.gateway sudah string, tidak perlu diubah. Topup & paket InaYule tidak memakai BB.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE payment_gateways MODIFY gateway ENUM('duitku', 'midtrans', 'ipaymu', 'bb') NOT NULL");
        DB::statement("ALTER TABLE form_payments MODIFY gateway ENUM('duitku', 'midtrans', 'ipaymu', 'bb') NOT NULL");
    }

    public function down(): void
    {
        // Hanya dikembalikan kalau belum ada data BB, supaya tidak ada data yang terpotong.
        if (DB::table('payment_gateways')->where('gateway', 'bb')->exists() || DB::table('form_payments')->where('gateway', 'bb')->exists()) {
            throw new RuntimeException('Masih ada data gateway BB, rollback dibatalkan.');
        }

        DB::statement("ALTER TABLE form_payments MODIFY gateway ENUM('duitku', 'midtrans', 'ipaymu') NOT NULL");
        DB::statement("ALTER TABLE payment_gateways MODIFY gateway ENUM('duitku', 'midtrans', 'ipaymu') NOT NULL");
    }
};
