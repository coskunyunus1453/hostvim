<?php

/**
 * Bulut VPS sağlayıcıları — otomatik sunucu kurulumu için hazır API entegrasyonları.
 */
return [
    'providers' => [
        'hetzner' => [
            'api_name' => 'hetzner',
            'name' => 'Hetzner Cloud',
            'tagline' => 'Fiyat/performans lideri',
            'highlight' => 'Almanya ve Finlandiya lokasyonları; Türkiye\'ye düşük ping. Saniyeler içinde sunucu kurar.',
            'website' => 'https://www.hetzner.com/cloud',
            'docs_url' => 'https://docs.hetzner.cloud/',
            'currency' => 'EUR',
            'credential_fields' => [
                'api_token' => ['label' => 'API Token', 'type' => 'password', 'required' => true],
            ],
            'default_regions' => [
                'fsn1' => 'Falkenstein (DE)',
                'nbg1' => 'Nürnberg (DE)',
                'hel1' => 'Helsinki (FI)',
            ],
            'default_sizes' => [
                'cx22' => 'CX22 — 2 vCPU, 4 GB RAM',
                'cx32' => 'CX32 — 4 vCPU, 8 GB RAM',
                'cpx21' => 'CPX21 — 3 vCPU, 4 GB RAM',
            ],
            'default_images' => [
                'ubuntu-22.04' => 'Ubuntu 22.04',
                'ubuntu-24.04' => 'Ubuntu 24.04',
                'debian-12' => 'Debian 12',
            ],
        ],
        'vultr' => [
            'api_name' => 'vultr',
            'name' => 'Vultr',
            'tagline' => '32+ global lokasyon',
            'highlight' => 'Gelişmiş API; anlık NVMe VPS kurulumu. WHMCS/WiseCP modülleri mevcut.',
            'website' => 'https://www.vultr.com',
            'docs_url' => 'https://www.vultr.com/api/',
            'currency' => 'USD',
            'credential_fields' => [
                'api_key' => ['label' => 'API Key', 'type' => 'password', 'required' => true],
            ],
            'default_regions' => [
                'fra' => 'Frankfurt (DE)',
                'ams' => 'Amsterdam (NL)',
                'ist' => 'Istanbul (TR)',
            ],
            'default_sizes' => [
                'vc2-1c-1gb' => '1 vCPU, 1 GB RAM',
                'vc2-2c-4gb' => '2 vCPU, 4 GB RAM',
                'vc2-4c-8gb' => '4 vCPU, 8 GB RAM',
            ],
            'default_images' => [
                '2136' => 'Ubuntu 22.04 LTS',
                '1743' => 'Ubuntu 24.04 LTS',
            ],
        ],
        'digitalocean' => [
            'api_name' => 'digitalocean',
            'name' => 'DigitalOcean',
            'tagline' => 'Sektörün en popüler bulutu',
            'highlight' => 'Sorunsuz API entegrasyonu; Droplet\'leri saniyeler içinde açar.',
            'website' => 'https://www.digitalocean.com',
            'docs_url' => 'https://docs.digitalocean.com/reference/api/',
            'currency' => 'USD',
            'credential_fields' => [
                'api_token' => ['label' => 'API Token', 'type' => 'password', 'required' => true],
            ],
            'default_regions' => [
                'fra1' => 'Frankfurt (DE)',
                'ams3' => 'Amsterdam (NL)',
                'lon1' => 'London (UK)',
            ],
            'default_sizes' => [
                's-1vcpu-1gb' => '1 vCPU, 1 GB RAM',
                's-2vcpu-4gb' => '2 vCPU, 4 GB RAM',
                's-4vcpu-8gb' => '4 vCPU, 8 GB RAM',
            ],
            'default_images' => [
                'ubuntu-22-04-x64' => 'Ubuntu 22.04',
                'ubuntu-24-04-x64' => 'Ubuntu 24.04',
            ],
        ],
        'linode' => [
            'api_name' => 'linode',
            'name' => 'Linode (Akamai)',
            'tagline' => 'Kurumsal yüksek performans',
            'highlight' => 'Tam otomasyonlu API; anlık kurulum altyapısı.',
            'website' => 'https://www.linode.com',
            'docs_url' => 'https://techdocs.akamai.com/linode-api/reference',
            'currency' => 'USD',
            'credential_fields' => [
                'api_token' => ['label' => 'Personal Access Token', 'type' => 'password', 'required' => true],
            ],
            'default_regions' => [
                'eu-central' => 'Frankfurt (DE)',
                'eu-west' => 'London (UK)',
                'us-east' => 'Newark (US)',
            ],
            'default_sizes' => [
                'g6-nanode-1' => '1 GB — Nanode',
                'g6-standard-2' => '4 GB — Standard',
                'g6-standard-4' => '8 GB — Standard',
            ],
            'default_images' => [
                'linode/ubuntu22.04' => 'Ubuntu 22.04',
                'linode/ubuntu24.04' => 'Ubuntu 24.04',
            ],
        ],
        'contabo' => [
            'api_name' => 'contabo',
            'name' => 'Contabo',
            'tagline' => 'Yüksek kaynak / uygun fiyat',
            'highlight' => 'VPS ve VDS otomatik kurulum (API). EU/US/Asya lokasyonları. cloud-init ile Panelze kurulabilir.',
            'website' => 'https://contabo.com',
            'docs_url' => 'https://api.contabo.com/',
            'currency' => 'EUR',
            'credential_fields' => [
                'client_id' => ['label' => 'Client ID', 'type' => 'text', 'required' => true],
                'client_secret' => ['label' => 'Client Secret', 'type' => 'password', 'required' => true],
                'api_user' => ['label' => 'API User (e-posta)', 'type' => 'text', 'required' => true],
                'api_password' => ['label' => 'API Password', 'type' => 'password', 'required' => true],
            ],
            'default_regions' => [
                'EU' => 'Avrupa (Almanya)',
                'UK' => 'Birleşik Krallık',
                'US-central' => 'ABD - Merkez',
                'US-east' => 'ABD - Doğu',
                'US-west' => 'ABD - Batı',
                'SIN' => 'Singapur (Asya)',
            ],
            'default_sizes' => [
                'V1' => 'VPS S SSD — 4 vCPU, 8 GB',
                'V2' => 'VPS M SSD — 6 vCPU, 16 GB',
                'V3' => 'VPS L SSD — 8 vCPU, 30 GB',
                'V12' => 'VPS S NVMe — 4 vCPU, 8 GB',
                'V8' => 'VDS S — 3 pCPU, 24 GB',
                'V9' => 'VDS M — 4 pCPU, 32 GB',
                'V10' => 'VDS L — 6 pCPU, 48 GB',
            ],
            'default_images' => [
                // Contabo standart imaj UUID'leri; admin panelde "İmajları çek" ile güncelleyebilirsiniz.
                'afecbb85-e2fc-46f0-9684-b46b1faf00bb' => 'Ubuntu 24.04',
                'd64d5c6c-9dda-4e38-8174-0ee282474d8a' => 'Ubuntu 22.04',
            ],
        ],
    ],

    'hostname_prefix' => 'hv',
    'ssh_key_name' => null,

    /*
     * VPS oluşturulduğunda cloud-init ile sunucuya otomatik Panelze paneli kurulumu.
     * remote_install_url: VPS'te root olarak çalıştırılacak kurulum betiğinin URL'i
     *   (deploy/bootstrap/remote-install.sh barındırılan adresi).
     */
    'panel_install' => [
        'enabled' => (bool) env('CLOUD_PANELZE_AUTO_INSTALL', false),
        'remote_install_url' => (string) env('CLOUD_PANELZE_INSTALL_URL', ''),
        'panel_url' => (string) env('CLOUD_PANELZE_PANEL_URL', env('PANELZE_PANEL_URL', '')),
    ],

    // createServer sonrasi sunucunun hazir (IP+aktif) olmasini bekleme limitleri.
    'poll' => [
        'max_attempts' => (int) env('CLOUD_POLL_MAX_ATTEMPTS', 30),
        'interval_seconds' => (int) env('CLOUD_POLL_INTERVAL', 10),
    ],
];
