<?php

namespace App\Services\Payment;

use RuntimeException;

/**
 * Dilempar kalau signature pada callback/webhook gateway pembayaran tidak
 * cocok dengan yang dihitung ulang dari kredensial kita. Jangan pernah
 * menandai transaksi "paid" kalau exception ini muncul.
 */
class PaymentSignatureMismatchException extends RuntimeException
{
}
