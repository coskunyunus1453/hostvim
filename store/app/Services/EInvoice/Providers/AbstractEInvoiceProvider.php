<?php

namespace App\Services\EInvoice\Providers;

use App\Models\Invoice;
use App\Services\EInvoice\EInvoiceProvider;
use App\Services\EInvoice\EInvoiceResult;
use App\Services\EInvoice\EInvoiceSettings;

abstract class AbstractEInvoiceProvider implements EInvoiceProvider
{
    public function refreshStatus(Invoice $invoice): EInvoiceResult
    {
        return EInvoiceResult::failure('Bu sağlayıcı durum sorgulamayı desteklemiyor.');
    }

    public function downloadPdf(Invoice $invoice): ?string
    {
        return null;
    }

    public function isEInvoiceUser(?string $taxNumber): ?bool
    {
        return null;
    }

    /**
     * Fatura kalemlerini sağlayıcıdan bağımsız tek tip diziye çevirir.
     *
     * @return list<array{name: string, quantity: int, unit_price: float, line_total: float, tax_rate: float, tax_amount: float}>
     */
    protected function normalizedLines(Invoice $invoice): array
    {
        $rate = (float) ($invoice->tax_rate ?: EInvoiceSettings::taxRate());
        $includesTax = EInvoiceSettings::priceIncludesTax();
        $out = [];

        foreach ($invoice->lines() as $line) {
            $qty = max(1, (int) ($line['quantity'] ?? 1));
            $gross = (float) ($line['total'] ?? ($line['unit_price'] ?? 0) * $qty);

            if ($includesTax) {
                $net = round($gross / (1 + $rate / 100), 2);
                $tax = round($gross - $net, 2);
            } else {
                $net = round($gross, 2);
                $tax = round($gross * $rate / 100, 2);
            }

            $out[] = [
                'name' => (string) ($line['name'] ?? 'Hizmet'),
                'quantity' => $qty,
                'unit_price' => $qty > 0 ? round($net / $qty, 4) : $net,
                'line_total' => $net,
                'tax_rate' => $rate,
                'tax_amount' => $tax,
            ];
        }

        return $out;
    }

    /** @return array<string, mixed> */
    protected function company(): array
    {
        return EInvoiceSettings::company();
    }
}
