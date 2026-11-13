<?php

namespace App\Services\Ai;

/**
 * Büyük dosya sınıflandırma + silinebilirlik değerlendirmesi (PanelZeka).
 */
class PanelZekaLargeFileAdvisor
{
    public const RISK_SAFE = 'safe';

    public const RISK_CAUTION = 'caution';

    public const RISK_BLOCKED = 'blocked';

    /**
     * @return array{
     *   category: string,
     *   category_label: string,
     *   summary: string,
     *   risk: self::RISK_*,
     *   deletable: bool,
     *   requires_confirm: bool,
     *   reason: string,
     *   tip: string
     * }
     */
    public function assess(string $absolutePath, ?int $bytes = null): array
    {
        $path = str_replace('\\', '/', $absolutePath);
        $norm = strtolower($path);
        $base = strtolower(basename($path));
        $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));

        if ($this->isBlockedPath($norm, $base, $ext)) {
            return $this->result(
                'critical',
                'Kritik / yapılandırma',
                'Yapılandırma, anahtar veya sistem dosyası; silinirse site/sunucu bozulabilir.',
                self::RISK_BLOCKED,
                false,
                false,
                'Hassas dosya — PanelZeka bu yolu silmez.',
                'İçeriği File Manager veya yedekten kontrol edin; silmeyin.'
            );
        }

        // ML / torch / büyük model ağırlıkları
        if (
            preg_match('#/(nc_app|nc_build|torch|models?)/#', $norm)
            || in_array($ext, ['pt', 'pth', 'onnx', 'safetensors', 'gguf', 'bin'], true)
            || str_contains($base, 'finetune')
            || str_contains($base, 'weights')
            || str_contains($base, 'checkpoint')
        ) {
            return $this->result(
                'ml_weights',
                'Yapay zekâ / model dosyası',
                'Eğitim veya çıkarım ağırlıkları / model checkpoint. Yeniden üretmek uzun sürebilir.',
                self::RISK_CAUTION,
                true,
                true,
                'Büyük model dosyası — yalnızca artık kullanılmıyorsa silin.',
                'Önce yedek alın; trash’e taşınır, kalıcı silinmez.'
            );
        }

        // SQLite / knowledge DB
        if (
            in_array($ext, ['db', 'sqlite', 'sqlite3'], true)
            || str_contains($base, 'knowledge')
            || str_ends_with($base, '.db-wal')
            || str_ends_with($base, '.db-shm')
        ) {
            return $this->result(
                'database',
                'Yerel veritabanı',
                'SQLite / uygulama veritabanı. Silmek uygulama verisini yok eder.',
                self::RISK_CAUTION,
                true,
                true,
                'Veritabanı dosyası — onay olmadan silinmez.',
                'Yedek varsa ve uygulama kapatıldıysa trash’e taşıyabilirsiniz.'
            );
        }

        // Git pack / objects
        if (str_contains($norm, '/.git/') || str_ends_with($base, '.pack') || (str_ends_with($base, '.idx') && str_contains($norm, '/.git/'))) {
            return $this->result(
                'git',
                'Git depo nesnesi',
                'Git pack/object — depo bütünlüğü bozulabilir; `git gc` tercih edilir.',
                self::RISK_CAUTION,
                true,
                true,
                'Git içi dosya — dikkatli silin.',
                'Mümkünse `git gc --aggressive` kullanın; doğrudan silmek repoyu bozabilir.'
            );
        }

        // Flutter / Android / SDK
        if (
            preg_match('#/(flutter|android|gradle|ndk|cmdline-tools)/#', $norm)
            || str_contains($norm, 'flutter_sdk')
            || str_contains($norm, 'worker_flutter')
        ) {
            return $this->result(
                'sdk',
                'SDK / derleme aracı',
                'Flutter/Android SDK veya derleme önbelleği. Yeniden indirilebilir.',
                self::RISK_SAFE,
                true,
                false,
                'SDK — güvenle trash’e alınabilir (yeniden kurulum gerekir).',
                'Build pipeline kullanıyorsanız sonra SDK’yı yeniden kurun.'
            );
        }

        // node_modules / vendor binaries / caches
        if (
            str_contains($norm, '/node_modules/')
            || str_contains($norm, '/vendor/') && in_array($ext, ['so', 'dll', 'dylib'], true)
            || preg_match('#/(cache|caches|tmp|temp|\.cache)/#', $norm)
        ) {
            return $this->result(
                'cache_deps',
                'Bağımlılık / önbellek',
                'Paket veya önbellek dosyası. Genelde `composer/npm install` ile geri gelir.',
                self::RISK_SAFE,
                true,
                false,
                'Önbellek/bağımlılık — silinebilir.',
                'Silmeden önce siteyi bakım moduna almanız gerekmez; trash kullanılır.'
            );
        }

        // Logs
        if (
            in_array($ext, ['log', 'out'], true)
            || str_contains($norm, '/log/')
            || str_contains($norm, '/logs/')
            || preg_match('#\.(log|out)\.\d+$#', $base)
        ) {
            return $this->result(
                'log',
                'Log dosyası',
                'Uygulama veya sunucu günlüğü. Disk açmak için güvenli aday.',
                self::RISK_SAFE,
                true,
                false,
                'Log — güvenle temizlenebilir.',
                'Gerekirse önce son satırları inceleyin; trash’e taşınır.'
            );
        }

        // Backups / archives
        if (
            in_array($ext, ['zip', 'tar', 'gz', 'tgz', 'bz2', 'xz', '7z', 'rar', 'bak', 'sql'], true)
            || str_contains($norm, '/backup')
            || str_contains($base, 'backup')
            || str_contains($base, 'dump')
        ) {
            return $this->result(
                'backup',
                'Yedek / arşiv',
                'Yedek veya sıkıştırılmış arşiv. Eski kopyalar disk yerini yer.',
                self::RISK_CAUTION,
                true,
                true,
                'Yedek dosyası — başka kopya olduğundan emin olun.',
                'Son yedek değilse trash’e taşıyabilirsiniz.'
            );
        }

        // Media / uploads
        if (
            in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm', 'mp3', 'wav', 'flac', 'iso', 'dmg'], true)
            || preg_match('#/(uploads?|media|storage/app/public)/#', $norm)
        ) {
            return $this->result(
                'media',
                'Medya / yükleme',
                'Video, ses veya kullanıcı yüklemesi. Site içeriği olabilir.',
                self::RISK_CAUTION,
                true,
                true,
                'Medya — kullanılmadığından emin olun.',
                'CDN/yedek kopyası yoksa silmeyin.'
            );
        }

        // Docker / VM images
        if (in_array($ext, ['qcow2', 'vmdk', 'vdi', 'img'], true) || str_contains($norm, '/docker/')) {
            return $this->result(
                'image',
                'Disk / konteyner imajı',
                'Sanal disk veya konteyner katmanı; yeniden çekilebilir veya kritik olabilir.',
                self::RISK_CAUTION,
                true,
                true,
                'İmaj dosyası — kullanımda olmadığından emin olun.',
                'Çalışan konteyner/VM bağlıysa silmeyin.'
            );
        }

        // PHP / source — blocked for delete by default
        if (in_array($ext, ['php', 'js', 'ts', 'tsx', 'jsx', 'vue', 'css', 'scss', 'html', 'json', 'yml', 'yaml'], true)
            && ! str_contains($norm, '/cache/')
            && ! str_contains($norm, '/node_modules/')
        ) {
            return $this->result(
                'source',
                'Kaynak kod / yapılandırma',
                'Uygulama kaynak dosyası. Yanlışlıkla silmek siteyi kırar.',
                self::RISK_BLOCKED,
                false,
                false,
                'Kaynak dosya — PanelZeka silmez.',
                'File Manager’dan bilinçli düzenleyin.'
            );
        }

        $sizeHint = $bytes !== null && $bytes > 500_000_000
            ? ' Çok büyük (>500 MB).'
            : '';

        return $this->result(
            'other',
            'Diğer dosya',
            'Tür otomatik sınıflandırılamadı.'.$sizeHint.' Silmeden önce ne işe yaradığını doğrulayın.',
            self::RISK_CAUTION,
            true,
            true,
            'Bilinmeyen tür — onay gerekir.',
            'İçeriği ve yolu okuyup emin olduktan sonra trash’e alın.'
        );
    }

    /**
     * @param  list<array{path: string, bytes?: int, size_human?: string}>  $files
     * @return list<array<string, mixed>>
     */
    public function enrichMany(array $files): array
    {
        $out = [];
        foreach ($files as $f) {
            $path = (string) ($f['path'] ?? '');
            if ($path === '') {
                continue;
            }
            $bytes = isset($f['bytes']) ? (int) $f['bytes'] : null;
            $assess = $this->assess($path, $bytes);
            $out[] = array_merge($f, [
                'name' => basename($path),
                'assessment' => $assess,
            ]);
        }

        return $out;
    }

    private function isBlockedPath(string $norm, string $base, string $ext): bool
    {
        $denyNames = [
            '.env',
            'wp-config.php',
            'id_rsa',
            'id_dsa',
            'id_ed25519',
            'authorized_keys',
            'database.php',
            'composer.lock',
            'package-lock.json',
        ];
        if (in_array($base, $denyNames, true)) {
            return true;
        }
        if (in_array($ext, ['pem', 'key', 'crt', 'p12', 'pfx'], true)) {
            return true;
        }
        if (preg_match('#(^|/)\.env(\.|$)#', $norm)) {
            return true;
        }
        // Panel / sistem kökleri
        if (preg_match('#/(etc|boot|usr/bin|usr/sbin|lib/systemd)/#', $norm)) {
            return true;
        }
        if (str_contains($norm, '/panel/vendor/') || str_contains($norm, '/panel/.env')) {
            return true;
        }

        return false;
    }

    /**
     * @return array{
     *   category: string,
     *   category_label: string,
     *   summary: string,
     *   risk: string,
     *   deletable: bool,
     *   requires_confirm: bool,
     *   reason: string,
     *   tip: string
     * }
     */
    private function result(
        string $category,
        string $label,
        string $summary,
        string $risk,
        bool $deletable,
        bool $requiresConfirm,
        string $reason,
        string $tip,
    ): array {
        return [
            'category' => $category,
            'category_label' => $label,
            'summary' => $summary,
            'risk' => $risk,
            'deletable' => $deletable,
            'requires_confirm' => $requiresConfirm || $risk === self::RISK_CAUTION,
            'reason' => $reason,
            'tip' => $tip,
        ];
    }
}
