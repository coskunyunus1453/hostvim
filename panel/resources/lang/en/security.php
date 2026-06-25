<?php

return [
    'access_denied' => 'You do not have access to the security center.',
    'bootstrap_done' => 'Default security layers have been applied.',
    'hint_sudo' => 'Check /usr/local/sbin/panelze-security (or legacy panelsar-security) and the sudoers NOPASSWD rule on the server. Re-run deploy/bootstrap/install-production.sh if needed.',
    'hint_fim_baseline' => 'Create an FIM baseline first, then run the scan again.',
    'hint_modsecurity' => 'ModSecurity configuration was not found. Verify Apache + modsecurity2 is installed on the server.',
    'hint_service' => 'The related service package (fail2ban/modsecurity/clamav) may not be installed or enabled.',
    'hint_clamav_path' => 'ClamAV target must be under /var/www or /home (or the web root defined by PANELZE_WEB_ROOT).',
    'hint_maldet' => 'Linux Malware Detect (maldet) is not installed on the server. Install LMD or use ClamAV scan only.',
    'hint_firewall' => 'Firewall rule could not be applied via iptables. Ensure iptables is installed and panelze-security script is up to date.',
    'hint_invalid_input' => 'Invalid security rule input. Check profile/mode/domain/target fields and try again.',
    'operation_failed' => 'Security operation failed.',
];
