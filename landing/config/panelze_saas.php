<?php

return [
    /**
     * Boş: yalnızca geçerli lisans anahtarı yeterli (rate limit önerilir).
     * Dolu: POST /api/v1/license/validate için Authorization: Bearer <değer> zorunlu.
     */
    'license_api_secret' => env('PANELZE_LICENSE_API_SECRET', ''),

    /**
     * Panel güncelleme hub API (GET /api/v1/panel-updates/check).
     * Dolu ise Authorization: Bearer zorunlu.
     */
    'panel_updates_api_secret' => env('PANELZE_PANEL_UPDATES_API_SECRET', env('PANELZE_LICENSE_API_SECRET', '')),

    /**
     * Çevrimdışı imzalı lisans (Ed25519) — hub aktivasyon sırasında domaine bağlı
     * PLZ1 anahtarı imzalar; panel/engine bunu internet olmadan doğrular.
     *
     * - offline_signing_secret: SATICI private key (base64). YALNIZCA hub sunucusunda
     *   .env'de tutulur, asla repoya/panele konmaz. Boşsa imzalama devre dışı kalır
     *   ve aktivasyon yalnızca opak (online) anahtar döner.
     * - offline_public_key: panel/engine'e gömülü public key ile AYNI olmalıdır.
     * - default_max_activations: lisans başına izinli farklı host sayısı. 0 = sınırsız.
     *   Ürün/lisans limits içinde 'max_activations' varsa o önceliklidir.
     */
    'offline_signing_secret' => env('PANELZE_LICENSE_SIGNING_SECRET', ''),
    'offline_public_key' => env('PANELZE_LICENSE_PUBLIC_KEY', 'tiv72XAtO2krha6GBWryaXo+WGscEEbnbpo283xnLg8='),
    'offline_grace_days' => (int) env('PANELZE_LICENSE_OFFLINE_GRACE_DAYS', 14),
    'default_max_activations' => (int) env('PANELZE_LICENSE_MAX_ACTIVATIONS', 0),

    /**
     * Ödeme: Türkiye → PayTR, diğer → Stripe (locale veya zorlama ile).
     */
    'billing' => [
        /** auto | paytr | stripe — auto iken locale ve force_provider’a bakılır */
        'default_provider' => env('PANELZE_BILLING_DEFAULT', 'auto'),
        /** Boş değilse (paytr|stripe) her zaman bu sağlayıcı kullanılır */
        'force_provider' => env('PANELZE_BILLING_FORCE_PROVIDER', ''),
        /** default_provider=auto iken bu locale’ler PayTR seçer */
        'turkish_locales' => array_values(array_filter(array_map('trim', explode(',', (string) env('PANELZE_BILLING_TR_LOCALES', 'tr'))))),
    ],

    'paytr' => [
        'merchant_id' => env('PAYTR_MERCHANT_ID', ''),
        'merchant_key' => env('PAYTR_MERCHANT_KEY', ''),
        'merchant_salt' => env('PAYTR_MERCHANT_SALT', ''),
        /** 1 = test işlem (canlı mağazada PayTR test modu) */
        'test_mode' => env('PAYTR_TEST_MODE', '0'),
        'debug_on' => env('PAYTR_DEBUG_ON', '0'),
        'timeout_limit' => (int) env('PAYTR_TIMEOUT_MINUTES', 30),
    ],

    'stripe' => [
        'secret' => env('STRIPE_SECRET', ''),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
    ],
];
