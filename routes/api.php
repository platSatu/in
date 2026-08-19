<?php

use App\Http\Controllers\Payment\FormPaymentController;
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
