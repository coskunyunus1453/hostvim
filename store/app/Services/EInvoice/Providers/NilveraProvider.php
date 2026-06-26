<?php

namespace App\Services\EInvoice\Providers;

use App\Models\Invoice;
use App\Services\EInvoice\EInvoiceResult;
use App\Services\EInvoice\EInvoiceSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Nilvera e-Fatura / e-Arşiv entegrasyonu.
 * REST + Bearer token. Test: apitest.nilvera.com, Prod: api.nilvera.com.
 *
 * Not: Nilvera'nın model şeması zamanla güncellenebilir. Gerçek hesapla ilk gönderimde
 * Swagger (https://apitest.nilvera.com/swagger/docs/v1/einvoice) ile alan adlarını doğrulayın.
 */
class NilveraProvider extends AbstractEInvoiceProvider
{
    public function key(): string
    {
        return 'nilvera';
    }

    public function label(): string
    {
        return 'Nilvera';
    }

    public function isConfigured(): bool
    {
        return (string) EInvoiceSettings::get('e_invoice.nilvera_api_key', '') !== '';
    }

    private function baseUrl(): string
    {
        return EInvoiceSettings::testMode()
            ? 'https://apitest.nilvera.com'
            : 'https://api.nilvera.com';
    }

    private function client()
    {
        return Http::baseUrl($this->baseUrl())
            ->withToken((string) EInvoiceSettings::get('e_invoice.nilvera_api_key', ''))
            ->acceptJson()
            ->timeout(40);
    }

    public function issue(Invoice $invoice): EInvoiceResult
    {
        if (! $this->isConfigured()) {
            return EInvoiceResult::failure('Nilvera API anahtarı tanımlı değil.');
        }

        $isEInvoice = $invoice->type === Invoice::TYPE_EINVOICE;
        $endpoint = $isEInvoice ? '/einvoice/Send/Model' : '/earchive/Send/Model';

        try {
            $response = $this->client()->post($endpoint, $this->buildModel($invoice));
        } catch (\Throwable $e) {
            Log::error('nilvera.issue_exception', ['invoice' => $invoice->id, 'error' => $e->getMessage()]);

            return EInvoiceResult::failure('Nilvera bağlantı hatası: '.$e->getMessage());
        }

        if (! $response->successful()) {
            $msg = $this->extractError($response->json(), $response->body());

            return EInvoiceResult::failure('Nilvera reddetti: '.$msg, ['body' => $response->body()]);
        }

        $data = $response->json() ?? [];
        $uuid = $data['UUID'] ?? $data['InvoiceUUID'] ?? $data['uuid'] ?? null;

        return new EInvoiceResult(
            ok: true,
            message: 'Fatura Nilvera üzerinden gönderildi.',
            uuid: $uuid !== null ? (string) $uuid : null,
            providerInvoiceId: isset($data['InvoiceId']) ? (string) $data['InvoiceId'] : null,
            status: Invoice::STATUS_ISSUED,
            raw: is_array($data) ? $data : [],
        );
    }

    public function refreshStatus(Invoice $invoice): EInvoiceResult
    {
        if ($invoice->provider_uuid === null) {
            return EInvoiceResult::failure('Fatura UUID yok.');
        }

        $isEInvoice = $invoice->type === Invoice::TYPE_EINVOICE;
        $endpoint = ($isEInvoice ? '/einvoice/Status/' : '/earchive/Status/').$invoice->provider_uuid;

        try {
            $response = $this->client()->get($endpoint);
        } catch (\Throwable $e) {
            return EInvoiceResult::failure('Nilvera bağlantı hatası: '.$e->getMessage());
        }

        if (! $response->successful()) {
            return EInvoiceResult::failure('Durum alınamadı: '.$response->status());
        }

        $data = $response->json() ?? [];
        $status = $this->mapStatus((string) ($data['Status'] ?? $data['status'] ?? ''));

        return new EInvoiceResult(ok: true, message: 'Durum güncellendi.', status: $status, raw: $data);
    }

    public function downloadPdf(Invoice $invoice): ?string
    {
        if ($invoice->provider_uuid === null) {
            return null;
        }

        $isEInvoice = $invoice->type === Invoice::TYPE_EINVOICE;
        $endpoint = ($isEInvoice ? '/einvoice/Pdf/' : '/earchive/Pdf/').$invoice->provider_uuid;

        try {
            $response = $this->client()->get($endpoint);
            if ($response->successful()) {
                return $response->body();
            }
        } catch (\Throwable $e) {
            Log::warning('nilvera.pdf_failed', ['invoice' => $invoice->id, 'error' => $e->getMessage()]);
        }

        return null;
    }

    public function isEInvoiceUser(?string $taxNumber): ?bool
    {
        if ($taxNumber === null || trim($taxNumber) === '') {
            return null;
        }

        try {
            $response = $this->client()->get('/general/GlobalCompany/Check/TaxNumber/'.trim($taxNumber));
            if ($response->successful()) {
                $data = $response->json() ?? [];

                return (bool) ($data['IsEInvoiceUser'] ?? $data['isEInvoiceUser'] ?? ! empty($data));
            }
        } catch (\Throwable) {
            // bilinmiyor
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function buildModel(Invoice $invoice): array
    {
        $company = $this->company();
        $lines = $this->normalizedLines($invoice);

        return [
            'InvoiceInfo' => [
                'UUID' => (string) \Illuminate\Support\Str::uuid(),
                'InvoiceType' => 'SATIS',
                'InvoiceProfile' => $invoice->type === Invoice::TYPE_EINVOICE ? 'TICARIFATURA' : 'EARSIVFATURA',
                'CurrencyCode' => $invoice->currency,
                'IssueDate' => now()->format('Y-m-d'),
            ],
            'CompanyInfo' => [
                'TaxNumber' => $company['tax_number'],
                'Name' => $company['title'],
                'TaxOffice' => $company['tax_office'],
                'Address' => $company['address'],
            ],
            'CustomerInfo' => [
                'TaxNumber' => $invoice->customer_tax_number ?: '11111111111',
                'Name' => $invoice->customer_name,
                'TaxOffice' => $invoice->customer_tax_office,
                'Address' => $invoice->customer_address,
                'Email' => $invoice->customer_email,
            ],
            'InvoiceLines' => array_map(fn (array $l) => [
                'Name' => $l['name'],
                'Quantity' => $l['quantity'],
                'UnitType' => 'C62',
                'Price' => $l['unit_price'],
                'KDVPercent' => $l['tax_rate'],
                'KDVAmount' => $l['tax_amount'],
                'LineAmount' => $l['line_total'],
            ], $lines),
            'Notes' => ['HostVim sipariş no: '.($invoice->order?->order_number ?? '-')],
        ];
    }

    private function mapStatus(string $remote): string
    {
        return match (strtoupper($remote)) {
            'SUCCEED', 'SUCCESS', 'SENT', 'DELIVERED' => Invoice::STATUS_SENT,
            'ACCEPTED', 'APPROVED' => Invoice::STATUS_ACCEPTED,
            'REJECTED', 'REFUSED' => Invoice::STATUS_REJECTED,
            'ERROR', 'FAILED' => Invoice::STATUS_ERROR,
            default => Invoice::STATUS_ISSUED,
        };
    }

    /**
     * @param  mixed  $json
     */
    private function extractError($json, string $body): string
    {
        if (is_array($json)) {
            return (string) ($json['Message'] ?? $json['message'] ?? $json['error'] ?? ($json['Errors'][0] ?? $body));
        }

        return $body !== '' ? $body : 'Bilinmeyen hata';
    }
}
