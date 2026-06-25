<?php

namespace App\Models;

use App\Services\Domain\DomainCurrency;
use App\Services\Domain\DomainSettings;
use Illuminate\Database\Eloquent\Model;

class DomainTld extends Model
{
    protected $fillable = [
        'tld',
        'register_price',
        'renew_price',
        'transfer_price',
        'wholesale_register',
        'wholesale_renew',
        'wholesale_currency',
        'wholesale_registrar_api',
        'registrar_api_name',
        'markup_percent',
        'auto_price',
        'is_active',
        'sort_order',
        'prices_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'register_price' => 'decimal:2',
            'renew_price' => 'decimal:2',
            'transfer_price' => 'decimal:2',
            'wholesale_register' => 'decimal:2',
            'wholesale_renew' => 'decimal:2',
            'markup_percent' => 'decimal:2',
            'auto_price' => 'boolean',
            'is_active' => 'boolean',
            'prices_synced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (DomainTld $tld) {
            if ($tld->auto_price) {
                $tld->recalculatePrices();
            }
        });
    }

    public function normalizedTld(): string
    {
        $tld = strtolower(trim($this->tld));

        return str_starts_with($tld, '.') ? $tld : '.'.$tld;
    }

    /**
     * Otomatik fiyat modu: maliyet (wholesale) + para birimi + kar marji + kur
     * ile satis fiyatlarini (register/renew/transfer) yeniden hesaplar.
     */
    public function recalculatePrices(): void
    {
        $wholesaleRegister = (float) ($this->wholesale_register ?? 0);
        if ($wholesaleRegister <= 0) {
            // Maliyet girilmemis: otomatik hesap yapacak veri yok, mevcut satis fiyatina dokunma.
            return;
        }

        $currency = $this->wholesale_currency ?: 'USD';
        $converter = app(DomainCurrency::class);
        $settings = app(DomainSettings::class);

        $markup = $this->markup_percent;
        if ($markup === null || $markup === '') {
            $markup = $settings->defaultMarkupPercent();
        }
        $factor = 1 + (((float) $markup) / 100);

        $wholesaleRenew = (float) ($this->wholesale_renew ?? 0);
        if ($wholesaleRenew <= 0) {
            $wholesaleRenew = $wholesaleRegister;
        }

        $this->register_price = round($converter->toTry($wholesaleRegister, $currency) * $factor, 2);
        $this->renew_price = round($converter->toTry($wholesaleRenew, $currency) * $factor, 2);
        $this->prices_synced_at = now();
    }
}
