<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\PaymentMethod;
use InvalidArgumentException;

class PaymentManager
{
    public function gateway(string $code): PaymentGatewayInterface
    {
        return match ($code) {
            'paytr' => app(PayTRGateway::class),
            'iyzico' => app(IyzicoGateway::class),
            'bank_transfer' => app(BankTransferGateway::class),
            'stripe' => app(StripeGateway::class),
            'paypal' => app(PayPalGateway::class),
            'payoneer' => app(PayoneerGateway::class),
            default => throw new InvalidArgumentException("Desteklenmeyen ödeme yöntemi: {$code}"),
        };
    }

    public function initiate(Order $order, PaymentMethod $method): array
    {
        return $this->gateway($method->code)->initiate($order, $method);
    }

    public function handleCallback(string $code, array $data): Order
    {
        return $this->gateway($code)->handleCallback($data);
    }
}
