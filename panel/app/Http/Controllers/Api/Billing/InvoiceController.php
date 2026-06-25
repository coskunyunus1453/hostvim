<?php

namespace App\Http\Controllers\Api\Billing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Billing\InvoicePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class InvoiceController extends Controller
{
    public function __construct(private InvoicePaymentService $payments) {}

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

    public function pay(Request $request, Invoice $invoice): JsonResponse
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);

        try {
            return response()->json($this->payments->initiate($invoice, $request->user()));
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => $e->getMessage() ?: 'Ödeme başlatılamadı.'], 422);
        }
    }
}
