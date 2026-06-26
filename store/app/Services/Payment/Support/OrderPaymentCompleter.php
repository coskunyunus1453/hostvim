<?php

namespace App\Services\Payment\Support;

use App\Models\Order;

class OrderPaymentCompleter
{
    /**
     * @param  array<string, mixed>  $paymentData
     */
    public function markPaid(Order $order, ?string $reference = null, array $paymentData = []): Order
    {
        if ($order->payment_status === 'paid') {
            return $order;
        }

        $order->update([
            'payment_status' => 'paid',
            'status' => 'processing',
            'payment_reference' => $reference,
            'payment_data' => array_merge($order->payment_data ?? [], $paymentData),
        ]);

        return $order->fresh();
    }

    /**
     * @param  array<string, mixed>  $paymentData
     */
    public function markFailed(Order $order, array $paymentData = []): Order
    {
        if ($order->payment_status === 'paid') {
            return $order;
        }

        $order->update([
            'payment_status' => 'failed',
            'status' => 'cancelled',
            'payment_data' => array_merge($order->payment_data ?? [], $paymentData),
        ]);

        return $order->fresh();
    }
}
