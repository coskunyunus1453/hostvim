<?php

return array_replace_recursive(require __DIR__.'/../en/domains.php', [
    'stack_alert_dismissed' => 'Site-Warnung geschlossen.',
    'stack_alert_open_hint' => 'Site-Konfigurationsprobleme erkannt. Im Panel beheben.',
    'stack_summary' => 'Erkannt: :profile (:runtime) — Webserver: :server — Vertrauen: :confidence',
    'stack_alert_title' => ':domain — :profile (:count Problem(e))',
    'stack_alert_more' => '…und :count weitere Problem(e)',
    'stack_issues' => [
        'missing_index' => 'Einstiegsdatei fehlt: :path',
        'missing_path' => 'Fehlendes :profile-Element: :path',
        'incomplete_install' => ':profile-Installation wirkt unvollständig (:count fehlende Elemente).',
        'docroot_mismatch' => 'Document Root falsch. Empfohlen: :recommended (aktuell: :current).',
        'stale_user_ini' => 'Alte .user.ini kann PHP mit veraltetem Pfad blockieren.',
        'node_reverse_proxy' => 'Node.js benötigt Reverse Proxy und App-Prozess.',
        'nginx_perf_optional' => 'Nginx-Performance-Preset wird empfohlen.',
        'apache_htaccess_ok' => '.htaccess erkannt; Apache passt gut.',
        'ols_active' => 'OpenLiteSpeed aktiv; Regeln prüfen.',
    ],
]);
