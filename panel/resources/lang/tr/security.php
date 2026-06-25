<?php

return [
    'access_denied' => 'Güvenlik merkezine erişim yetkiniz yok.',
    'bootstrap_done' => 'Varsayılan güvenlik katmanları uygulandı.',
    'hint_sudo' => 'Sunucuda /usr/local/sbin/panelze-security (veya eski panelsar-security) ve sudoers NOPASSWD kuralını kontrol edin. Gerekirse deploy/bootstrap/install-production.sh yeniden çalıştırın.',
    'hint_fim_baseline' => 'FIM taraması için önce baseline oluşturun, ardından tekrar tarama başlatın.',
    'hint_modsecurity' => 'ModSecurity yapılandırması bulunamadı. Sunucuda Apache + modsecurity2 kurulu mu kontrol edin.',
    'hint_service' => 'İlgili servis paketi (fail2ban/modsecurity/clamav) yüklü veya etkin olmayabilir.',
    'hint_clamav_path' => 'ClamAV hedefi yalnızca /var/www veya /home altında olabilir (veya PANELZE_WEB_ROOT ile tanımlı web kökü).',
    'hint_maldet' => 'Linux Malware Detect (maldet) sunucuda kurulu değil. İsterseniz LMD kurun veya yalnızca ClamAV taramasını kullanın.',
    'hint_firewall' => 'Güvenlik duvarı kuralı iptables üzerinden uygulanamadı. Sunucuda iptables kurulu olduğundan ve panelze-security scriptinin güncel olduğundan emin olun.',
    'hint_invalid_input' => 'Geçersiz güvenlik kuralı girdisi. Profil/mod/domain/target alanlarını kontrol edip tekrar deneyin.',
    'operation_failed' => 'Güvenlik işlemi başarısız.',
];
