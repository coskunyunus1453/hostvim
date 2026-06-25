<?php

namespace App\Support;

class NavIcons
{
    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            '' => 'Simge yok',
            'home' => 'Ana sayfa',
            'server' => 'Sunucu',
            'cloud' => 'Bulut',
            'globe' => 'Domain / Dünya',
            'cpu' => 'VPS / CPU',
            'shield' => 'Güvenlik / SSL',
            'mail' => 'E-posta',
            'document' => 'Döküman / Blog',
            'phone' => 'İletişim',
            'cart' => 'Sepet',
            'user' => 'Hesap',
            'sparkles' => 'Öne çıkan',
            'bolt' => 'Hız / Performans',
            'support' => 'Destek',
        ];
    }

    public static function isValid(?string $icon): bool
    {
        return $icon === null || $icon === '' || array_key_exists($icon, self::options());
    }
}
