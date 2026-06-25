<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\Panel\PanelCustomerService;
use Illuminate\Http\Request;
use RuntimeException;

class InvoiceController extends Controller
{
    public function index(Request $request, PanelCustomerService $panel)
    {
        $user = $request->user();

        if (! $user->panel_user_id) {
            return view('account.invoices', ['linked' => false, 'invoices' => []]);
        }

        try {
            $result = $panel->invoices($user, max(1, (int) $request->query('page', 1)));
        } catch (RuntimeException $e) {
            return view('account.invoices', [
                'linked' => true,
                'invoices' => [],
                'error' => $e->getMessage(),
            ]);
        }

        return view('account.invoices', [
            'linked' => true,
            'invoices' => $result['data'] ?? [],
            'meta' => $result['meta'] ?? [],
        ]);
    }

    public function show(Request $request, PanelCustomerService $panel, int $invoiceId)
    {
        try {
            $result = $panel->invoice($request->user(), $invoiceId);
        } catch (RuntimeException $e) {
            return redirect()->route('account.invoices')->with('error', $e->getMessage());
        }

        return view('account.invoice-show', [
            'invoice' => $result['invoice'] ?? null,
        ]);
    }

    public function pay(Request $request, PanelCustomerService $panel, int $invoiceId)
    {
        try {
            $result = $panel->payInvoice($request->user(), $invoiceId);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        if (! empty($result['redirect_url'])) {
            return redirect()->away($result['redirect_url']);
        }
        if (! empty($result['html'])) {
            return response($result['html']);
        }

        return back()->with('success', $result['message'] ?? 'Ödeme işlemi başlatıldı.');
    }
}
