<?php

namespace App\Services\EInvoice\Providers;

use App\Models\Invoice;
use App\Services\EInvoice\EInvoiceResult;
use App\Services\EInvoice\EInvoiceSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mükellef (mukellef.co) e-Fatura / e-Arşiv entegrasyonu.
 * Bearer token ile REST. Düşük/orta hacim için uygun, modern API.
 *
 * Endpoint adları hesabınızdaki API dokümanına göre teyit edilmelidir;
 * varsayılan olarak /v1/invoices kullanılır.
 */
class MukellefProvider extends AbstractEInvoiceProvider
{
    private const BASE = 'https://api.mukellef.co';

    public function key(): string
    {
        return 'mukellef';
    }

    public function label(): string
    {
        return 'Mükellef';
    }

    public function isConfigured(): bool
    {
        return (string) EInvoiceSettings::get('e_invoice.mukellef_api_key', '') !== '';
    }

    private function client()
    {
        return Http::baseUrl(self::BASE)
            ->withToken((string) EInvoiceSettings::get('e_invoice.mukellef_api_key', ''))
            ->acceptJson()
            ->timeout(40);
    }

    public function issue(Invoice $invoice): EInvoiceResult
    {
        if (! $this->isConfigured()) {
            return EInvoiceResult::failure('Mükellef API anahtarı tanımlı değil.');
        }

        try {
            $response = $this->client()->post('/v1/invoices', $this->buildPayload($invoice));
        } catch (\Throwable $e) {
            Log::error('mukellef.issue_exception', ['invoice' => $invoice->id, 'error' => $e->getMessage()]);

            return EInvoiceResult::failure('Mükellef bağlantı hatası: '.$e->getMessage());
        }

        if (! $response->successful()) {
            return EInvoiceResult::failure('Mükellef reddetti: '.$response->body(), ['body' => $response->body()]);
        }

        $data = $response->json() ?? [];
        $uuid = $data['uuid'] ?? $data['data']['uuid'] ?? null;
        $id = $data['id'] ?? $data['data']['id'] ?? null;

        return new EInvoiceResult(
            ok: true,
            message: 'Fatura Mükellef üzerinden gönderildi.',
            uuid: $uuid !== null ? (string) $uuid : null,
            providerInvoiceId: $id !== null ? (string) $id : null,
            status: Invoice::STATUS_ISSUED,
            raw: is_array($data) ? $data : [],
        );
    }

    public function downloadPdf(Invoice $invoice): ?string
    {
        $id = $invoice->provider_invoice_id ?: $invoice->provider_uuid;
        if ($id === null) {
            return null;
        }

        try {
            $response = $this->client()->get('/v1/invoices/'.$id.'/pdf');
            if ($response->successful()) {
                return $response->body();
            }
        } catch (\Throwable $e) {
            Log::warning('mukellef.pdf_failed', ['invoice' => $invoice->id, 'error' => $e->getMessage()]);
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function buildPayload(Invoice $invoice): array
    {
        $company = $this->company();
        $items = [];
        foreach ($this->normalizedLines($invoice) as $l) {
            $items[] = [
                'name' => $l['name'],
                'quantity' => $l['quantity'],
                'unit_price' => $l['unit_price'],
                'vat_rate' => $l['tax_rate'],
            ];
        }

        return [
            'type' => $invoice->type === Invoice::TYPE_EINVOICE ? 'e_invoice' : 'e_archive',
            'currency' => $invoice->currency,
            'issue_date' => now()->format('Y-m-d'),
            'seller' => [
                'title' => $company['title'],
                'tax_number' => $company['tax_number'],
                'tax_office' => $company['tax_office'],
            ],
            'customer' => [
                'name' => $invoice->customer_name,
                'email' => $invoice->customer_email,
                'tax_number' => $invoice->customer_tax_number,
                'tax_office' => $invoice->customer_tax_office,
                'address' => $invoice->customer_address,
            ],
            'items' => $items,
            'note' => 'HostVim sipariş no: '.($invoice->order?->order_number ?? '-'),
        ];
    }
}
