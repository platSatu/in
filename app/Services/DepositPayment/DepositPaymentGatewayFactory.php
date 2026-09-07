<?php

namespace App\Services\DepositPayment;

use App\Models\PaymentGateway;
use App\Services\DepositPayment\Contracts\DepositPaymentGatewayInterface;
use App\Services\DepositPayment\Gateways\DepositDuitkuGateway;
use App\Services\DepositPayment\Gateways\DepositIpaymuGateway;
use App\Services\DepositPayment\Gateways\DepositMidtransGateway;
use RuntimeException;

class DepositPaymentGatewayFactory
{
    /**
     * Buat instance driver gateway topup saldo dari 1 baris konfigurasi
     * PaymentGateway (kredensial + environment sandbox/production) --
     * konfigurasinya SAMA (tabel payment_gateways) dengan yang dipakai
     * form_payments, cuma driver pemanggilnya yang independen.
     */
    public static function make(PaymentGateway $config): DepositPaymentGatewayInterface
    {
        return match ($config->gateway) {
            'midtrans' => new DepositMidtransGateway($config),
            'duitku' => new DepositDuitkuGateway($config),
            'ipaymu' => new DepositIpaymuGateway($config),
            default => throw new RuntimeException("Gateway pembayaran '{$config->gateway}' tidak dikenal."),
        };
    }
}
