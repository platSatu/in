<?php

namespace App\Services\CoursePackagePayment;

use RuntimeException;

/**
 * Dilempar App\Http\Controllers\StudentPortal\InaYulePackageWebhookController
 * ::processUpgradeConfirmation() KHUSUS untuk membatalkan (rollback) SELURUH
 * DB transaction upgrade -- termasuk trade-in credit yang sudah sempat
 * dieksekusi di dalamnya -- kalau ternyata nilai trade-in atau saldo Deposit
 * tidak lagi cukup saat gateway konfirmasi. Pesan exception ini dipakai
 * sebagai kode status singkat ('insufficient_trade_in' /
 * 'insufficient_deposit'), BUKAN pesan untuk ditampilkan ke user.
 */
class UpgradeConfirmationFailedException extends RuntimeException
{
}
