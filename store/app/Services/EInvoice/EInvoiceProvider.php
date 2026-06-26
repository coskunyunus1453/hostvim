<?php

namespace App\Services\EInvoice;

use App\Models\Invoice;

/**
 * E-fatura / e-arşiv entegratörü sürücü arayüzü.
 * Yeni bir sağlayıcı eklemek için bu arayüzü uygulayın ve EInvoiceResolver'a kaydedin.
 */
interface EInvoiceProvider
{
    /** Sağlayıcı anahtarı: nilvera | parasut | mukellef */
    public function key(): string;

    /** İnsan-okur ad */
    public function label(): string;

    /** Sağlayıcı yapılandırılmış (API kimlikleri girilmiş) mi? */
    public function isConfigured(): bool;

    /** Faturayı entegratöre gönderip resmi e-fatura/e-arşiv olarak keser. */
    public function issue(Invoice $invoice): EInvoiceResult;

    /** Kesilmiş faturanın güncel durumunu (kabul/red vb.) sorgular. */
    public function refreshStatus(Invoice $invoice): EInvoiceResult;

    /** Resmi PDF içeriğini (binary) indirir; yoksa null. */
    public function downloadPdf(Invoice $invoice): ?string;

    /**
     * Alıcı e-Fatura mükellefi mi? true=e-Fatura, false=e-Arşiv, null=bilinmiyor.
     * Boş VKN/TCKN için null döner.
     */
    public function isEInvoiceUser(?string $taxNumber): ?bool;
}
