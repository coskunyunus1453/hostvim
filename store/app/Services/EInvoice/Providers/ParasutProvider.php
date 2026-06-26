<?php

namespace App\Services\EInvoice\Providers;

use App\Models\Invoice;
use App\Services\EInvoice\EInvoiceResult;
use App\Services\EInvoice\EInvoiceSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Paraşüt (api.parasut.com) e-Fatura / e-Arşiv entegrasyonu.
 * OAuth2 (password grant) → satış faturası oluştur → e-belge dönüşümü.
 *
 * Akış: sales_invoices oluştur, ardından e_archives/e_invoices ile resmileştir.
 * İlk gerçek gönderimden önce company_id ve grant bilgilerini doğrulayın.
 */
class ParasutProvider extends AbstractEInvoiceProvider
{
    private const BASE = 'https://api.parasut.com/v4';

    private const TOKEN_URL = 'https://api.parasut.com/oauth/token';

    public function key(): string
    {
        return 'parasut';
    }

    public function label(): string
    {
        return 'Paraşüt';
    }

    public function isConfigured(): bool
    {
        return (string) EInvoiceSettings::get('e_invoice.parasut_client_id', '') !== ''
            && (string) EInvoiceSettings::get('e_invoice.parasut_username', '') !== ''
            && (string) EInvoiceSettings::get('e_invoice.parasut_company_id', '') !== '';
    }

    private function companyId(): string
    {
        return (string) EInvoiceSettings::get('e_invoice.parasut_company_id', '');
    }

    private function token(): ?string
    {
        return Cache::remember('einvoice.parasut.token', 6000, function (): ?string {
            try {
                $response = Http::asForm()->acceptJson()->timeout(30)->post(self::TOKEN_URL, [
                    'grant_type' => 'password',
                    'client_id' => (string) EInvoiceSettings::get('e_invoice.parasut_client_id', ''),
                    'client_secret' => (string) EInvoiceSettings::get('e_invoice.parasut_client_secret', ''),
                    'username' => (string) EInvoiceSettings::get('e_invoice.parasut_username', ''),
                    'password' => (string) EInvoiceSettings::get('e_invoice.parasut_password', ''),
                    'redirect_uri' => 'urn:ietf:wg:oauth:2.0:oob',
                ]);

                if ($response->successful()) {
                    return (string) ($response->json()['access_token'] ?? '') ?: null;
                }

                Log::error('parasut.token_failed', ['body' => $response->body()]);
            } catch (\Throwable $e) {
                Log::error('parasut.token_exception', ['error' => $e->getMessage()]);
            }

            return null;
        });
    }

    private function client()
    {
        return Http::baseUrl(self::BASE.'/'.$this->companyId())
            ->withToken((string) $this->token())
            ->acceptJson()
            ->timeout(40);
    }

    public function issue(Invoice $invoice): EInvoiceResult
    {
        if (! $this->isConfigured()) {
            return EInvoiceResult::failure('Paraşüt API kimlik bilgileri eksik.');
        }
        if ($this->token() === null) {
            return EInvoiceResult::failure('Paraşüt yetkilendirme (token) alınamadı. Kimlik bilgilerini kontrol edin.');
        }

        try {
            $contactId = $this->ensureContact($invoice);
            if ($contactId === null) {
                return EInvoiceResult::failure('Paraşüt müşteri kaydı oluşturulamadı.');
            }

            $payload = $this->salesInvoicePayload($invoice, $contactId);
            $response = $this->client()->post('/sales_invoices', $payload);
        } catch (\Throwable $e) {
            Log::error('parasut.issue_exception', ['invoice' => $invoice->id, 'error' => $e->getMessage()]);

            return EInvoiceResult::failure('Paraşüt bağlantı hatası: '.$e->getMessage());
        }

        if (! $response->successful()) {
            return EInvoiceResult::failure('Paraşüt reddetti: '.$response->body(), ['body' => $response->body()]);
        }

        $data = $response->json() ?? [];
        $invoiceId = $data['data']['id'] ?? null;

        return new EInvoiceResult(
            ok: true,
            message: 'Fatura Paraşüt üzerinde oluşturuldu. E-belge dönüşümünü Paraşüt panelinden veya otomatik kuyruktan kontrol edin.',
            providerInvoiceId: $invoiceId !== null ? (string) $invoiceId : null,
            status: Invoice::STATUS_ISSUED,
            raw: is_array($data) ? $data : [],
        );
    }

    private function ensureContact(Invoice $invoice): ?string
    {
        $payload = [
            'data' => [
                'type' => 'contacts',
                'attributes' => [
                    'name' => $invoice->customer_name ?: ($invoice->customer_email ?: 'Müşteri'),
                    'email' => $invoice->customer_email,
                    'contact_type' => 'person',
                    'tax_number' => $invoice->customer_tax_number,
                    'tax_office' => $invoice->customer_tax_office,
                    'account_type' => 'customer',
                ],
            ],
        ];

        $response = $this->client()->post('/contacts', $payload);
        if ($response->successful()) {
            return (string) ($response->json()['data']['id'] ?? '') ?: null;
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function salesInvoicePayload(Invoice $invoice, string $contactId): array
    {
        $details = [];
        foreach ($this->normalizedLines($invoice) as $l) {
            $details[] = [
                'type' => 'sales_invoice_details',
                'attributes' => [
                    'quantity' => $l['quantity'],
                    'unit_price' => $l['unit_price'],
                    'vat_rate' => $l['tax_rate'],
                    'description' => $l['name'],
                ],
            ];
        }

        return [
            'data' => [
                'type' => 'sales_invoices',
                'attributes' => [
                    'item_type' => 'invoice',
                    'description' => 'HostVim '.($invoice->order?->order_number ?? $invoice->invoice_number),
                    'issue_date' => now()->format('Y-m-d'),
                    'currency' => $invoice->currency,
                ],
                'relationships' => [
                    'contact' => ['data' => ['type' => 'contacts', 'id' => $contactId]],
                    'details' => ['data' => $details],
                ],
            ],
        ];
    }
}
