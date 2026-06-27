<?php

namespace App\Services;

use App\Models\Product;
use App\Services\Domain\DomainAvailabilityService;
use App\Services\Domain\DomainSettings;
use App\Services\Panel\PanelzeApiService;
use App\Support\BillingCycle;
use InvalidArgumentException;

class CartService
{
    protected string $sessionKey = 'hostvim_cart';

    protected const PRODUCT_KEY_PATTERN = '/^\d+_(monthly|quarterly|semiannual|yearly|biennial|triennial|onetime)$/';

    protected const DOMAIN_KEY_PATTERN = '/^domain:[a-z0-9.-]+$/';

    public function __construct(
        protected CampaignService $campaigns,
        protected DomainAvailabilityService $domains,
        protected DomainSettings $domainSettings,
        protected PanelzeApiService $panelApi,
        protected AccountingReportService $accounting,
    ) {}

    public function items(): array
    {
        return session($this->sessionKey, []);
    }

    public function add(Product $product, string $billingCycle = 'monthly', int $quantity = 1): void
    {
        if ($product->isDomain()) {
            throw new InvalidArgumentException('Alan adları /domain sayfasından eklenir.');
        }

        $billingCycle = $this->validateCycle($billingCycle);
        $quantity = max(1, min($quantity, 99));

        $pricing = $this->campaigns->pricingFor($product, $billingCycle);
        $price = $pricing['final'];

        if ($price <= 0) {
            throw new InvalidArgumentException('Seçilen fatura dönemi için geçerli fiyat bulunamadı.');
        }

        $items = $this->items();
        $key = $product->id.'_'.$billingCycle;

        if (isset($items[$key])) {
            $items[$key]['quantity'] = min(99, $items[$key]['quantity'] + $quantity);
        } else {
            $items[$key] = [
                'item_type' => match (true) {
                    $product->isCloudProvision() => 'cloud',
                    $product->isManualProvision() => 'manual',
                    default => 'hosting',
                },
                'product_id' => $product->id,
                'product_name' => $product->name,
                'billing_cycle' => $billingCycle,
                'unit_price' => (float) $price,
                'original_price' => (float) $pricing['original'],
                'quantity' => $quantity,
                'provision_type' => $product->resolvedProvisionType(),
            ];
        }

        session([$this->sessionKey => $items]);
    }

    /**
     * @param  array{domain_mode:string,domain_name:string,domain_years?:int,addon_ids?:list<int>}  $config
     */
    public function addConfiguredHosting(Product $product, string $billingCycle, array $config): void
    {
        if (! $product->isHosting()) {
            throw new InvalidArgumentException('Yalnızca hosting ürünleri yapılandırılabilir.');
        }

        $billingCycle = $this->validateCycle($billingCycle);
        $domainMode = (string) ($config['domain_mode'] ?? 'own');
        $domainName = strtolower(trim((string) ($config['domain_name'] ?? '')));
        $domainYears = max(1, min(10, (int) ($config['domain_years'] ?? 1)));
        $addonIds = array_map('intval', $config['addon_ids'] ?? []);

        if (! in_array($domainMode, ['register', 'transfer', 'own'], true)) {
            throw new InvalidArgumentException('Geçersiz alan adı seçeneği.');
        }

        if ($domainName === '' || ! preg_match('/^([a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $domainName)) {
            throw new InvalidArgumentException('Geçerli bir alan adı girin.');
        }

        $this->removeProductLines($product->id);

        if ($domainMode === 'register') {
            $domainKey = 'domain:'.$domainName;
            if (! isset($this->items()[$domainKey])) {
                $this->addDomain($domainName, $domainYears);
            }
        }

        $pricing = $this->campaigns->pricingFor($product, $billingCycle);
        $price = $pricing['final'];
        if ($price <= 0) {
            throw new InvalidArgumentException('Seçilen fatura dönemi için geçerli fiyat bulunamadı.');
        }

        $hostKey = $product->id.'_'.$billingCycle;
        $addons = [];
        $addonTotal = 0.0;

        foreach ($product->activeAddons()->whereIn('id', $addonIds)->get() as $addon) {
            $addonPrice = $addon->priceForParentCycle($billingCycle);
            if ($addonPrice === null || $addonPrice <= 0) {
                continue;
            }
            $addons[] = [
                'id' => $addon->id,
                'name' => $addon->name,
                'unit_price' => round($addonPrice, 2),
            ];
            $addonTotal += $addonPrice;
        }

        $items = $this->items();
        $items[$hostKey] = [
            'item_type' => 'hosting',
            'product_id' => $product->id,
            'product_name' => $product->name,
            'billing_cycle' => $billingCycle,
            'unit_price' => round((float) $price + $addonTotal, 2),
            'original_price' => round((float) $pricing['original'] + $addonTotal, 2),
            'hosting_base_price' => (float) $price,
            'quantity' => 1,
            'provision_type' => 'hosting',
            'service_domain' => $domainName,
            'domain_mode' => $domainMode,
            'domain_years' => $domainYears,
            'addons' => $addons,
        ];

        session([$this->sessionKey => $items]);
    }

    protected function removeProductLines(int $productId): void
    {
        $items = $this->items();
        foreach (array_keys($items) as $key) {
            if (preg_match('/^'.$productId.'_/', $key)) {
                unset($items[$key]);
            }
        }
        session([$this->sessionKey => $items]);
    }

    public function addDomain(string $domain, int $years = 1, ?float $unitPrice = null): void
    {
        $domain = strtolower(trim($domain));
        $years = max(1, min(10, $years));

        $registrarApi = null;
        if ($unitPrice === null) {
            if (! $this->domainSettings->registerEnabled()) {
                throw new InvalidArgumentException('Alan adı satışı şu an kapalı.');
            }
            $check = $this->domains->check($domain);
            if (! ($check['available'] ?? false)) {
                throw new InvalidArgumentException('Alan adı müsait değil veya desteklenmiyor.');
            }
            $unitPrice = (float) ($check['register_price'] ?? 0) * $years;
            $registrarApi = $check['registrar_api'] ?? null;
        }

        if ($unitPrice <= 0) {
            throw new InvalidArgumentException('Geçersiz alan adı fiyatı.');
        }

        $key = 'domain:'.$domain;
        session([$this->sessionKey => array_merge($this->items(), [
            $key => [
                'item_type' => 'domain_register',
                'product_id' => null,
                'product_name' => 'Alan adı: '.$domain.' ('.$years.' yıl)',
                'domain_name' => $domain,
                'domain_years' => $years,
                'billing_cycle' => 'yearly',
                'unit_price' => round($unitPrice, 2),
                'original_price' => round($unitPrice, 2),
                'quantity' => 1,
                'provision_type' => 'domain',
                'registrar_api' => $registrarApi,
            ],
        ])]);
    }

    public function remove(string $key): void
    {
        if (! preg_match(self::PRODUCT_KEY_PATTERN, $key) && ! preg_match(self::DOMAIN_KEY_PATTERN, $key)) {
            return;
        }

        $items = $this->items();
        unset($items[$key]);
        session([$this->sessionKey => $items]);
    }

    public function clear(): void
    {
        session()->forget($this->sessionKey);
        $this->campaigns->removeCoupon();
    }

    public function count(): int
    {
        return (int) array_sum(array_column($this->items(), 'quantity'));
    }

    public function hasHosting(): bool
    {
        foreach ($this->items() as $item) {
            if (($item['item_type'] ?? '') === 'hosting') {
                return true;
            }
        }

        return false;
    }

    public function hostingNeedsDomainInput(): bool
    {
        foreach ($this->items() as $item) {
            if (($item['item_type'] ?? '') !== 'hosting') {
                continue;
            }
            if (empty($item['service_domain'])) {
                return true;
            }
        }

        return false;
    }

    public function subtotal(): float
    {
        return (float) collect($this->items())->sum(fn ($item) => $item['unit_price'] * $item['quantity']);
    }

    public function couponDiscount(): float
    {
        $items = $this->validatedItems();
        $coupon = $this->campaigns->appliedCoupon();

        if (! $coupon || empty($items)) {
            return 0.0;
        }

        try {
            $this->campaigns->validateCouponForCart($coupon->code, $items);
        } catch (InvalidArgumentException) {
            return 0.0;
        }

        return $this->campaigns->couponDiscount($coupon, $items);
    }

    public function total(): float
    {
        return max(0, round($this->subtotal() - $this->couponDiscount(), 2));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function validatedItems(): array
    {
        $validated = [];
        $apiConfigured = $this->panelApi->isConfigured();

        foreach ($this->items() as $key => $item) {
            if (preg_match(self::DOMAIN_KEY_PATTERN, $key)) {
                $domain = (string) ($item['domain_name'] ?? '');
                if ($domain === '') {
                    continue;
                }
                if ($this->domainSettings->registerEnabled()) {
                    $check = $this->domains->check($domain);
                    if (! ($check['available'] ?? false)) {
                        throw new InvalidArgumentException('Alan adı artık müsait değil: '.$domain);
                    }
                    $years = max(1, (int) ($item['domain_years'] ?? 1));
                    $price = round((float) ($check['register_price'] ?? 0) * $years, 2);
                    $item['unit_price'] = $price;
                    $item['original_price'] = $price;
                    $item['registrar_api'] = $check['registrar_api'] ?? ($item['registrar_api'] ?? null);
                }
                $validated[$key] = $this->withUnitCost($item);

                continue;
            }

            if (! preg_match(self::PRODUCT_KEY_PATTERN, $key)) {
                continue;
            }

            $product = Product::query()
                ->where('id', $item['product_id'])
                ->where('is_active', true)
                ->first();

            if (! $product) {
                continue;
            }

            $cycle = $item['billing_cycle'];
            $pricing = $this->campaigns->pricingFor($product, $cycle);
            $addonTotal = (float) collect($item['addons'] ?? [])->sum('unit_price');
            $price = (float) $pricing['final'] + $addonTotal;
            $original = (float) $pricing['original'] + $addonTotal;

            if ($price <= 0 && ! $product->isManualProvision() && ! $product->isCloudProvision()) {
                continue;
            }

            $quantity = max(1, min(99, (int) ($item['quantity'] ?? 1)));

            if ($apiConfigured && $product->isHosting() && ! $product->panel_package_id) {
                throw new InvalidArgumentException(
                    '"'.$product->name.'" hosting paketi panel ile eşleştirilmemiş.'
                );
            }

            if ($product->isCloudProvision()) {
                if ($product->cloud_provider_api === null || $product->cloud_region === null || $product->cloud_plan === null || $product->cloud_image === null) {
                    throw new InvalidArgumentException(
                        '"'.$product->name.'" bulut API/bölge/plan yapılandırması eksik (admin).'
                    );
                }
            }

            $validated[$key] = $this->withUnitCost([
                'item_type' => match (true) {
                    $product->isCloudProvision() => 'cloud',
                    $product->isManualProvision() => 'manual',
                    default => 'hosting',
                },
                'product_id' => $product->id,
                'product_name' => $product->name,
                'billing_cycle' => $cycle,
                'unit_price' => $price,
                'original_price' => $original,
                'quantity' => $quantity,
                'provision_type' => $product->resolvedProvisionType(),
                'service_domain' => $item['service_domain'] ?? null,
                'domain_mode' => $item['domain_mode'] ?? null,
                'domain_years' => $item['domain_years'] ?? null,
                'addons' => $item['addons'] ?? [],
                'hosting_base_price' => $item['hosting_base_price'] ?? null,
            ]);
        }

        if ($validated !== $this->items()) {
            session([$this->sessionKey => $validated]);
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    protected function withUnitCost(array $item): array
    {
        $cost = $this->accounting->resolveUnitCostForCartItem($item);
        if ($cost !== null) {
            $item['unit_cost'] = $cost;
        }

        return $item;
    }

    protected function validateCycle(string $cycle): string
    {
        if (! BillingCycle::isValid($cycle)) {
            throw new InvalidArgumentException('Geçersiz fatura dönemi.');
        }

        return $cycle;
    }
}
