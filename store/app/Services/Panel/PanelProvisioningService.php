<?php

namespace App\Services\Panel;

use App\Jobs\FulfillPanelOrderJob;
use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Throwable;

class PanelProvisioningService
{
    public function __construct(
        private PanelzeApiService $api,
    ) {}

    public function dispatchIfNeeded(Order $order): void
    {
        $order = $order->fresh();

        if (! $this->shouldQueue($order)) {
            return;
        }

        FulfillPanelOrderJob::dispatch($order->id);
    }

    public function process(Order $order): void
    {
        $order = $order->fresh(['items.product', 'paymentMethod']);

        if ($order->payment_status !== 'paid') {
            return;
        }

        if ($order->panel_provision_status === 'completed') {
            return;
        }

        $cloudOnly = app(\App\Services\Cloud\CloudProvisioningService::class)->orderHasCloudItems($order)
            && ! $this->orderHasPanelItems($order);

        if ($cloudOnly) {
            $order->update([
                'panel_provision_status' => 'skipped',
                'panel_provision_error' => 'Yalnızca bulut VPS — panel kurulumu gerekmez.',
            ]);

            return;
        }

        if (! $this->claimForProcessing($order)) {
            return;
        }

        if (! $this->api->isConfigured()) {
            $this->markSkipped($order, 'Panelze API yapılandırılmamış.');

            return;
        }

        try {
            $items = $this->buildFulfillItems($order);
        } catch (InvalidArgumentException $e) {
            $this->markFailed($order, $e->getMessage());

            return;
        }

        try {
            $result = $this->api->fulfill([
                'store_order_number' => $order->order_number,
                'customer' => [
                    'name' => $order->customer_name,
                    'email' => $order->customer_email,
                    'phone' => $order->customer_phone,
                    'locale' => app()->getLocale(),
                ],
                'items' => $items,
                'payment' => [
                    'method' => $order->paymentMethod?->code ?? 'store',
                    'reference' => $order->payment_reference,
                ],
            ]);

            if (! $this->isValidFulfillResponse($result)) {
                $recovered = $this->tryRecoverFromPanelStatus($order);
                if ($recovered) {
                    return;
                }

                throw new InvalidArgumentException('Panel yanıtı geçersiz veya eksik.');
            }

            $order->update([
                'panel_order_id' => (int) $result['panel_order_id'],
                'panel_order_number' => (string) $result['panel_order_number'],
                'panel_provision_status' => 'completed',
                'panel_provision_error' => null,
                'panel_provisioned_at' => now(),
                'status' => 'completed',
            ]);

            if (! empty($result['panel_user_id']) && $order->user_id) {
                app(PanelCustomerService::class)->assignPanelUserId(
                    $order->user,
                    (int) $result['panel_user_id'],
                );
            }

            $this->sendOrderConfirmation($order->fresh(), $result);
        } catch (Throwable $e) {
            if ($this->tryRecoverFromPanelStatus($order)) {
                return;
            }

            report($e);
            Log::error('Panelze provision hatası', [
                'order' => $order->order_number,
                'message' => $e->getMessage(),
            ]);

            $this->markFailed($order, 'Panel kurulumu başarısız. Lütfen daha sonra tekrar deneyin veya destek ile iletişime geçin.');
        }
    }

    public function retry(Order $order): void
    {
        if ($order->payment_status !== 'paid' || $order->panel_provision_status === 'completed') {
            return;
        }

        if ($order->panel_provision_status === 'processing') {
            return;
        }

        $order->update([
            'panel_provision_status' => 'pending',
            'panel_provision_error' => null,
        ]);

        $this->dispatchIfNeeded($order->fresh());
    }

    private function shouldQueue(Order $order): bool
    {
        if ($order->payment_status !== 'paid') {
            return false;
        }

        if (in_array($order->panel_provision_status, ['completed', 'skipped'], true)) {
            return false;
        }

        return in_array($order->panel_provision_status, ['pending', 'failed'], true);
    }

    private function claimForProcessing(Order $order): bool
    {
        return Cache::lock('panel-provision:'.$order->id, 120)->get(function () use ($order): bool {
            $claimed = Order::query()
                ->whereKey($order->id)
                ->where('payment_status', 'paid')
                ->whereIn('panel_provision_status', ['pending', 'failed'])
                ->update([
                    'panel_provision_status' => 'processing',
                    'panel_provision_error' => null,
                ]);

            return $claimed > 0;
        }) ?? false;
    }

    /**
     * @return list<array<string, mixed>>
     *
     * @throws InvalidArgumentException
     */
    private function buildFulfillItems(Order $order): array
    {
        if ($order->items->isEmpty()) {
            throw new InvalidArgumentException('Sipariş kalemi bulunamadı.');
        }

        $items = [];
        $missingMapping = [];

        foreach ($order->items as $line) {
            $type = (string) ($line->item_type ?? 'hosting');

            if ($type === 'domain_register') {
                $domain = trim((string) ($line->domain_name ?? ''));
                if ($domain === '') {
                    throw new InvalidArgumentException('Domain sipariş kaleminde alan adı eksik.');
                }

                $items[] = [
                    'item_type' => 'domain_register',
                    'domain' => strtolower($domain),
                    'domain_years' => max(1, min(10, (int) ($line->domain_years ?? 1))),
                    'unit_price' => (float) $line->unit_price,
                    'registrar_api' => $line->config_meta['registrar_api'] ?? null,
                ];

                continue;
            }

            if ($type === 'cloud' || $line->product?->isCloudProvision()) {
                continue;
            }

            if ($type === 'manual' || $line->product?->isManualProvision()) {
                $cycle = $line->billing_cycle === 'yearly' ? 'yearly' : 'monthly';
                $quantity = max(1, min(99, (int) $line->quantity));

                for ($i = 0; $i < $quantity; $i++) {
                    $items[] = [
                        'item_type' => 'manual',
                        'product_name' => $line->product_name,
                        'billing_cycle' => $cycle,
                        'unit_price' => (float) $line->unit_price,
                    ];
                }

                continue;
            }

            $packageId = $line->product?->panel_package_id;

            if (! $packageId) {
                $missingMapping[] = $line->product_name;

                continue;
            }

            if ($line->billing_cycle === 'onetime') {
                throw new InvalidArgumentException(
                    '"'.$line->product_name.'" tek seferlik ödeme ile panelde otomatik kurulamaz. Yıllık/aylık paket seçin.'
                );
            }

            $cycle = \App\Support\BillingCycle::panelCycle((string) $line->billing_cycle);
            $quantity = max(1, min(99, (int) $line->quantity));
            $serviceDomain = trim((string) ($line->service_domain ?? ''));

            for ($i = 0; $i < $quantity; $i++) {
                $item = [
                    'item_type' => 'hosting',
                    'package_id' => (int) $packageId,
                    'billing_cycle' => $cycle,
                ];

                if ($serviceDomain !== '') {
                    $item['domain'] = strtolower($serviceDomain);
                }

                $items[] = $item;
            }
        }

        if ($missingMapping !== []) {
            throw new InvalidArgumentException(
                'Panel paket eşlemesi eksik: '.implode(', ', $missingMapping)
            );
        }

        if ($items === []) {
            throw new InvalidArgumentException('Kurulacak hizmet bulunamadı.');
        }

        if (count($items) > 20) {
            throw new InvalidArgumentException('Tek siparişte en fazla 20 hizmet kalemi gönderilebilir.');
        }

        return $items;
    }

    private function orderHasPanelItems(Order $order): bool
    {
        foreach ($order->items as $line) {
            $type = (string) ($line->item_type ?? 'hosting');
            if ($type === 'domain_register') {
                return true;
            }
            if ($type === 'cloud' || $line->product?->isCloudProvision()) {
                continue;
            }
            if ($type === 'manual' || $line->product?->isManualProvision()) {
                return true;
            }
            if ($line->product?->panel_package_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function isValidFulfillResponse(array $result): bool
    {
        return ($result['ok'] ?? false) === true
            && ! empty($result['panel_order_id'])
            && ! empty($result['panel_order_number']);
    }

    private function tryRecoverFromPanelStatus(Order $order): bool
    {
        try {
            $status = $this->api->fulfillStatus($order->order_number);
        } catch (Throwable) {
            return false;
        }

        if (($status['found'] ?? false) !== true) {
            return false;
        }

        $order->update([
            'panel_order_id' => (int) ($status['panel_order_id'] ?? 0) ?: null,
            'panel_order_number' => $status['panel_order_number'] ?? null,
            'panel_provision_status' => 'completed',
            'panel_provision_error' => null,
            'panel_provisioned_at' => now(),
            'status' => 'completed',
        ]);

        $this->sendOrderConfirmation($order->fresh(), $status);

        return true;
    }

    private function markSkipped(Order $order, string $message): void
    {
        $order->update([
            'panel_provision_status' => 'skipped',
            'panel_provision_error' => $message,
        ]);
    }

    private function markFailed(Order $order, string $message): void
    {
        $order->update([
            'panel_provision_status' => 'failed',
            'panel_provision_error' => $message,
            'status' => 'processing',
        ]);

        $this->sendProvisionDelayedEmail($order);
    }

    /**
     * Hosting kurulumu otomatik tamamlanamadiginda musteriye bilgilendirme gonderir.
     * (Admin'e bildirim OrderObserver -> AdminNotificationService uzerinden ayrica gider.)
     */
    private function sendProvisionDelayedEmail(Order $order): void
    {
        if (empty($order->customer_email)) {
            return;
        }

        $body = '<p>Sayın '.e($order->customer_name).',</p>'
            .'<p><strong>'.e($order->order_number).'</strong> numaralı siparişiniz alındı ve ödemeniz onaylandı. '
            .'Hosting hesabınızın kurulumu otomatik olarak tamamlanamadı; ekibimiz bilgilendirildi ve kurulumu en kısa sürede tamamlayacaktır.</p>'
            .'<p>Herhangi bir işlem yapmanıza gerek yoktur. Hesabınız hazır olduğunda giriş bilgileriniz e-posta ile iletilecektir.</p>';

        try {
            $template = EmailTemplate::query()->where('slug', 'order-confirmation')->where('is_active', true)->first();
            $subject = 'Siparişiniz işleniyor — '.$order->order_number;
            if ($template !== null) {
                Mail::to($order->customer_email)->queue(new TemplatedMail($subject, $body));

                return;
            }

            Mail::raw(strip_tags(str_replace(['</p>', '<br>'], ["\n\n", "\n"], $body)), function ($message) use ($order, $subject): void {
                $message->to($order->customer_email)->subject($subject);
            });
        } catch (Throwable $e) {
            Log::warning('panel.delayed_email_failed', ['order' => $order->order_number, 'error' => $e->getMessage()]);
        }
    }

    /**
     * @param  array<string, mixed>  $panelResult
     */
    private function sendOrderConfirmation(Order $order, array $panelResult): void
    {
        $template = EmailTemplate::query()
            ->where('slug', 'order-confirmation')
            ->where('is_active', true)
            ->first();

        if ($template === null) {
            return;
        }

        $panelLogin = (string) ($panelResult['panel_login_url'] ?? config('panelze.panel_login_url', ''));
        $tempPassword = (string) ($panelResult['temporary_password'] ?? '');
        $needsPasswordSetup = (bool) ($panelResult['needs_password_setup'] ?? false);

        if ($tempPassword !== '') {
            $passwordLine = '<p>Geçici şifreniz: <strong>'.e($tempPassword).'</strong> (ilk girişte değiştirmeniz istenecektir).</p>';
        } elseif ($needsPasswordSetup) {
            $resetUrl = e($panelLogin.'/login');
            $passwordLine = '<p>Panel şifrenizi belirlemek için <a href="'.$resetUrl.'">giriş sayfasından</a> “Şifremi unuttum” bağlantısını kullanın.</p>';
        } else {
            $passwordLine = '';
        }

        $replacements = [
            'customer_name' => $order->customer_name,
            'order_number' => $order->order_number,
            'total' => number_format((float) $order->total, 2, ',', '.'),
            'panel_login_url' => $panelLogin,
            'temporary_password_line' => $passwordLine,
            'panel_order_number' => (string) ($panelResult['panel_order_number'] ?? ''),
        ];

        $subject = $this->render($template->subject, $replacements);
        $body = $this->render($template->body, $replacements);

        Mail::to($order->customer_email)->queue(new TemplatedMail($subject, $body));
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function render(string $text, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $text = str_replace('{'.$key.'}', $value, $text);
        }

        return $text;
    }
}
