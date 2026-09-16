<?php

namespace App\Services\CoursePackagePayment;

use App\Models\PaymentGateway;
use App\Services\CoursePackagePayment\Contracts\CoursePackagePaymentGatewayInterface;
use App\Services\CoursePackagePayment\Gateways\CoursePackageDuitkuGateway;
use App\Services\CoursePackagePayment\Gateways\CoursePackageIpaymuGateway;
use App\Services\CoursePackagePayment\Gateways\CoursePackageMidtransGateway;
use RuntimeException;

class CoursePackagePaymentGatewayFactory
{
    /**
     * Buat instance driver gateway checkout package dari 1 baris konfigurasi
     * PaymentGateway (kredensial + environment sandbox/production) --
     * konfigurasinya SAMA (tabel payment_gateways) dengan yang dipakai
     * form_payments & topup saldo, cuma driver pemanggilnya yang independen.
     */
    public static function make(PaymentGateway $config): CoursePackagePaymentGatewayInterface
    {
        return match ($config->gateway) {
            'midtrans' => new CoursePackageMidtransGateway($config),
            'duitku' => new CoursePackageDuitkuGateway($config),
            'ipaymu' => new CoursePackageIpaymuGateway($config),
            default => throw new RuntimeException("Gateway pembayaran '{$config->gateway}' tidak dikenal."),
        };
    }
}
