<?php

namespace App\Http\Controllers\Api\Billing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Billing\BillingSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\StripeClient;

class InvoiceController extends Controller
{
    public function __construct(private BillingSettings $settings) {}

    public function index(Request $request): JsonResponse
    {
        $invoices = $request->user()->invoices()
            ->with('items')
            ->latest()
            ->paginate(20);

        return response()->json($invoices);
    }

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);

        return response()->json($invoice->load('items', 'subscription.hostingPackage', 'subscription.domain'));
    }

    /**
     * Ödeme başlat: Stripe yapılandırılmışsa tek seferlik ödeme oturumu döner;
     * aksi halde manuel ödeme talimatlarını (havale vb.) döner.
     */
    public function pay(Request $request, Invoice $invoice): JsonResponse
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);

        if (! $invoice->isPayable()) {
            return response()->json(['message' => 'Bu fatura ödenebilir durumda değil.'], 422);
        }

        $secret = config('services.stripe.secret');
        if (! $secret) {
            return response()->json([
                'method' => 'manual',
                'instructions' => (string) $this->settings->get('payment_instructions', ''),
                'amount' => (float) $invoice->total,
                'currency' => $invoice->currency,
                'reference' => $invoice->number,
            ]);
        }

        $stripe = new StripeClient($secret);
        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'customer_email' => $request->user()->email,
            'client_reference_id' => (string) $request->user()->id,
            'metadata' => ['invoice_id' => (string) $invoice->id],
            'payment_intent_data' => ['metadata' => ['invoice_id' => (string) $invoice->id]],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($invoice->currency),
                    'product_data' => ['name' => 'Fatura '.$invoice->number],
                    'unit_amount' => (int) round((float) $invoice->total * 100),
                ],
                'quantity' => 1,
            ]],
            'success_url' => url('/billing?invoice='.$invoice->id.'&paid=1'),
            'cancel_url' => url('/billing?invoice='.$invoice->id),
        ]);

        return response()->json(['method' => 'stripe', 'url' => $session->url, 'id' => $session->id]);
    }
}
