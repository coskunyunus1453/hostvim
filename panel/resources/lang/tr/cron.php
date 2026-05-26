<?php

return [
    'created' => 'Zamanlanmış görev oluşturuldu',
    'updated' => 'Zamanlanmış görev güncellendi',
    'deleted' => 'Zamanlanmış görev silindi',
    'run_success' => 'Görev başarıyla çalıştırıldı',
    'run_failed' => 'Görev çalıştırılamadı',
    'run_timeout' => 'Görev zaman aşımına uğradı (180 sn)',
    'invalid_schedule' => 'Tam olarak beş alan girin: dakika saat gün ay haftanın günü (boşlukla ayrılmış).',
    'schedule_in_command_field' => 'Zamanlama üst alana yazılmalı (örn. 0 * * * *). Komut kutusuna yalnızca çalıştırılacak satırı girin.',
    'command_empty' => 'Komut boş olamaz.',
    'command_too_long' => 'Komut en fazla 2000 karakter olabilir.',
    'command_no_multiline' => 'Komut tek satır olmalıdır.',
    'command_no_substitution' => 'Güvenlik: `$(...)` veya backtick ile komut enjeksiyonu kullanılamaz.',
    'command_forbidden_pattern' => 'Güvenlik: bu komut deseni izin verilmiyor (ör. pipe ile kabuk açma, rm -rf /).',
    'command_path_not_allowed' => 'Komuttaki dosya yolu yalnızca sizin site dizinleriniz veya /usr/bin gibi sistem araçları altında olmalı.:hint',
    'command_path_hint' => 'Örnek kök: :path',
    'database_not_ready' => 'Cron tablosu hazır değil. Sunucuda panel dizininde: php artisan migrate --force',
];
