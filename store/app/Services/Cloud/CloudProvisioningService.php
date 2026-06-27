<?php

namespace App\Services\Cloud;

use App\Jobs\FulfillCloudOrderJob;
use App\Mail\TemplatedMail;
use App\Models\CloudProvider;
use App\Models\CloudServer;
use App\Models\EmailTemplate;
use App\Models\Order;
use App\Models\Product;
use App\Services\AdminNotificationService;
use App\Services\Cloud\Provider\CloudProviderResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class CloudProvisioningService
{
    public function __construct(
        private CloudSettings $settings,
        private CloudProviderResolver $providers,
        private AdminNotificationService $notifications,
    ) {}

    public function dispatchIfNeeded(Order $order): void
    {
        $order = $order->fresh(['items.product']);

        if (! $this->shouldQueue($order)) {
            return;
        }

        FulfillCloudOrderJob::dispatch($order->id);
    }

    public function process(Order $order): void
    {
        $order = $order->fresh(['items.product']);

        if ($order->payment_status !== 'paid') {
            return;
        }

        if ($order->cloud_provision_status === 'completed') {
            return;
        }

        if (! $this->claimForProcessing($order)) {
            return;
        }

        if (! $this->settings->provisionEnabled()) {
            $this->markSkipped($order, 'Bulut otomatik kurulum kapalı.');

            return;
        }

        $cloudItems = $this->cloudOrderItems($order);
        if ($cloudItems === []) {
            $this->markSkipped($order, 'Bulut kurulum kalemi yok.');

            return;
        }

        $errors = [];
        $provisioned = 0;

        foreach ($cloudItems as $line) {
            $product = $line->product;
            if (! $product instanceof Product) {
                $errors[] = 'Ürün bulunamadı: '.$line->product_name;

                continue;
            }

            $quantity = max(1, min(10, (int) $line->quantity));

            // Idempotency: bu kalem icin saglayicida zaten olusturulmus (external_id'li,
            // basarisiz olmayan) sunuculari say. Retry'da bunlar TEKRAR olusturulmaz —
            // boylece ozellikle Contabo gibi ucretli saglayicilarda mukerrer sunucu/fatura onlenir.
            $alreadyProvisioned = CloudServer::query()
                ->where('order_item_id', $line->id)
                ->whereNotNull('external_id')
                ->where('status', '!=', CloudServer::STATUS_FAILED)
                ->count();
            $provisioned += $alreadyProvisioned;

            for ($i = $alreadyProvisioned; $i < $quantity; $i++) {
                try {
                    $this->provisionOne($order, $line, $product, $i);
                    $provisioned++;
                } catch (Throwable $e) {
                    report($e);
                    $errors[] = $product->name.': '.$e->getMessage();
                    Log::error('cloud.provision_failed', [
                        'order' => $order->order_number,
                        'product' => $product->slug,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }

        if ($provisioned > 0 && $errors === []) {
            $order->update([
                'cloud_provision_status' => 'completed',
                'cloud_provision_error' => null,
                'cloud_provisioned_at' => now(),
                'status' => 'completed',
            ]);
            $this->sendServerCredentialsEmail($order->fresh(['cloudServers']));

            return;
        }

        if ($provisioned > 0) {
            $order->update([
                'cloud_provision_status' => 'completed',
                'cloud_provision_error' => implode('; ', $errors),
                'cloud_provisioned_at' => now(),
            ]);
            $this->sendServerCredentialsEmail($order->fresh(['cloudServers']));

            return;
        }

        $this->markFailed($order, $errors !== [] ? implode('; ', $errors) : 'Sunucu kurulamadı.');
    }

    public function orderHasCloudItems(Order $order): bool
    {
        return $this->cloudOrderItems($order) !== [];
    }

    /**
     * Saglayicidaki tum sunuculari cekip cloud_servers tablosuna senkronlar (import + guncelle).
     *
     * @return array{imported: int, updated: int, total: int}
     */
    public function syncServers(\App\Models\CloudProvider $account): array
    {
        $servers = $this->providers->driver($account->api_name)->listServers($account);
        $imported = 0;
        $updated = 0;

        foreach ($servers as $remote) {
            $externalId = (string) ($remote['external_id'] ?? '');
            if ($externalId === '') {
                continue;
            }

            $row = CloudServer::query()
                ->where('provider_api', $account->api_name)
                ->where('external_id', $externalId)
                ->first();

            $status = $this->mapRemoteStatus((string) ($remote['status'] ?? ''));
            $attributes = [
                'hostname' => (string) ($remote['hostname'] ?? $externalId),
                'region' => $remote['region'] ?? null,
                'plan' => $remote['plan'] ?? null,
                'image' => $remote['image'] ?? null,
                'ipv4' => $remote['ipv4'] ?? null,
                'ipv6' => $remote['ipv6'] ?? null,
                'status' => $status,
            ];

            if ($row === null) {
                CloudServer::create(array_merge($attributes, [
                    'provider_api' => $account->api_name,
                    'external_id' => $externalId,
                    'meta' => ['provider' => $account->api_name, 'source' => 'sync'],
                ]));
                $imported++;
            } else {
                // Senkronda IP/durum guncelle; mevcut root_password/order baglarini koru.
                $row->update(array_filter($attributes, fn ($v) => $v !== null) + ['status' => $status]);
                $updated++;
            }
        }

        return ['imported' => $imported, 'updated' => $updated, 'total' => count($servers)];
    }

    private function mapRemoteStatus(string $remote): string
    {
        $remote = strtolower($remote);

        return match (true) {
            in_array($remote, ['running', 'active', 'ok'], true) => CloudServer::STATUS_ACTIVE,
            in_array($remote, ['off', 'stopped', 'shutoff'], true) => CloudServer::STATUS_ACTIVE,
            in_array($remote, ['provisioning', 'new', 'pending', 'booting', 'initializing', 'installingbooting'], true) => CloudServer::STATUS_PROVISIONING,
            default => CloudServer::STATUS_PROVISIONING,
        };
    }

    private function provisionOne(Order $order, $line, Product $product, int $index): CloudServer
    {
        $apiName = (string) ($product->cloud_provider_api ?? '');
        if ($apiName === '') {
            throw new InvalidArgumentException('Üründe bulut API seçilmemiş: '.$product->name);
        }

        $account = $this->providers->account($apiName);
        if ($account === null) {
            throw new InvalidArgumentException($this->providers->providerLabel($apiName).' API yapılandırılmamış veya kapalı.');
        }

        $region = (string) ($product->cloud_region ?? '');
        $plan = (string) ($product->cloud_plan ?? '');
        $image = (string) ($product->cloud_image ?? '');

        if ($region === '' || $plan === '' || $image === '') {
            throw new InvalidArgumentException('Üründe bölge/plan/image eksik: '.$product->name);
        }

        $hostname = $this->buildHostname($order, $product, $index);
        $labels = [
            'hostvim-order' => $order->order_number,
            'hostvim-product' => $product->slug,
        ];

        $serverRow = CloudServer::create([
            'order_id' => $order->id,
            'order_item_id' => $line->id,
            'provider_api' => $apiName,
            'hostname' => $hostname,
            'region' => $region,
            'plan' => $plan,
            'image' => $image,
            'status' => CloudServer::STATUS_PROVISIONING,
        ]);

        $driver = $this->providers->driver($apiName);

        $createConfig = [
            'hostname' => $hostname,
            'region' => $region,
            'plan' => $plan,
            'image' => $image,
            'labels' => $labels,
            'root_password' => bin2hex(random_bytes(10)).'Aa1!',
        ];

        // Panelze paneli VPS'e VARSAYILAN kurulmaz. Yalnizca musteri satin alma
        // sirasinda "panel dahil" sectiyse (config_meta.install_panel) cloud-init ile kurulur.
        if ($this->wantsPanel($line)) {
            $userData = $this->buildCloudInit($order, $product, $hostname);
            if ($userData !== null) {
                $createConfig['user_data'] = $userData;
                $serverRow->update(['meta' => array_merge($serverRow->meta ?? [], ['panel_install' => 'queued'])]);
            }
        }

        try {
            $result = $driver->createServer($account, $createConfig);

            $externalId = (string) ($result['external_id'] ?? '');
            $serverRow->update([
                'external_id' => $externalId ?: null,
                'ipv4' => $result['ipv4'] ?? null,
                'ipv6' => $result['ipv6'] ?? null,
                'root_password' => $result['root_password'] ?? null,
                'status' => CloudServer::STATUS_PROVISIONING,
                'meta' => array_merge($serverRow->meta ?? [], $result['meta'] ?? []),
            ]);

            // Sunucu hazir (IP + aktif) olana kadar polling yap.
            if ($externalId !== '') {
                $polled = $this->pollUntilReady($driver, $account, $externalId);
                if ($polled !== null) {
                    $serverRow->update([
                        'ipv4' => $polled['ipv4'] ?? $serverRow->ipv4,
                        'ipv6' => $polled['ipv6'] ?? $serverRow->ipv6,
                        'meta' => array_merge($serverRow->meta ?? [], $polled['meta'] ?? []),
                    ]);
                }
            }
        } catch (Throwable $e) {
            // Sunucu olusturma/polling basarisiz: satiri FAILED isaretle ki admin panelinde
            // "Kuruluyor"da sonsuza dek takili kalmasin ve hata nedeni gorunsun.
            $serverRow->refresh();
            $serverRow->update([
                'status' => CloudServer::STATUS_FAILED,
                'provision_error' => Str::limit($e->getMessage(), 480),
            ]);
            $this->notifications->fromCloudServerFailed($serverRow->fresh());

            throw $e;
        }

        $serverRow->refresh();
        $serverRow->update([
            'status' => $serverRow->ipv4 ? CloudServer::STATUS_ACTIVE : CloudServer::STATUS_PROVISIONING,
            'provisioned_at' => now(),
        ]);

        return $serverRow->fresh();
    }

    /**
     * createServer sonrasi sunucunun IP alip aktif olmasini bekler.
     *
     * @return array{ipv4: ?string, ipv6: ?string, status: string, meta?: array<string,mixed>}|null
     */
    private function pollUntilReady($driver, CloudProvider $account, string $externalId): ?array
    {
        $maxAttempts = $this->settings->pollMaxAttempts();
        $interval = $this->settings->pollIntervalSeconds();
        $last = null;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            try {
                $state = $driver->getServer($account, $externalId);
            } catch (Throwable $e) {
                Log::warning('cloud.poll_failed', ['external_id' => $externalId, 'error' => $e->getMessage()]);
                $state = null;
            }

            if (is_array($state)) {
                $last = $state;
                if (! empty($state['ready'])) {
                    return $state;
                }
            }

            sleep($interval);
        }

        return $last;
    }

    /**
     * Siparis kaleminde "Panelze paneli dahil" secilmis mi?
     */
    private function wantsPanel($line): bool
    {
        $meta = is_array($line->config_meta ?? null) ? $line->config_meta : [];

        return (bool) ($meta['install_panel'] ?? false);
    }

    /**
     * VPS'te cloud-init ile calistirilacak Panelze kurulum betigini uretir.
     * Kurulum URL'i tanimliysa script doner; aksi halde null.
     */
    private function buildCloudInit(Order $order, Product $product, string $hostname): ?string
    {
        $installUrl = $this->settings->remoteInstallUrl();
        if ($installUrl === '' || ! preg_match('#^https?://#', $installUrl)) {
            Log::warning('cloud.cloudinit.skipped', ['reason' => 'install_url_missing', 'order' => $order->order_number]);

            return null;
        }

        $safeUrl = escapeshellarg($installUrl);
        $safeHost = escapeshellarg($hostname);

        return <<<CLOUDINIT
#!/bin/bash
set -e
exec > /var/log/panelze-install.log 2>&1
hostnamectl set-hostname {$safeHost} || true
export DEBIAN_FRONTEND=noninteractive
export PANELZE_AUTO_INSTALL=1
curl -fsSL {$safeUrl} | bash
CLOUDINIT;
    }

    private function buildHostname(Order $order, Product $product, int $index): string
    {
        $prefix = (string) config('cloud_providers.hostname_prefix', 'hv');
        $slug = Str::slug($product->slug) ?: 'vps';
        $suffix = strtolower(substr($order->order_number, -6));
        $host = $prefix.'-'.$slug.'-'.$suffix;
        if ($index > 0) {
            $host .= '-'.($index + 1);
        }

        return substr($host, 0, 63);
    }

    /** @return list<\App\Models\OrderItem> */
    private function cloudOrderItems(Order $order): array
    {
        $items = [];
        foreach ($order->items as $line) {
            if ($line->product?->isCloudProvision()) {
                $items[] = $line;
            }
        }

        return $items;
    }

    private function shouldQueue(Order $order): bool
    {
        if ($order->payment_status !== 'paid') {
            return false;
        }

        if (! $this->orderHasCloudItems($order)) {
            return false;
        }

        return in_array($order->cloud_provision_status, ['pending', 'failed'], true);
    }

    private function claimForProcessing(Order $order): bool
    {
        return Cache::lock('cloud-provision:'.$order->id, 180)->get(function () use ($order): bool {
            $claimed = Order::query()
                ->whereKey($order->id)
                ->where('payment_status', 'paid')
                ->whereIn('cloud_provision_status', ['pending', 'failed'])
                ->update([
                    'cloud_provision_status' => 'processing',
                    'cloud_provision_error' => null,
                ]);

            return $claimed > 0;
        }) ?? false;
    }

    private function markSkipped(Order $order, string $message): void
    {
        $order->update([
            'cloud_provision_status' => 'skipped',
            'cloud_provision_error' => $message,
        ]);
    }

    private function markFailed(Order $order, string $message): void
    {
        $order->update([
            'cloud_provision_status' => 'failed',
            'cloud_provision_error' => $message,
            'status' => 'processing',
        ]);

        $this->sendProvisionDelayedEmail($order);
    }

    /**
     * Sunucu kurulumu otomatik tamamlanamadiginda musteriye bilgilendirme gonderir.
     * (Admin'e bildirim OrderObserver -> AdminNotificationService uzerinden ayrica gider.)
     */
    private function sendProvisionDelayedEmail(Order $order): void
    {
        if (empty($order->customer_email)) {
            return;
        }

        $body = '<p>Sayın '.e($order->customer_name).',</p>'
            .'<p><strong>'.e($order->order_number).'</strong> numaralı sunucu siparişiniz alındı ve ödemeniz onaylandı. '
            .'Sunucu kurulumunuz otomatik olarak tamamlanamadı; ekibimiz bilgilendirildi ve kurulumu en kısa sürede tamamlayacaktır.</p>'
            .'<p>Herhangi bir işlem yapmanıza gerek yoktur. Sunucunuz hazır olduğunda erişim bilgileriniz e-posta ile iletilecektir.</p>';

        try {
            $template = EmailTemplate::query()->where('slug', 'order-confirmation')->where('is_active', true)->first();
            if ($template !== null) {
                $subject = 'Siparişiniz işleniyor — '.$order->order_number;
                Mail::to($order->customer_email)->queue(new TemplatedMail($subject, $body));

                return;
            }

            Mail::raw(strip_tags(str_replace(['</p>', '<br>'], ["\n\n", "\n"], $body)), function ($message) use ($order): void {
                $message->to($order->customer_email)->subject('Siparişiniz işleniyor — '.$order->order_number);
            });
        } catch (Throwable $e) {
            Log::warning('cloud.delayed_email_failed', ['order' => $order->order_number, 'error' => $e->getMessage()]);
        }
    }

    private function sendServerCredentialsEmail(Order $order): void
    {
        $servers = $order->cloudServers()->where('status', CloudServer::STATUS_ACTIVE)->get();
        if ($servers->isEmpty()) {
            return;
        }

        // Panel yalnizca panel_install isaretli sunuculara kurulur.
        $anyPanel = $servers->contains(fn (CloudServer $s) => ($s->meta['panel_install'] ?? null) !== null);

        $lines = $servers->map(function (CloudServer $s): string {
            $autoPanel = ($s->meta['panel_install'] ?? null) !== null;
            $provider = $this->providers->providerLabel($s->provider_api);
            $pass = $s->root_password ? '<br>Root şifre: <strong>'.e($s->root_password).'</strong>' : '';
            $ip = $s->ipv4 ?: ($s->ipv6 ?: '—');

            $panelLine = '';
            if ($autoPanel) {
                // Panel musterinin KENDI sunucusuna kurulur; adres sunucunun kendi IP'sidir
                // (merkezi panel.hostvim.com degil).
                $panelTarget = $s->ipv4 ? 'https://'.$s->ipv4 : ($s->ipv6 ? 'https://['.$s->ipv6.']' : '');
                $panelLink = $panelTarget !== '' ? '<a href="'.e($panelTarget).'">'.e($panelTarget).'</a>' : 'sunucu IP adresiniz';
                $panelLine = '<br>Panelze paneli: '.$panelLink.' <em>(kurulum birkaç dakika içinde tamamlanır)</em>';
            }

            return '<li><strong>'.e($s->hostname).'</strong> ('.e($provider).')<br>IP: <code>'.e($ip).'</code>'.$pass.$panelLine.'</li>';
        })->implode('');

        $panelNote = $anyPanel
            ? '<p>Sunucunuza <strong>Panelze</strong> hosting paneli otomatik olarak kuruluyor. Kurulum tamamlandığında panel adresinden giriş yapabilirsiniz (ilk kurulum 10-15 dakika sürebilir).</p>'
            : '<p>Sunucunuza SSH ile bağlanabilirsiniz: <code>ssh root@SUNUCU_IP</code>. Dilerseniz panelden Panelze kurulumunu sonradan başlatabilirsiniz.</p>';

        $body = '<p>Sayın '.e($order->customer_name).',</p>'
            .'<p><strong>'.e($order->order_number).'</strong> siparişiniz için sunucularınız hazır:</p>'
            .'<ul>'.$lines.'</ul>'
            .$panelNote;

        $template = EmailTemplate::query()->where('slug', 'order-confirmation')->where('is_active', true)->first();
        if ($template !== null) {
            $subject = str_replace('{order_number}', $order->order_number, $template->subject);
            Mail::to($order->customer_email)->queue(new TemplatedMail($subject, $body));

            return;
        }

        Mail::raw(strip_tags(str_replace(['<br>', '</li>'], ["\n", "\n"], $body)), function ($message) use ($order): void {
            $message->to($order->customer_email)->subject('Sunucunuz hazır — '.$order->order_number);
        });
    }
}
