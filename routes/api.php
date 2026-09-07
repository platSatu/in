<?php

use App\Http\Controllers\Payment\FormPaymentController;
use App\Http\Controllers\Dashboard\DepositWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Webhook server-to-server dari gateway pembayaran (Midtrans/Duitku/iPaymu).
// Sengaja ditaruh di routes/api.php karena group "api" tidak pakai middleware
// CSRF (server gateway tidak bisa kirim CSRF token). Signature tiap gateway
// tetap diverifikasi manual di FormPaymentController sebelum status transaksi
// diubah, jadi endpoint ini tetap aman meskipun tanpa CSRF.
Route::post('/payment/webhook/midtrans', [FormPaymentController::class, 'midtransWebhook'])->name('payment.webhook.midtrans');
Route::post('/payment/webhook/duitku', [FormPaymentController::class, 'duitkuWebhook'])->name('payment.webhook.duitku');
Route::post('/payment/webhook/ipaymu', [FormPaymentController::class, 'ipaymuWebhook'])->name('payment.webhook.ipaymu');

// Webhook topup saldo -- jalur TERPISAH dari webhook form_payments di atas
// (lihat App\Services\DepositPayment\* & App\Http\Controllers\Dashboard\
// DepositWebhookController), keputusan owner supaya fitur topup saldo tidak
// menyentuh sama sekali alur pembayaran Form/Quiz yang sudah live. Sama
// seperti webhook form_payments, sengaja ditaruh di routes/api.php karena
// group "api" tidak pakai middleware CSRF (server gateway tidak bisa kirim
// CSRF token) -- signature tiap gateway tetap diverifikasi manual di
// DepositWebhookController sebelum saldo dikreditkan.
Route::post('/deposit/webhook/midtrans', [DepositWebhookController::class, 'midtransWebhook'])->name('deposit.webhook.midtrans');
Route::post('/deposit/webhook/duitku', [DepositWebhookController::class, 'duitkuWebhook'])->name('deposit.webhook.duitku');
Route::post('/deposit/webhook/ipaymu', [DepositWebhookController::class, 'ipaymuWebhook'])->name('deposit.webhook.ipaymu');
