<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductCategory;

class HostingConfigureService
{
    protected string $sessionKey = 'hostvim_hosting_configure';

    public function get(): ?array
    {
        $data = session($this->sessionKey);

        return is_array($data) ? $data : null;
    }

    public function start(Product $product, ProductCategory $category): void
    {
        session([$this->sessionKey => [
            'product_id' => $product->id,
            'category_slug' => $category->slug,
            'product_slug' => $product->slug,
            'domain_mode' => null,
            'domain_name' => null,
            'domain_years' => 1,
            'billing_cycle' => null,
            'addon_ids' => [],
        ]]);
    }

    public function update(array $patch): void
    {
        $current = $this->get() ?? [];
        session([$this->sessionKey => array_merge($current, $patch)]);
    }

    public function clear(): void
    {
        session()->forget($this->sessionKey);
    }

    public function resolveProduct(): ?Product
    {
        $data = $this->get();
        if (! $data || empty($data['product_id'])) {
            return null;
        }

        return Product::query()
            ->where('id', $data['product_id'])
            ->where('is_active', true)
            ->with(['category', 'activeAddons'])
            ->first();
    }
}
