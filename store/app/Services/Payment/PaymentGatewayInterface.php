<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\PaymentMethod;

interface PaymentGatewayInterface
{
    public function initiate(Order $order, PaymentMethod $method): array;

    public function handleCallback(array $data): Order;

    public function verify(array $data): bool;
}
