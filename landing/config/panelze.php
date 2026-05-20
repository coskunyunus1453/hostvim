<?php

return [
    /** Tek satır kurulum: curl -fsSL … | bash */
    'install_one_liner_url' => env('PANELZE_GET_INSTALL_URL', 'https://get.panelze.sh'),

    /** Tam uzaktan kurulum betiği (repo + install-production) */
    'install_remote_url' => env('PANELZE_INSTALL_REMOTE_URL', 'https://install.panelze.com/remote-install.sh'),

    /** Community kurulum betiği (ham GitHub; get.panelze.sh bunu çağırır) */
    'install_community_script' => env(
        'PANELZE_INSTALL_COMMUNITY_SCRIPT',
        'https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install-community.sh'
    ),
];
