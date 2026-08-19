<?php

namespace App\Services\Payment;

use App\Models\PaymentGateway;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Gateways\DuitkuGateway;
use App\Services\Payment\Gateways\IpaymuGateway;
use App\Services\Payment\Gateways\MidtransGateway;
use RuntimeException;

class PaymentGatewayFactory
{
    /**
     * Buat instance driver gateway pembayaran dari 1 baris konfigurasi
     * PaymentGateway (kredensial + environment sandbox/production).
     */
    public static function make(PaymentGateway $config): PaymentGatewayInterface
    {
        return match ($config->gateway) {
            'midtrans' => new MidtransGateway($config),
            'duitku' => new DuitkuGateway($config),
            'ipaymu' => new IpaymuGateway($config),
            default => throw new RuntimeException("Gateway pembayaran '{$config->gateway}' tidak dikenal."),
        };
    }
}
