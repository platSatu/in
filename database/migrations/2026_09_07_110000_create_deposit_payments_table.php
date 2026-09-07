<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| === DEPOSIT PAYMENTS ===
| Satu baris = satu percobaan transaksi topup saldo lewat payment gateway
| (tombol "+ Tambah Saldo" di dropdown profile -- lihat
| resources/views/layouts/partials/header.blade.php). Polanya SENGAJA
| disamakan persis dengan `form_payments` (lihat migration
| create_form_payments_table & FormPaymentController): status HANYA boleh
| berubah jadi "paid" lewat webhook resmi gateway
| (App\Http\Controllers\Dashboard\DepositWebhookController), tidak pernah
| dari request browser/redirect biasa -- supaya tidak bisa dipalsukan.
|
| Tabel ini TERPISAH dari `form_payments` (isolasi disengaja, keputusan
| owner) supaya integrasi topup saldo ini tidak menyentuh sama sekali alur
| pembayaran Form/Quiz yang sudah live -- lihat juga namespace terpisah
| App\Services\DepositPayment\* (beda dari App\Services\Payment\* yang
| dipakai form_payments).
|
| Begitu status = 'paid' (lewat webhook), baris `deposits` + `transactions`
| BARU dibuat di titik itu juga (App\Models\Deposit / App\Models\Transaction)
| -- tabel ini sendiri cuma jejak sesi checkout ke gateway, BUKAN ledger
| saldo (ledger saldo tetap satu-satunya sumber kebenaran di `deposits` &
| `transactions`, supaya konsisten dengan modul Deposit yang sudah ada).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('user_id', 36);
            $table->foreignUuid('payment_gateway_id')
                ->nullable()
                ->constrained('payment_gateways')
                ->nullOnDelete();

            $table->string('order_id', 50)->unique();
            $table->enum('gateway', ['duitku', 'midtrans', 'ipaymu']);

            // name/email/handphone diambil dari profil user yang topup (bukan
            // diisi manual seperti form_payments -- topup ini SELALU untuk
            // saldo akun sendiri yang sedang login), disimpan sekalian supaya
            // riwayat gateway-nya tetap utuh walau profil user berubah nanti.
            $table->string('name');
            $table->string('email');
            $table->string('handphone', 20)->nullable();

            $table->decimal('amount', 12, 2)->default(0);

            $table->enum('status', ['pending', 'paid', 'failed', 'expired'])->default('pending');

            $table->string('payment_method', 20)->nullable();
            $table->text('payment_url')->nullable();
            $table->string('gateway_reference')->nullable();

            $table->json('raw_response')->nullable();
            $table->json('raw_callback')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'status']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_payments');
    }
};
