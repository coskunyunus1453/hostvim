<?php

/**
 * Hazır domain registrar API sağlayıcıları — Hostvim store admin'den yapılandırılır.
 */
return [
    'providers' => [
        'spaceship' => [
            'api_name' => 'spaceship',
            'name' => 'Spaceship',
            'tagline' => '2026\'nın en ucuz fiyat lideri',
            'highlight' => 'Kayıt ve yenileme ücretleri neredeyse maliyetine yakın; modern panel.',
            'website' => 'https://www.spaceship.com',
            'docs_url' => 'https://docs.spaceship.dev/',
            'currency' => 'USD',
            'credential_fields' => [
                'api_key' => ['label' => 'API Key', 'type' => 'password', 'required' => true],
                'api_secret' => ['label' => 'API Secret', 'type' => 'password', 'required' => true],
            ],
            'supported_tlds' => ['global'],
        ],
        'porkbun' => [
            'api_name' => 'porkbun',
            'name' => 'Porkbun',
            'tagline' => 'Sektörün en sevilen ve güvenilir platformu',
            'highlight' => 'Ücretsiz WHOIS gizliliği; komisyonsuz, doğrudan net fiyatlandırma.',
            'website' => 'https://porkbun.com',
            'docs_url' => 'https://porkbun.com/api/json/v3/documentation',
            'currency' => 'USD',
            'credential_fields' => [
                'api_key' => ['label' => 'API Key', 'type' => 'password', 'required' => true],
                'secret_key' => ['label' => 'Secret API Key', 'type' => 'password', 'required' => true],
            ],
            'supported_tlds' => ['global'],
        ],
        'cloudflare' => [
            'api_name' => 'cloudflare',
            'name' => 'Cloudflare Registrar',
            'tagline' => 'Sıfır kar marjı politikası',
            'highlight' => 'Domainleri ICANN geliş fiyatına satar; DNS yönetimi Cloudflare\'de olmalı.',
            'website' => 'https://www.cloudflare.com/products/registrar/',
            'docs_url' => 'https://developers.cloudflare.com/registrar/registrar-api/',
            'currency' => 'USD',
            'credential_fields' => [
                'api_token' => ['label' => 'API Token', 'type' => 'password', 'required' => true],
                'account_id' => ['label' => 'Account ID', 'type' => 'text', 'required' => true],
            ],
            'supported_tlds' => ['global'],
        ],
        'metunic' => [
            'api_name' => 'metunic',
            'name' => 'Metunic',
            'tagline' => 'En ucuz .tr uzantıları',
            'highlight' => 'ODTÜ kökenli yerli firma; .com.tr, .net.tr ve resmi Türkiye uzantıları.',
            'website' => 'https://metunic.com.tr',
            'docs_url' => 'https://app.metunic.com.tr',
            'currency' => 'TRY',
            'credential_fields' => [
                'base_url' => ['label' => 'API URL', 'type' => 'text', 'required' => false, 'placeholder' => 'https://api.metunic.com.tr/v1'],
                'username' => ['label' => 'API Kullanıcı / E-posta', 'type' => 'text', 'required' => true],
                'password' => ['label' => 'API Şifre', 'type' => 'password', 'required' => true],
                'cookie_name' => ['label' => 'Oturum çerez adı', 'type' => 'text', 'required' => false, 'placeholder' => 'WiseCPMetunicFononline'],
            ],
            'supported_tlds' => ['TR', 'tr'],
        ],
    ],

    'default_usd_try_rate' => 35.0,
    'default_markup_percent' => 15.0,
];
