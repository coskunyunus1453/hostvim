<?php

return array_replace_recursive(require __DIR__.'/../en/domains.php', [
    'stack_alert_dismissed' => 'Alerte site fermée.',
    'stack_alert_open_hint' => 'Problèmes de configuration détectés. Ouvrez le panneau pour corriger.',
    'stack_summary' => 'Détecté : :profile (:runtime) — Serveur : :server — Confiance : :confidence',
    'stack_alert_title' => ':domain — :profile (:count problème(s))',
    'stack_alert_more' => '…et :count autre(s) problème(s)',
    'stack_issues' => [
        'missing_index' => 'Fichier d’entrée introuvable : :path',
        'missing_path' => 'Élément :profile manquant : :path',
        'incomplete_install' => 'Installation :profile incomplète (:count éléments manquants).',
        'docroot_mismatch' => 'Racine document incorrecte. Recommandé : :recommended (actuel : :current).',
        'stale_user_ini' => 'Ancien .user.ini peut bloquer PHP avec un chemin obsolète.',
        'node_reverse_proxy' => 'Node.js nécessite un reverse proxy et un processus applicatif.',
        'nginx_perf_optional' => 'Preset de performance Nginx recommandé.',
        'apache_htaccess_ok' => '.htaccess détecté ; Apache adapté.',
        'ols_active' => 'OpenLiteSpeed actif ; vérifiez la compatibilité des règles.',
    ],
]);
