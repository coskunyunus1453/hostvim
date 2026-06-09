<?php

$panelRoot = dirname(__DIR__);

return [
    'version' => env('PANELZE_PANEL_VERSION', '0.1.0'),

    /** Panel self-update hub (panelze.com landing API) */
    'updates' => [
        'hub_url' => rtrim(trim((string) env(
            'PANELZE_UPDATE_HUB_URL',
            env('HOSTVIM_UPDATE_HUB_URL', env('LICENSE_SERVER_URL', env('PANELZE_LICENSE_HUB_URL', env('HOSTVIM_LICENSE_HUB_URL', 'https://panelze.com'))))
        )), '/'),
        'api_secret' => trim((string) env(
            'PANELZE_PANEL_UPDATES_API_SECRET',
            env('HOSTVIM_PANEL_UPDATES_API_SECRET', env('PANELZE_LICENSE_API_SECRET', env('HOSTVIM_LICENSE_API_SECRET', '')))
        )),
        'channel' => env('PANELZE_UPDATE_CHANNEL', 'stable'),
        'check_cache_seconds' => (int) env('PANELZE_UPDATE_CHECK_CACHE', 300),
    ],
    'profile' => env('APP_PROFILE', 'customer'),
    'customer_profile' => env('APP_PROFILE', 'customer') === 'customer',
    'vendor_profile' => env('APP_PROFILE', 'customer') === 'vendor',

    /** Hosting dosya kökü (engine `paths.web_root` ile aynı olmalı; boşsa proje kökü/data/www) */
    'hosting_web_root' => env('PANELZE_HOSTING_WEB_ROOT', env('PANELSAR_HOSTING_WEB_ROOT', dirname($panelRoot).DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'www')),

    /** Boşluk/BOM temizliği; eski kurulumlar için PANELSAR_* yedek anahtarları */
    'engine_url' => rtrim(trim((string) env('ENGINE_API_URL', env('PANELSAR_ENGINE_URL', 'http://127.0.0.1:9090'))), '/'),
    'engine_internal_key' => trim((string) env('ENGINE_INTERNAL_KEY', env('PANELSAR_ENGINE_INTERNAL_KEY', ''))),
    'engine_secret' => trim((string) env('ENGINE_API_SECRET', env('PANELSAR_ENGINE_API_SECRET', env('PANELSAR_JWT_SECRET', '')))),
    /** Uzak yedek → engine restore-upload (HTTP istemci timeout, saniye) */
    'engine_restore_upload_timeout' => (int) env('PANELZE_ENGINE_RESTORE_UPLOAD_TIMEOUT', 7200),
    /** Dosya yöneticisi indirme: panel → engine HTTP timeout (saniye) */
    'engine_download_timeout' => max(60, (int) env('PANELZE_ENGINE_DOWNLOAD_TIMEOUT', 1800)),
    'vendor_license_signing_key' => env('VENDOR_LICENSE_SIGNING_KEY', ''),
    'vendor_billing_webhook_secret' => env('VENDOR_BILLING_WEBHOOK_SECRET', ''),
    'vendor_request_replay_ttl_seconds' => (int) env('VENDOR_REQUEST_REPLAY_TTL_SECONDS', 300),
    'vendor_license_grace_hours' => (int) env('VENDOR_LICENSE_GRACE_HOURS', 24),
    'vendor_portal_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('VENDOR_PORTAL_HOSTS', ''))
    ))),
    'vendor_enabled' => filter_var(env('VENDOR_ENABLED', env('APP_PROFILE', 'customer') === 'vendor'), FILTER_VALIDATE_BOOLEAN),
    /**
     * Varsayılan kapalı. Yalnızca .env’de açıkça true/1/on/yes iken açılır (boş veya false = kapalı).
     * Satır yoksa veya false ise kapatılmış sayılır; üretimde `ENFORCE_ADMIN_2FA=true` kaldıysa `php artisan config:clear` deneyin.
     * Açıkken: 2FA etkin admin hesaplarında kritik API’ler için girişte OTP ile verilen token gerekir.
     * Not: Kullanıcının Ayarlar’dan kendi açtığı 2FA (two_factor_enabled) bundan bağımsızdır; kapalı politika ile bile o hesap OTP ister.
     */
    'enforce_admin_2fa' => (static function (): bool {
        $v = env('ENFORCE_ADMIN_2FA');
        if ($v === null || $v === '') {
            return false;
        }
        if (is_bool($v)) {
            return $v;
        }
        $s = strtolower(trim((string) $v));

        return in_array($s, ['1', 'true', 'yes', 'on'], true);
    })(),

    /** Maskelemeli audit logları (önerilir: açık) */
    'safe_audit_enabled' => filter_var(env('PANELZE_SAFE_AUDIT', env('PANELSAR_SAFE_AUDIT', true)), FILTER_VALIDATE_BOOLEAN),
    'cors_allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
    ))),

    /** Web terminal WebSocket URL’si (wss) — HTTP panelde kapalı; TLS sonrası true yapın */
    'force_wss_terminal' => filter_var(env('FORCE_WSS_TERMINAL', false), FILTER_VALIDATE_BOOLEAN),

    /** Let’s Encrypt: istekte e-posta yoksa engine `hosting.lets_encrypt_email` ile birlikte kullanılır */
    'lets_encrypt_email' => env('PANELZE_LETS_ENCRYPT_EMAIL', env('PANELSAR_LETS_ENCRYPT_EMAIL', '')),

    /**
     * WHMCS modül zip indirimi (admin panel sayfası).
     * Boş: önce ../integrations/.../panelze, yoksa storage/app/whmcs/panelze-whmcs-module.zip
     */
    'whmcs_module_source_dir' => env('PANELZE_WHMCS_MODULE_SOURCE_DIR', ''),
    'whmcs_module_prebuilt_zip' => env('PANELZE_WHMCS_MODULE_PREBUILT_ZIP', ''),

    /**
     * Merkezi lisans doğrulama adresi. .env’de LICENSE_SERVER_URL yoksa varsayılan hub kullanılır;
     * müşteri kurulumunda ek ayar gerekmez. Özel / hava boşluklu kurulum: LICENSE_SERVER_URL= ile kapatın.
     */
    'license_server' => rtrim(trim((string) env(
        'LICENSE_SERVER_URL',
        env('PANELZE_LICENSE_HUB_URL', env('HOSTVIM_LICENSE_HUB_URL', 'https://panelze.com'))
    )), '/'),
    /** Panel → hub isteğinde Bearer (landing PANELZE_LICENSE_API_SECRET ile aynı olmalı) */
    'license_server_api_secret' => trim((string) env(
        'LICENSE_SERVER_API_SECRET',
        env('PANELZE_LICENSE_API_SECRET', env('HOSTVIM_LICENSE_API_SECRET', ''))
    )),
    /** Otomasyon / eski kurulum: doluysa veritabanındaki anahtardan önceliklidir */
    'license_key' => trim((string) env('LICENSE_KEY', '')),

    /**
     * Pro lisans plan kodları (hub product code) ve premium modüller.
     * Geliştirme: PANELZE_LICENSE_FORCE_PRO=1 veya PANELZE_FEATURE_PHPMYADMIN_SSO=1
     */
    'license' => [
        'force_valid' => env('PANELZE_LICENSE_FORCE_VALID', false),
        'force_pro' => env('PANELZE_LICENSE_FORCE_PRO', false),
        'pro_plan_codes' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env(
                'PANELZE_PRO_PLAN_CODES',
                'pro,pro-monthly,pro-yearly,pro-lifetime,pro-lisans,enterprise,vendor'
            ))
        ))),
        'community_plan_codes' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('PANELZE_COMMUNITY_PLAN_CODES', 'community'))
        ))),
        'pro_default_modules' => ['phpmyadmin_sso'],
    ],

    'features' => [
        'phpmyadmin_sso' => env('PANELZE_FEATURE_PHPMYADMIN_SSO', false),
    ],

    /** phpMyAdmin signon (Pro): tek kullanımlık panel → /pma-signon → phpMyAdmin */
    'phpmyadmin_signon' => [
        'session_name' => env('PANELZE_PMA_SIGNON_SESSION', 'SignonSession'),
        'token_ttl' => max(30, (int) env('PANELZE_PMA_SIGNON_TTL', 90)),
    ],

    /**
     * İsteğe bağlı CDN önbellek temizliği (api_token + zone_id; provider: cloudflare).
     *
     * @var array{provider: string, api_token: string, zone_id: string}
     */
    'cdn' => [
        'provider' => strtolower(trim((string) env('PANELZE_CDN_PROVIDER', ''))),
        'api_token' => trim((string) env('PANELZE_CDN_API_TOKEN', '')),
        'zone_id' => trim((string) env('PANELZE_CDN_ZONE_ID', '')),
    ],

    'default_locale' => env('PANEL_DEFAULT_LOCALE', 'en'),
    'available_locales' => explode(',', env('PANEL_AVAILABLE_LOCALES', 'en,tr,de,fr,es,pt,zh,ja,ar,ru')),

    'default_php_version' => '8.2',
    'supported_php_versions' => ['7.4', '8.0', '8.1', '8.2', '8.3'],

    'backup' => [
        'retention_days' => 30,
        'max_backups_per_user' => 5,
    ],

    'google_drive' => [
        'client_id' => env('GOOGLE_DRIVE_CLIENT_ID', ''),
        'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET', ''),
        'redirect_uri' => env('GOOGLE_DRIVE_REDIRECT_URI', ''),
        'folder_name' => env('GOOGLE_DRIVE_FOLDER_NAME', 'Panelze Backups'),
    ],

    /** Meraklısına — hız testi ve SEO analizi */
    'curious' => [
        'speed_download_bytes' => (int) env('PANELZE_SPEED_DOWNLOAD_BYTES', 2_097_152),
        'speed_upload_max_bytes' => (int) env('PANELZE_SPEED_UPLOAD_MAX_BYTES', 2_097_152),
        'speed_token_ttl' => (int) env('PANELZE_SPEED_TOKEN_TTL', 300),
        'seo_timeout' => (int) env('PANELZE_SEO_TIMEOUT', 20),
        'ookla_enabled' => filter_var(env('PANELZE_OOKLA_ENABLED', true), FILTER_VALIDATE_BOOL),
        'ookla_binary' => env('PANELZE_OOKLA_BINARY', 'speedtest'),
        'ookla_fallback_binary' => env('PANELZE_OOKLA_FALLBACK_BINARY', 'speedtest-cli'),
        'ookla_timeout' => (int) env('PANELZE_OOKLA_TIMEOUT', 120),
        'ookla_cache_minutes' => (int) env('PANELZE_OOKLA_CACHE_MINUTES', 30),
        'ookla_history_limit' => (int) env('PANELZE_OOKLA_HISTORY_LIMIT', 30),
        'ookla_history_retention_days' => (int) env('PANELZE_OOKLA_HISTORY_RETENTION_DAYS', 90),
        'ookla_history_max_rows' => (int) env('PANELZE_OOKLA_HISTORY_MAX_ROWS', 200),
    ],

    'limits' => [
        'max_upload_size_mb' => 256,
        'max_file_manager_size_mb' => (int) env('PANELZE_MAX_FILE_MANAGER_SIZE_MB', 50),
        /** SQL yedeği içe aktarma (MB) */
        'max_db_import_mb' => (int) env('PANELZE_MAX_DB_IMPORT_MB', 512),
        /** Zip açarken kota: arşiv boyutu × çarpan (tahmini çıkarılan veri) */
        'disk_unzip_expand_multiplier' => max(2, (int) env('PANELZE_DISK_UNZIP_EXPAND_MULT', 4)),
    ],

    /** Müşteri cron görevleri (cron:run-due) */
    'cron' => [
        'timeout' => max(30, (int) env('PANELZE_CRON_TIMEOUT', 180)),
        // 0 = kapalı (scrape gibi uzun süre çıktı vermeyen işler 120 sn'de kesilmesin)
        'idle_timeout' => max(0, (int) env('PANELZE_CRON_IDLE_TIMEOUT', 0)),
        'lock_seconds' => max(60, (int) env('PANELZE_CRON_LOCK_SECONDS', 600)),
    ],

    /** API throttle (dosya yöneticisi vb.) — .env ile artırılabilir */
    'rate_limits' => [
        'files_read_per_minute' => max(60, (int) env('PANELZE_FILES_READ_PER_MINUTE', 360)),
        'files_write_per_minute' => max(30, (int) env('PANELZE_FILES_WRITE_PER_MINUTE', 180)),
        'files_upload_per_minute' => max(10, (int) env('PANELZE_FILES_UPLOAD_PER_MINUTE', 40)),
        'databases_import_per_hour' => max(4, (int) env('PANELZE_DB_IMPORT_PER_HOUR', 30)),
        /** Meraklısına hız testi (ping×3 + prepare + download + upload + cleanup ≈ 8 istek/test) */
        'curious_speed_per_minute' => max(40, (int) env('PANELZE_CURIOUS_SPEED_PER_MINUTE', 200)),
        'curious_speed_complete_per_hour' => max(6, (int) env('PANELZE_CURIOUS_SPEED_COMPLETE_PER_HOUR', 30)),
        'curious_seo_per_minute' => max(5, (int) env('PANELZE_CURIOUS_SEO_PER_MINUTE', 10)),
    ],

    /** mysqldump / mysql / pg_dump / psql — PATH’te yoksa tam yol verin */
    'database_tools' => [
        'mysqldump_path' => env('MYSQLDUMP_PATH', 'mysqldump'),
        'mysql_path' => env('MYSQL_CLIENT_PATH', 'mysql'),
        'pg_dump_path' => env('PG_DUMP_PATH', 'pg_dump'),
        'psql_path' => env('PSQL_PATH', 'psql'),
    ],

    /**
     * Arayüzde “harici araç” bağlantıları (ör. https://mysql.example.com/phpmyadmin).
     */
    'ui' => [
        'phpmyadmin_url' => env('PHPMYADMIN_URL', ''),
        'adminer_url' => env('ADMINER_URL', ''),
    ],

    /** Panel üzerinden gerçek MySQL veritabanı/kullanıcı oluşturma (XAMPP: root + boş şifre) */
    'mysql_provision' => [
        'enabled' => env('MYSQL_PROVISION_ENABLED', false),
        'host' => env('MYSQL_PROVISION_HOST', env('DB_HOST', '127.0.0.1')),
        'port' => (int) env('MYSQL_PROVISION_PORT', env('DB_PORT', 3306)),
        'username' => env('MYSQL_PROVISION_USERNAME', 'root'),
        'password' => env('MYSQL_PROVISION_PASSWORD', ''),
        /** Yeni veritabanı için varsayılan MySQL kullanıcı @host (localhost / 127.0.0.1 / % vb.) */
        'grant_host' => env('MYSQL_PROVISION_GRANT_HOST', 'localhost'),
        /**
         * İzin verilen GRANT host değerleri (özel IP için ayrıca geçerli IPv4/IPv6 kabul edilir).
         *
         * @var list<string>
         */
        'allowed_grant_hosts' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('MYSQL_ALLOWED_GRANT_HOSTS', 'localhost,127.0.0.1,%'))
        ))),
    ],

    /** PostgreSQL: CREATE DATABASE / USER (pdo_pgsql gerekir) */
    'postgres_provision' => [
        'enabled' => env('POSTGRES_PROVISION_ENABLED', false),
        'host' => env('POSTGRES_PROVISION_HOST', '127.0.0.1'),
        'port' => (int) env('POSTGRES_PROVISION_PORT', 5432),
        'username' => env('POSTGRES_PROVISION_USERNAME', 'postgres'),
        'password' => env('POSTGRES_PROVISION_PASSWORD', ''),
        'admin_database' => env('POSTGRES_PROVISION_ADMIN_DB', 'postgres'),
    ],

    /**
     * WHMCS provisioning modülü → panel REST (Bearer paylaşımlı gizli anahtar).
     * @see integrations/whmcs/modules/servers/panelze/panelze.php
     */
    'whmcs_integration' => [
        'secret' => trim((string) env('PANELZE_WHMCS_SECRET', '')),
        /** WHMCS SSO sonrası tarayıcı yönlendirmesi (örn. https://panel.example.com/admin) */
        'sso_redirect_base' => rtrim(trim((string) env('PANELZE_SSO_PANEL_URL', '')), '/'),
    ],
];
