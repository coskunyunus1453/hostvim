<?php

namespace App\Services\EInvoice;

use App\Services\EInvoice\Providers\MukellefProvider;
use App\Services\EInvoice\Providers\NilveraProvider;
use App\Services\EInvoice\Providers\ParasutProvider;

class EInvoiceResolver
{
    /** @return array<string, class-string<EInvoiceProvider>> */
    public const PROVIDERS = [
        'nilvera' => NilveraProvider::class,
        'parasut' => ParasutProvider::class,
        'mukellef' => MukellefProvider::class,
    ];

    /** @return array<string, string> Seçim listesi (label) */
    public static function options(): array
    {
        return [
            'none' => 'Kapalı (sadece taslak/proforma PDF)',
            'nilvera' => 'Nilvera — e-Fatura/e-Arşiv (önerilen)',
            'parasut' => 'Paraşüt — ön muhasebe + e-Fatura',
            'mukellef' => 'Mükellef — uygun kontör',
        ];
    }

    /** Aktif (ayarlardaki) sağlayıcıyı döndürür; yoksa null. */
    public function active(): ?EInvoiceProvider
    {
        return $this->make(EInvoiceSettings::provider());
    }

    public function make(?string $key): ?EInvoiceProvider
    {
        $class = self::PROVIDERS[$key] ?? null;
        if ($class === null) {
            return null;
        }

        return app($class);
    }
}
