<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\Payment\PayPalGateway;
use App\Services\Payment\PayoneerGateway;
use App\Services\Payment\PaymentManager;
use App\Services\Payment\StripeGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function paytrCallback(Request $request, PaymentManager $payments)
    {
        try {
            $payments->handleCallback('paytr', $request->all());
        } catch (\Throwable $e) {
            Log::error('PayTR callback hatası', ['message' => $e->getMessage()]);
        }

        return response('OK');
    }

    public function iyzicoCallback(Request $request, PaymentManager $payments)
    {
        $order = $payments->handleCallback('iyzico', $request->all());

        return $this->redirectAfterPayment($order);
    }

    public function stripeReturn(Request $request, Order $order, StripeGateway $stripe)
    {
        $sessionId = (string) $request->query('session_id', '');
        if ($sessionId === '') {
            return redirect()->to(
                \Illuminate\Support\Facades\URL::temporarySignedRoute('payment.fail', now()->addDays(7), ['order' => $order->id])
            );
        }

        $order = $stripe->completeReturn($order, $sessionId);

        return $this->redirectAfterPayment($order);
    }

    public function stripeWebhook(Request $request, StripeGateway $stripe)
    {
        $method = PaymentMethod::query()->where('code', 'stripe')->where('is_active', true)->first();
        if ($method === null) {
            return response('Stripe inactive', 404);
        }

        $ok = $stripe->handleWebhook($request->getContent(), $request->header('Stripe-Signature', ''), $method);

        return response($ok ? 'OK' : 'Invalid', $ok ? 200 : 400);
    }

    public function paypalReturn(Request $request, Order $order, PayPalGateway $paypal)
    {
        $paypalOrderId = (string) $request->query('token', '');
        if ($paypalOrderId === '') {
            return redirect()->to(
                \Illuminate\Support\Facades\URL::temporarySignedRoute('payment.fail', now()->addDays(7), ['order' => $order->id])
            );
        }

        $order = $paypal->captureReturn($order, $paypalOrderId);

        return $this->redirectAfterPayment($order);
    }

    public function payoneerReturn(Request $request, Order $order, PayoneerGateway $payoneer)
    {
        $requestId = (string) $request->query('payment_request_id', $request->query('id', ''));
        $order = $payoneer->completeReturn($order, $requestId !== '' ? $requestId : null);

        return $this->redirectAfterPayment($order);
    }

    public function payoneerWebhook(Request $request, PayoneerGateway $payoneer)
    {
        try {
            $payoneer->handleWebhook($request->all());
        } catch (\Throwable $e) {
            Log::error('Payoneer webhook hatası', ['message' => $e->getMessage()]);

            return response('Error', 500);
        }

        return response('OK');
    }

    protected function redirectAfterPayment(Order $order)
    {
        if ($order->payment_status === 'paid') {
            return redirect()->to(
                \Illuminate\Support\Facades\URL::temporarySignedRoute('payment.success', now()->addDays(7), ['order' => $order->id])
            );
        }

        return redirect()->to(
            \Illuminate\Support\Facades\URL::temporarySignedRoute('payment.fail', now()->addDays(7), ['order' => $order->id])
        );
    }
}
