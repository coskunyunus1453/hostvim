<?php

namespace App\Services\Invoice;

use App\Models\Invoice;
use App\Models\Order;
use App\Services\EInvoice\EInvoiceResolver;
use App\Services\EInvoice\EInvoiceSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    public function __construct(private EInvoiceResolver $resolver) {}

    /**
     * Sipariş için (yoksa) taslak fatura oluşturur ve proforma PDF üretir.
     * Idempotent: fatura zaten varsa onu döndürür.
     */
    public function createForOrder(Order $order): Invoice
    {
        $existing = Invoice::query()->where('order_id', $order->id)->first();
        if ($existing !== null) {
            return $existing;
        }

        $order->loadMissing(['items', 'user']);

        $total = (float) $order->total;
        // Fatura her zaman müşterinin ÖDEDİĞİ tutara (order.total) eşit olmalı.
        if ((float) $order->tax_rate > 0) {
            // Yeni siparişler: ödeme anında hesaplanan KDV kırılımını kullan (çifte KDV'yi önler).
            $rate = (float) $order->tax_rate;
            $taxTotal = round((float) $order->tax_amount, 2);
            $subtotal = round($total - $taxTotal, 2);
            $invoiceTotal = $total;
        } else {
            // Geriye dönük (KDV kırılımı olmayan eski siparişler): ayardan hesapla.
            $rate = EInvoiceSettings::taxRate();
            $includesTax = EInvoiceSettings::priceIncludesTax();
            $subtotal = $includesTax ? round($total / (1 + $rate / 100), 2) : $total;
            $taxTotal = $includesTax ? round($total - $subtotal, 2) : round($total * $rate / 100, 2);
            $invoiceTotal = $includesTax ? $total : round($subtotal + $taxTotal, 2);
        }

        $customer = $this->resolveCustomer($order);
        $type = $this->resolveType($customer['tax_number']);

        $invoice = Invoice::create([
            'order_id' => $order->id,
            'invoice_number' => $this->nextNumber(),
            'type' => $type,
            'status' => Invoice::STATUS_DRAFT,
            'provider' => EInvoiceSettings::isEnabled() ? EInvoiceSettings::provider() : null,
            'customer_name' => $customer['name'],
            'customer_email' => $customer['email'],
            'customer_tax_office' => $customer['tax_office'],
            'customer_tax_number' => $customer['tax_number'],
            'customer_address' => $customer['address'],
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => $invoiceTotal,
            'tax_rate' => $rate,
            'currency' => $order->currency ?: 'TRY',
            'meta' => ['lines' => $this->buildLines($order, $rate)],
        ]);

        $this->renderPdf($invoice);

        return $invoice->fresh();
    }

    /**
     * Faturayı entegratöre gönderip resmi e-fatura/e-arşiv olarak keser.
     */
    public function issue(Invoice $invoice): Invoice
    {
        $provider = $this->resolver->active();
        if ($provider === null) {
            $invoice->update(['status' => Invoice::STATUS_ERROR, 'error_message' => 'E-fatura sağlayıcısı seçili değil (Ayarlar → E-Fatura).']);

            return $invoice->fresh();
        }
        if (! $provider->isConfigured()) {
            $invoice->update(['status' => Invoice::STATUS_ERROR, 'error_message' => $provider->label().' API kimlik bilgileri eksik.']);

            return $invoice->fresh();
        }
        if ($invoice->isIssued()) {
            return $invoice;
        }

        $invoice->loadMissing('order');
        $result = $provider->issue($invoice);

        if (! $result->ok) {
            $invoice->update([
                'status' => Invoice::STATUS_ERROR,
                'error_message' => $result->message,
                'meta' => array_merge($invoice->meta ?? [], ['last_provider_response' => $result->raw]),
            ]);

            return $invoice->fresh();
        }

        $invoice->update([
            'status' => $result->status ?: Invoice::STATUS_ISSUED,
            'provider' => $provider->key(),
            'provider_uuid' => $result->uuid,
            'provider_invoice_id' => $result->providerInvoiceId,
            'issued_at' => now(),
            'error_message' => null,
            'meta' => array_merge($invoice->meta ?? [], ['last_provider_response' => $result->raw]),
        ]);

        // Resmi PDF'i indirip taslağın yerine koy.
        $this->syncOfficialPdf($invoice->fresh(), $provider);

        return $invoice->fresh();
    }

    public function refreshStatus(Invoice $invoice): Invoice
    {
        $provider = $this->resolver->active();
        if ($provider === null) {
            return $invoice;
        }

        $result = $provider->refreshStatus($invoice);
        if ($result->ok && $result->status) {
            $invoice->update(['status' => $result->status]);
            $this->syncOfficialPdf($invoice->fresh(), $provider);
        }

        return $invoice->fresh();
    }

    /** Faturanın PDF içeriğini döndürür (gerçek e-fatura PDF varsa onu, yoksa taslağı). */
    public function pdfContents(Invoice $invoice): string
    {
        if ($invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path)) {
            return Storage::disk('local')->get($invoice->pdf_path);
        }

        return $this->renderPdf($invoice);
    }

    /** Taslak/proforma PDF üretir, kaydeder ve içeriğini döndürür. */
    public function renderPdf(Invoice $invoice): string
    {
        $invoice->loadMissing('order');

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'company' => EInvoiceSettings::company(),
            'lines' => $invoice->lines(),
        ])->setPaper('a4');

        $content = $pdf->output();
        $path = 'invoices/'.$invoice->invoice_number.'.pdf';
        Storage::disk('local')->put($path, $content);

        if ($invoice->pdf_path !== $path) {
            $invoice->forceFill(['pdf_path' => $path])->save();
        }

        return $content;
    }

    private function syncOfficialPdf(Invoice $invoice, $provider): void
    {
        try {
            $pdf = $provider->downloadPdf($invoice);
            if ($pdf !== null && $pdf !== '') {
                $path = 'invoices/'.$invoice->invoice_number.'-official.pdf';
                Storage::disk('local')->put($path, $pdf);
                $invoice->forceFill(['pdf_path' => $path])->save();
            }
        } catch (\Throwable $e) {
            Log::warning('invoice.official_pdf_failed', ['invoice' => $invoice->id, 'error' => $e->getMessage()]);
        }
    }

    private function resolveType(?string $taxNumber): string
    {
        $provider = $this->resolver->active();
        if ($provider !== null && $taxNumber) {
            $isEInvoice = $provider->isEInvoiceUser($taxNumber);
            if ($isEInvoice === true) {
                return Invoice::TYPE_EINVOICE;
            }
        }

        return Invoice::TYPE_EARCHIVE;
    }

    /** @return array{name: string, email: string, tax_office: ?string, tax_number: ?string, address: ?string} */
    private function resolveCustomer(Order $order): array
    {
        $user = $order->user;

        return [
            'name' => $user?->billing_company ?: ($order->customer_company ?: ($user?->name ?: $order->customer_name)),
            'email' => $order->customer_email ?: (string) $user?->email,
            'tax_office' => $user?->tax_office,
            'tax_number' => $user?->tax_number,
            'address' => $user?->billing_address ?: $order->customer_address,
        ];
    }

    /** @return list<array{name: string, quantity: int, unit_price: float, total: float, tax_rate: float}> */
    private function buildLines(Order $order, float $rate): array
    {
        $lines = [];
        foreach ($order->items as $item) {
            $name = $item->product_name ?: 'Hizmet';
            if ($item->domain_name) {
                $name = trim($name.' — '.$item->domain_name.($item->domain_years ? ' ('.$item->domain_years.' yıl)' : ''));
            }

            $lines[] = [
                'name' => $name,
                'quantity' => max(1, (int) $item->quantity),
                'unit_price' => (float) $item->unit_price,
                'total' => (float) $item->total,
                'tax_rate' => $rate,
            ];
        }

        if ($lines === []) {
            $lines[] = [
                'name' => 'Sipariş '.$order->order_number,
                'quantity' => 1,
                'unit_price' => (float) $order->total,
                'total' => (float) $order->total,
                'tax_rate' => $rate,
            ];
        }

        return $lines;
    }

    private function nextNumber(): string
    {
        $prefix = 'HV-'.now()->year.'-';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $last = Invoice::query()
                ->where('invoice_number', 'like', $prefix.'%')
                ->orderByDesc('id')
                ->value('invoice_number');

            $seq = $last ? ((int) substr((string) $last, strlen($prefix))) + 1 : 1;
            $candidate = $prefix.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);

            if (! Invoice::query()->where('invoice_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        return $prefix.now()->format('His').random_int(10, 99);
    }
}
