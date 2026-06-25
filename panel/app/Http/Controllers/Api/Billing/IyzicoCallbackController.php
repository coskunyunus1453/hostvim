<?php

namespace App\Http\Controllers\Api\Billing;

use App\Http\Controllers\Controller;
use App\Services\Billing\InvoicePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IyzicoCallbackController extends Controller
{
    public function __invoke(Request $request, InvoicePaymentService $payments): RedirectResponse
    {
        $token = (string) $request->input('token', '');
        if ($token !== '') {
            $payments->completeIyzico($token);
        }

        return redirect('/invoices?paid=1');
    }
}
