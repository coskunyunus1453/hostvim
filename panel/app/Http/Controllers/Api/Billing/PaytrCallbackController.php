<?php

namespace App\Http\Controllers\Api\Billing;

use App\Http\Controllers\Controller;
use App\Services\Billing\InvoicePaymentService;
use App\Services\Billing\Payment\PaytrBillingGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PaytrCallbackController extends Controller
{
    public function __invoke(Request $request, PaytrBillingGateway $paytr, InvoicePaymentService $payments): Response
    {
        $post = $request->all();
        if (! $paytr->verifyCallback($post)) {
            Log::warning('PayTR billing callback: bad hash', ['merchant_oid' => $post['merchant_oid'] ?? '']);

            return response('BAD HASH', 400);
        }

        $payments->completePaytr(
            merchantRef: (string) ($post['merchant_oid'] ?? ''),
            status: (string) ($post['status'] ?? ''),
            totalAmount: (string) ($post['total_amount'] ?? '0'),
            paymentId: isset($post['payment_id']) ? (string) $post['payment_id'] : null,
        );

        return response('OK');
    }
}
