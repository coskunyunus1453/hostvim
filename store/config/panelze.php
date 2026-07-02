<?php

return [

    'api_url' => rtrim(trim((string) env('PANELZE_API_URL', '')), '/'),

    'secret' => trim((string) env('PANELZE_STORE_SECRET', '')),

    'panel_login_url' => rtrim(trim((string) env('PANELZE_PANEL_URL', env('PANELZE_STORE_PANEL_URL', ''))), '/'),

    'store_account_url' => rtrim(trim((string) env('HOSTVIM_STORE_ACCOUNT_URL', env('APP_URL', ''))), '/').'/hesabim',

    // Panelde hosting hesabi olusturma (fulfill) agir bir islemdir (engine site kurulumu
    // ~40-60 sn surebilir). Dusuk timeout ConnectionException uretir; bu durumda panel
    // provizyonu tamamlasa bile store siparisi "failed" gorunur (tutarsizlik). Bu yuzden
    // varsayilan 120 sn. Not: queue worker --timeout degeri bundan BUYUK olmalidir.
    'timeout' => max(5, (int) env('PANELZE_API_TIMEOUT', 120)),

    /** Aynı sunucuda panel API için http://127.0.0.1 kullanımına izin ver */
    'allow_internal_http' => filter_var(env('PANELZE_API_ALLOW_INTERNAL_HTTP', true), FILTER_VALIDATE_BOOLEAN),

];
