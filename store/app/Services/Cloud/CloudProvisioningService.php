<?php

namespace App\Services\Cloud;

use App\Jobs\FulfillCloudOrderJob;
use App\Mail\TemplatedMail;
use App\Models\CloudServer;
use App\Models\EmailTemplate;
use App\Models\Order;
use App\Models\Product;
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

            for ($i = 0; $i < $quantity; $i++) {
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

        $result = $this->providers->driver($apiName)->createServer($account, [
            'hostname' => $hostname,
            'region' => $region,
            'plan' => $plan,
            'image' => $image,
            'labels' => $labels,
            'root_password' => bin2hex(random_bytes(10)).'Aa1!',
        ]);

        $serverRow->update([
            'external_id' => $result['external_id'] ?? null,
            'ipv4' => $result['ipv4'] ?? null,
            'ipv6' => $result['ipv6'] ?? null,
            'root_password' => $result['root_password'] ?? null,
            'status' => CloudServer::STATUS_ACTIVE,
            'meta' => $result['meta'] ?? null,
            'provisioned_at' => now(),
        ]);

        return $serverRow->fresh();
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
    }

    private function sendServerCredentialsEmail(Order $order): void
    {
        $servers = $order->cloudServers()->where('status', CloudServer::STATUS_ACTIVE)->get();
        if ($servers->isEmpty()) {
            return;
        }

        $lines = $servers->map(function (CloudServer $s): string {
            $provider = $this->providers->providerLabel($s->provider_api);
            $pass = $s->root_password ? '<br>Root şifre: <strong>'.e($s->root_password).'</strong>' : '';
            $ip = $s->ipv4 ?: ($s->ipv6 ?: '—');

            return '<li><strong>'.e($s->hostname).'</strong> ('.e($provider).')<br>IP: <code>'.e($ip).'</code>'.$pass.'</li>';
        })->implode('');

        $body = '<p>Sayın '.e($order->customer_name).',</p>'
            .'<p><strong>'.e($order->order_number).'</strong> siparişiniz için sunucularınız hazır:</p>'
            .'<ul>'.$lines.'</ul>'
            .'<p>SSH: <code>ssh root@SUNUCU_IP</code></p>';

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
