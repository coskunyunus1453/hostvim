<?php

return [
    'queued' => 'Yedek kuyruğa alındı',
    'deleted' => 'Yedek silindi',
    'restore_started' => 'Geri yükleme başlatıldı',
    'restore_no_engine_id' => 'Bu yedeğin engine kaydı yok; yeni yedek alıp ondan geri yükleyin.',
    'remote_restore_missing' => 'Remote restore için destination ve backup set gerekli.',
    'remote_restore_invalid_path' => 'Geçersiz yedek yolu (dizin çıkışına izin verilmez).',
    'remote_restore_not_found' => 'Uzak hedefte yedek dosyası bulunamadı.',
    'remote_restore_download_failed' => 'Yedek uzak hedeften indirilemedi.',
    'remote_restore_destination_inactive' => 'Seçilen yedek hedefi etkin değil.',
    'destination_saved' => 'Backup hedefi kaydedildi',
    'schedule_saved' => 'Backup planı kaydedildi',
    'synced' => 'Yedek remote hedefe senkronlandı',
    'download_unavailable' => 'İndirilebilir yedek dosyası bulunamadı.',
    'upload_required' => 'Yedek arşivi (.tar.gz) gerekli.',
    'upload_failed' => 'Dosya yüklenemedi.',
    'google_drive_not_configured' => 'Google Drive OAuth henüz yapılandırılmamış. Panelze.com → Panel entegrasyonları veya sunucu .env.',
    'google_drive_state_invalid' => 'OAuth oturumu geçersiz veya süresi doldu. Tekrar deneyin.',
    'google_drive_connected' => 'Google Drive bağlandı.',
    'google_drive_disconnected' => 'Google Drive bağlantısı kaldırıldı.',
    'google_drive_token_expired' => 'Google Drive oturumu süresi doldu. Yeniden bağlanın.',
];
