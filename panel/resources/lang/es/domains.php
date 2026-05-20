<?php

return array_replace_recursive(require __DIR__.'/../en/domains.php', [
    'stack_alert_dismissed' => 'Alerta de sitio cerrada.',
    'stack_alert_open_hint' => 'Problemas de configuración detectados. Abra el panel para corregir.',
    'stack_summary' => 'Detectado: :profile (:runtime) — Servidor: :server — Confianza: :confidence',
    'stack_alert_title' => ':domain — :profile (:count problema(s))',
    'stack_alert_more' => '…y :count problema(s) más',
    'stack_issues' => [
        'missing_index' => 'Archivo de entrada no encontrado: :path',
        'missing_path' => 'Falta elemento :profile: :path',
        'incomplete_install' => 'Instalación :profile incompleta (:count elementos faltantes).',
        'docroot_mismatch' => 'Raíz de documento incorrecta. Recomendado: :recommended (actual: :current).',
        'stale_user_ini' => 'Un .user.ini antiguo puede bloquear PHP con ruta obsoleta.',
        'node_reverse_proxy' => 'Node.js requiere proxy inverso y proceso de aplicación.',
        'nginx_perf_optional' => 'Se recomienda preset de rendimiento Nginx.',
        'apache_htaccess_ok' => '.htaccess detectado; Apache es adecuado.',
        'ols_active' => 'OpenLiteSpeed activo; verifique reglas compatibles.',
    ],
]);
