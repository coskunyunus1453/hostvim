<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\Invoice\InvoiceService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoices) {}

    /** Admin: herhangi bir faturanın PDF'ini görüntüle/indir. */
    public function adminPdf(Request $request, Invoice $invoice): Response
    {
        abort_unless((bool) ($request->user()?->is_admin), 403);

        return $this->stream($invoice);
    }

    /** Müşteri: yalnızca kendi faturasının PDF'i. */
    public function customerPdf(Request $request, Invoice $invoice): Response
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $owns = ($invoice->order && $invoice->order->user_id === $user->id)
            || ($invoice->customer_email !== null && strcasecmp($invoice->customer_email, (string) $user->email) === 0);

        abort_unless($owns, 403);

        return $this->stream($invoice);
    }

    private function stream(Invoice $invoice): Response
    {
        $content = $this->invoices->pdfContents($invoice);

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$invoice->invoice_number.'.pdf"',
        ]);
    }
}
