<?php

return [
    'created' => 'DNS record created',
    'deleted' => 'DNS record deleted',
    'zone_export_panel_records' => 'panel records only',
    'zone_export_soa_hint' => 'With BIND9 on the server, SOA/NS are written automatically; delegate NS at your registrar.',
    'bootstrap_done' => 'Default DNS records added',
    'bootstrap_failed' => 'Could not add default DNS records',
    'settings_saved' => 'DNS server settings saved',
    'settings_saved_bind' => 'DNS settings saved and BIND updated (:zones zones)',
    'bind_sync_failed' => 'DNS settings were saved but BIND could not be updated. Retry in a few seconds or run: sudo panelze-bind-sync',
    'apex_ns_managed' => 'Apex (@) NS records are managed automatically by the panel.',
    'a_must_be_ipv4' => 'A record value must be a valid IPv4 address.',
    'aaaa_must_be_ipv6' => 'AAAA record value must be a valid IPv6 address.',
    'ttl_range' => 'TTL must be between 60 and 604800 seconds.',
    'value_too_long' => 'Value may be at most :max characters.',
    'priority_required' => 'Priority is required for MX and SRV records.',
    'priority_range' => 'Priority must be between 0 and 65535.',
    'cname_invalid' => 'CNAME value must be a valid target hostname.',
];
