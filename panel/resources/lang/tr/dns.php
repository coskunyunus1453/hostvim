<?php

return [
    'created' => 'DNS kaydı oluşturuldu',
    'deleted' => 'DNS kaydı silindi',
    'zone_export_panel_records' => 'yalnızca panel kayıtları',
    'zone_export_soa_hint' => 'Sunucuda BIND9 etkinse SOA/NS otomatik yazılır; registrar NS yönlendirmesi gerekir.',
    'bootstrap_done' => 'Varsayılan DNS kayıtları eklendi',
    'bootstrap_failed' => 'Varsayılan DNS kayıtları eklenemedi',
    'settings_saved' => 'DNS sunucu ayarları kaydedildi',
    'settings_saved_bind' => 'DNS ayarları kaydedildi ve BIND güncellendi (:zones zone)',
    'bind_sync_failed' => 'DNS ayarları kaydedildi ancak BIND güncellenemedi. Birkaç saniye sonra tekrar deneyin veya sunucuda: sudo panelze-bind-sync',
    'apex_ns_managed' => 'Kök (@) NS kaydı panel tarafından otomatik yönetilir; buradan eklemeyin.',
    'a_must_be_ipv4' => 'A kaydı geçerli bir IPv4 adresi olmalıdır (ör. 203.0.113.10).',
    'aaaa_must_be_ipv6' => 'AAAA kaydı geçerli bir IPv6 adresi olmalıdır.',
    'ttl_range' => 'TTL 60 ile 604800 saniye arasında olmalıdır.',
    'value_too_long' => 'Değer en fazla :max karakter olabilir.',
    'priority_required' => 'MX ve SRV kayıtları için öncelik zorunludur.',
    'priority_range' => 'Öncelik 0 ile 65535 arasında olmalıdır.',
    'cname_invalid' => 'CNAME değeri geçerli bir hedef alan adı olmalıdır.',
];
