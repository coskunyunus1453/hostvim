<?php

/**
 * Müşteri arayüzünde görünen marka ve varsayılan teknik bilgiler.
 * Registrar API sağlayıcı adları burada yer almaz.
 */
return [
    'name' => env('HOSTVIM_BRAND_NAME', 'HostVim'),

    'default_nameservers' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('HOSTVIM_DEFAULT_NS', 'ns1.hostvim.com,ns2.hostvim.com'))
    ))),

    'support_email' => env('HOSTVIM_SUPPORT_EMAIL', 'destek@hostvim.com'),
];
