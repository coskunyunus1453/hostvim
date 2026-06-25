<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\PaymentMethod;

class BankTransferGateway implements PaymentGatewayInterface
{
    public function initiate(Order $order, PaymentMethod $method): array
    {
        $order->update([
            'payment_status' => 'awaiting_transfer',
            'status' => 'pending',
        ]);

        return [
            'type' => 'bank_transfer',
            'instructions' => $method->instructions,
            'order_number' => $order->order_number,
            'amount' => $order->total,
            'currency' => $order->currency,
        ];
    }

    public function handleCallback(array $data): Order
    {
        $order = Order::where('order_number', $data['order_number'] ?? '')->firstOrFail();

        if (($data['action'] ?? '') === 'confirm') {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
            ]);
        }

        return $order;
    }

    public function verify(array $data): bool
    {
        return true;
    }
}
