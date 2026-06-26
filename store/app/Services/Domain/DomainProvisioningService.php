<?php

namespace App\Services\Domain;

use App\Jobs\FulfillDomainOrderJob;
use App\Mail\TemplatedMail;
use App\Models\DomainName;
use App\Models\EmailTemplate;
use App\Models\Order;
use App\Services\AdminNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Domain siparislerini (item_type=domain_register) odeme sonrasi otomatik olarak
 * saglayicida (Spaceship) register eder ve musteriye baglar.
 */
class DomainProvisioningService
{
    public function __construct(
        private DomainManagementService $management,
        private AdminNotificationService $notifications,
    ) {}

    public function dispatchIfNeeded(Order $order): void
    {
        $order = $order->fresh('items');
        if ($order === null || $order->payment_status !== 'paid') {
            return;
        }
        if (! $this->hasDomainItems($order)) {
            return;
        }
        if ($this->management->preferredApi() === null) {
            // Otomatik kayit destekleyen saglayici yoksa atla (manuel surec).
            return;
        }

        FulfillDomainOrderJob::dispatch($order->id);
    }

    public function process(Order $order): void
    {
        $order = $order->fresh('items');
        if ($order === null || $order->payment_status !== 'paid') {
            return;
        }

        foreach ($this->domainItems($order) as $item) {
            $domain = strtolower(trim((string) ($item->domain_name ?? '')));
            if ($domain === '') {
                continue;
            }

            $existing = DomainName::query()->where('domain', $domain)->first();
            if ($existing !== null && in_array($existing->status, ['registered', 'active', 'registering'], true)) {
                // Zaten kayitli/kayit suruyor — mukerrer islemi onle, sadece musteriye bagla.
                if (! $existing->customer_email) {
                    $existing->update(['customer_email' => $order->customer_email, 'order_id' => $order->id]);
                }

                continue;
            }

            $years = max(1, min(10, (int) ($item->domain_years ?? 1)));
            $apiName = is_array($item->config_meta ?? null) ? ($item->config_meta['registrar_api'] ?? null) : null;

            try {
                $result = $this->management->registerForOrder($order, $domain, $years, $apiName);
                if ($result['ok'] ?? false) {
                    $this->sendCustomerDomainEmail($order, $domain, true);
                } else {
                    $this->notifications->fromDomainProvisionFailed($order, $domain, $result['message'] ?? null);
                    $this->sendCustomerDomainEmail($order, $domain, false);
                }
            } catch (Throwable $e) {
                report($e);
                $this->notifications->fromDomainProvisionFailed($order, $domain, $e->getMessage());
                $this->sendCustomerDomainEmail($order, $domain, false);
            }
        }
    }

    /**
     * Domain kaydi sonucunu musteriye e-posta ile bildirir.
     * Basari: domain aktif + yonetim linki; basarisizlik: "inceleniyor" bilgisi.
     */
    private function sendCustomerDomainEmail(Order $order, string $domain, bool $success): void
    {
        if (empty($order->customer_email)) {
            return;
        }

        if ($success) {
            $manageUrl = rtrim((string) config('app.url'), '/').'/hesap/alan-adlari';
            $subject = 'Alan adınız aktif — '.$domain;
            $body = '<p>Sayın '.e($order->customer_name).',</p>'
                .'<p><strong>'.e($domain).'</strong> alan adınız başarıyla kaydedildi ve hesabınıza tanımlandı.</p>'
                .'<p>Alan adınızı (DNS, yönlendirme, nameserver vb.) hesabınızdan yönetebilirsiniz: '
                .'<a href="'.e($manageUrl).'">'.e($manageUrl).'</a></p>';
        } else {
            $subject = 'Alan adı kaydınız işleniyor — '.$domain;
            $body = '<p>Sayın '.e($order->customer_name).',</p>'
                .'<p><strong>'.e($domain).'</strong> alan adı kaydınız otomatik olarak tamamlanamadı; ekibimiz bilgilendirildi ve '
                .'kaydı en kısa sürede tamamlayacaktır.</p>'
                .'<p>Herhangi bir işlem yapmanıza gerek yoktur. Sonuç hakkında ayrıca bilgilendirileceksiniz.</p>';
        }

        try {
            $template = EmailTemplate::query()->where('slug', 'order-confirmation')->where('is_active', true)->first();
            if ($template !== null) {
                Mail::to($order->customer_email)->queue(new TemplatedMail($subject, $body));

                return;
            }

            Mail::raw(strip_tags(str_replace(['</p>', '<br>'], ["\n\n", "\n"], $body)), function ($message) use ($order, $subject): void {
                $message->to($order->customer_email)->subject($subject);
            });
        } catch (Throwable $e) {
            Log::warning('domain.customer_email_failed', ['domain' => $domain, 'order' => $order->order_number, 'error' => $e->getMessage()]);
        }
    }

    private function hasDomainItems(Order $order): bool
    {
        return $this->domainItems($order) !== [];
    }

    /** @return list<\App\Models\OrderItem> */
    private function domainItems(Order $order): array
    {
        $out = [];
        foreach ($order->items as $item) {
            if (($item->item_type ?? '') === 'domain_register') {
                $out[] = $item;
            }
        }

        return $out;
    }
}
