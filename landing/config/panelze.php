<?php

return [
    /** Git deposu (install.sh / remote-install varsayılanı). */
    'repo_url' => env('PANELZE_REPO_URL', 'https://github.com/coskunyunus1453/hostvim.git'),

    'repo_branch' => env('PANELZE_REPO_BRANCH', 'main'),

    /** Panel + engine kurulum kökü. */
    'install_home' => env('PANELZE_INSTALL_HOME', '/var/www/panelze'),

    /** Tek satır kısa domain: curl -fsSL … | bash */
    'install_one_liner_url' => env('PANELZE_GET_INSTALL_URL', 'https://get.panelze.sh'),

    /** Tam uzaktan kurulum (git clone + install-production). */
    'install_remote_url' => env('PANELZE_INSTALL_REMOTE_URL', 'https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/bootstrap/remote-install.sh'),

    /** Community kurulum betiği. */
    'install_community_script' => env(
        'PANELZE_INSTALL_COMMUNITY_SCRIPT',
        'https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install-community.sh'
    ),

    /** Pro (lisanslı) kurulum betiği. */
    'install_pro_script' => env(
        'PANELZE_INSTALL_PRO_SCRIPT',
        'https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install-pro.sh'
    ),

    /** Ortak kurulum motoru (install-community içinden çağrılır). */
    'install_motor_script' => env(
        'PANELZE_INSTALL_MOTOR_SCRIPT',
        'https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install.sh'
    ),

    /** Güvenli güncelleme (siteler + DB korunur). */
    'install_update_community_script' => env(
        'PANELZE_INSTALL_UPDATE_COMMUNITY_SCRIPT',
        'https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install-update-community.sh'
    ),

    'install_update_pro_script' => env(
        'PANELZE_INSTALL_UPDATE_PRO_SCRIPT',
        'https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install-update-pro.sh'
    ),

    /** İlk admin bilgisi dosyası (install.sh sonrası). */
    'admin_login_file' => env('PANELZE_ADMIN_LOGIN_FILE', '/root/panelze-admin-login.txt'),
];
