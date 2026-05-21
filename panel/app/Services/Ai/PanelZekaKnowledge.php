<?php

namespace App\Services\Ai;

/**
 * PanelZeka — panel menüleri ve yönlendirme bilgisi (system prompt için).
 */
class PanelZekaKnowledge
{
    /**
     * @return list<array{path: string, title_tr: string, title_en: string, purpose_tr: string, admin_only?: bool, advanced?: bool}>
     */
    public static function routes(bool $isAdmin): array
    {
        $routes = [
            ['path' => '/dashboard', 'title_tr' => 'Kontrol Paneli', 'title_en' => 'Dashboard', 'purpose_tr' => 'Özet, kaynak grafikleri, hızlı durum'],
            ['path' => '/domains', 'title_tr' => 'Alan Adları', 'title_en' => 'Domains', 'purpose_tr' => 'Site ekleme, PHP sürümü, alt alan adı, alias, loglar'],
            ['path' => '/dns', 'title_tr' => 'DNS', 'title_en' => 'DNS', 'purpose_tr' => 'DNS kayıtları, zone export'],
            ['path' => '/databases', 'title_tr' => 'Veritabanları', 'title_en' => 'Databases', 'purpose_tr' => 'MySQL/PostgreSQL oluşturma, phpMyAdmin, yedek'],
            ['path' => '/email', 'title_tr' => 'E-posta', 'title_en' => 'Email', 'purpose_tr' => 'Posta kutusu, yönlendirici, webmail'],
            ['path' => '/files', 'title_tr' => 'Dosya Yöneticisi', 'title_en' => 'File Manager', 'purpose_tr' => 'Dosya düzenleme, izinler, zip'],
            ['path' => '/ftp', 'title_tr' => 'FTP', 'title_en' => 'FTP', 'purpose_tr' => 'FTP hesapları'],
            ['path' => '/ssl', 'title_tr' => 'SSL', 'title_en' => 'SSL', 'purpose_tr' => "Let's Encrypt, manuel sertifika, HTTPS yönlendirme"],
            ['path' => '/backups', 'title_tr' => 'Yedekler', 'title_en' => 'Backups', 'purpose_tr' => 'Yedek alma, geri yükleme, zamanlama'],
            ['path' => '/cron', 'title_tr' => 'Zamanlanmış Görevler', 'title_en' => 'Cron Jobs', 'purpose_tr' => 'Cron ekleme, şimdi çalıştır, loglar', 'advanced' => true],
            ['path' => '/monitoring', 'title_tr' => 'İzleme', 'title_en' => 'Monitoring', 'purpose_tr' => 'Sunucu CPU/RAM/disk, sağlık skoru', 'advanced' => true],
            ['path' => '/security', 'title_tr' => 'Güvenlik', 'title_en' => 'Security', 'purpose_tr' => 'Fail2ban, ModSecurity, ClamAV, firewall', 'advanced' => true],
            ['path' => '/installer', 'title_tr' => 'Uygulama Kurucu', 'title_en' => 'App Installer', 'purpose_tr' => 'WordPress vb. tek tık kurulum'],
            ['path' => '/node-apps', 'title_tr' => 'Node.js Uygulamaları', 'title_en' => 'Node Apps', 'purpose_tr' => 'PM2 ile Node projeleri', 'advanced' => true],
            ['path' => '/deploy', 'title_tr' => 'Deploy', 'title_en' => 'Deploy', 'purpose_tr' => 'Git deploy, sürüm geçmişi', 'advanced' => true],
            ['path' => '/ai-advisor', 'title_tr' => 'PanelZeka', 'title_en' => 'PanelZeka', 'purpose_tr' => 'AI asistan (bu sayfa)'],
            ['path' => '/settings', 'title_tr' => 'Hesap Ayarları', 'title_en' => 'Settings', 'purpose_tr' => 'Profil, şifre, 2FA, dil'],
        ];

        if ($isAdmin) {
            $routes = array_merge($routes, [
                ['path' => '/admin/users', 'title_tr' => 'Kullanıcılar', 'title_en' => 'Users', 'purpose_tr' => 'Müşteri hesapları', 'admin_only' => true],
                ['path' => '/admin/system', 'title_tr' => 'Sistem', 'title_en' => 'System', 'purpose_tr' => 'Sunucu metrikleri, servisler', 'admin_only' => true],
                ['path' => '/admin/server-settings', 'title_tr' => 'Sunucu Ayarları', 'title_en' => 'Server Settings', 'purpose_tr' => 'Genel sunucu yapılandırması', 'admin_only' => true],
                ['path' => '/admin/terminal', 'title_tr' => 'Terminal', 'title_en' => 'Terminal', 'purpose_tr' => 'Web terminal (dikkatli kullanım)', 'admin_only' => true],
                ['path' => '/admin/php-settings', 'title_tr' => 'PHP Ayarları', 'title_en' => 'PHP Settings', 'purpose_tr' => 'Global PHP-FPM/ini', 'admin_only' => true],
                ['path' => '/admin/webserver', 'title_tr' => 'Web Sunucusu', 'title_en' => 'Web Server', 'purpose_tr' => 'Nginx/Apache ayarları', 'admin_only' => true],
            ]);
        }

        return $routes;
    }

    public static function actionSchemaDoc(string $locale): string
    {
        if ($locale === 'en') {
            return <<<'DOC'
When you need the user to approve panel operations, append a fenced block ```hostvim-actions with JSON:
{
  "fixes": [{"domain_id": 1, "path": "relative/path", "content": "full file content", "summary": "why"}],
  "actions": [
    {"id": "unique-id", "type": "file_write", "title": "short label", "params": {"domain_id": 1, "path": "...", "content": "..."}},
    {"id": "db1", "type": "create_database", "title": "Create DB", "params": {"name": "mydb", "type": "mysql", "domain_id": 1}},
    {"id": "dom1", "type": "create_domain", "title": "Add site", "params": {"name": "example.com", "php_version": "8.2"}},
    {"id": "sec1", "type": "security_toggle", "title": "Enable Fail2ban", "params": {"feature": "fail2ban", "enabled": true}},
    {"id": "cmd1", "type": "run_command", "title": "Run PHP script", "params": {"command": "/usr/bin/php /path/to/spark list", "domain_id": 1}}
  ],
  "tips": ["optional navigation hints with panel paths like /ssl"]
}
All destructive or write operations MUST go in "actions" or "fixes" — the user approves before execution.
Never use shell operators (&&, |, >) in run_command. Use absolute paths. Admin-only: security_toggle, some server settings.
DOC;
        }

        return <<<'DOC'
Panel işlemi gerektiğinde ```hostvim-actions ile JSON ekle:
{
  "fixes": [{"domain_id": 1, "path": "göreli/yol", "content": "dosyanın tam yeni içeriği", "summary": "neden"}],
  "actions": [
    {"id": "benzersiz", "type": "file_write", "title": "kısa başlık", "params": {"domain_id": 1, "path": "...", "content": "..."}},
    {"id": "db1", "type": "create_database", "title": "Veritabanı oluştur", "params": {"name": "veritabani_adi", "type": "mysql", "domain_id": 1}},
    {"id": "dom1", "type": "create_domain", "title": "Site ekle", "params": {"name": "ornek.com", "php_version": "8.2"}},
    {"id": "sec1", "type": "security_toggle", "title": "Fail2ban aç", "params": {"feature": "fail2ban", "enabled": true}},
    {"id": "cmd1", "type": "run_command", "title": "Spark komutu", "params": {"command": "/usr/bin/php /yol/spark list", "domain_id": 1}}
  ],
  "tips": ["/ssl gibi panel yollarıyla yönlendirme ipuçları"]
}
Yazma/değiştirme işlemleri mutlaka "actions" veya "fixes" içinde olmalı — kullanıcı onaylamadan uygulanmaz.
run_command içinde &&, |, > kullanma; mutlak yol kullan. security_toggle ve bazı sunucu ayarları yalnızca admin.
DOC;
    }
}
