<?php

namespace App\Services\Ai;

/**
 * Büyük dosya/klasör sınıflandırma + silinebilirlik + du/find çıktı parse.
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
     *   tip: string,
     *   kind: 'file'|'dir'
     * }
     */
    public function assess(string $absolutePath, ?int $bytes = null, ?bool $isDir = null): array
    {
        $path = rtrim(str_replace('\\', '/', $absolutePath), '/');
        if ($path === '') {
            return $this->result('other', 'Diğer', 'Boş yol.', self::RISK_BLOCKED, false, false, 'Geçersiz yol.', '', 'file');
        }
        $norm = strtolower($path);
        $base = strtolower(basename($path));
        $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
        $dir = $isDir;
        if ($dir === null) {
            $dir = is_dir($path) && ! is_link($path);
        }
        $kind = $dir ? 'dir' : 'file';

        // Sistem / kritik yollar (dosya veya dizin)
        if ($this->isSystemCritical($norm, $base, $ext)) {
            return $this->result(
                'system',
                'Sistem / kritik',
                'Swap, MySQL, konteyner veya sistem alanı. Silmek sunucuyu bozar.',
                self::RISK_BLOCKED,
                false,
                false,
                'Sistem yolu — PanelZeka silmez.',
                'Bunları yalnızca bilinçli sistem bakımında yönetin.',
                $kind
            );
        }

        if ($this->isBlockedPath($norm, $base, $ext)) {
            return $this->result(
                'critical',
                'Kritik / yapılandırma',
                'Yapılandırma, anahtar veya sistem dosyası; silinirse site/sunucu bozulabilir.',
                self::RISK_BLOCKED,
                false,
                false,
                'Hassas dosya — PanelZeka bu yolu silmez.',
                'İçeriği File Manager veya yedekten kontrol edin; silmeyin.',
                $kind
            );
        }

        // Hosting site kök klasörü (…/data/www/example.com) — is_dir olmasa da path ile engelle
        if ($this->isHostingSiteRoot($path, $norm)) {
            return $this->result(
                'site_root',
                'Web sitesi kökü',
                'Tüm site dizini. Silmek domain’i ve tüm dosyaları yok eder.',
                self::RISK_BLOCKED,
                false,
                false,
                'Site kökü silinemez — File Manager / Domain silme kullanın.',
                'Disk açmak için site içindeki büyük dosyaları listeleyin (list_largest_files).',
                'dir'
            );
        }

        // Klasör: yalnızca bilinen güvenli türler
        if ($dir) {
            if (
                preg_match('#/(cache|caches|tmp|temp|\.cache|node_modules|vendor/[^/]+/[^/]+)/?$#', $norm)
                || str_contains($norm, 'flutter_sdk')
                || str_contains($norm, '/.git/objects')
            ) {
                return $this->result(
                    'cache_dir',
                    'Önbellek / SDK klasörü',
                    'Yeniden üretilebilir önbellek veya SDK dizini.',
                    self::RISK_CAUTION,
                    true,
                    true,
                    'Klasör — onay ile trash’e alınabilir.',
                    'Site kökü değil; yine de yedek/onay gerekir.',
                    'dir'
                );
            }

            return $this->result(
                'directory',
                'Klasör',
                'Genel dizin. Toplu silme riskli; önce içindeki büyük dosyalara bakın.',
                self::RISK_BLOCKED,
                false,
                false,
                'Genel klasör silinemez — dosya bazında silin.',
                'PanelZeka’da list_largest_files ile bu dizin altında dosya tarayın.',
                'dir'
            );
        }

        // Ollama / LLM blob
        if (str_contains($norm, '/.ollama/') || str_contains($norm, '/ollama/')) {
            return $this->result(
                'ollama',
                'Ollama model blob',
                'Yerel LLM model parçası. Silmek modeli yeniden indirtir.',
                self::RISK_CAUTION,
                true,
                true,
                'Model blob — kullanılmıyorsa silinebilir.',
                'Modeli tekrar çekmeniz gerekir.',
                'file'
            );
        }

        // ML / torch / büyük model ağırlıkları
        if (
            preg_match('#/(nc_app|nc_build|torch|models?)/#', $norm)
            || in_array($ext, ['pt', 'pth', 'onnx', 'safetensors', 'gguf'], true)
            || str_contains($base, 'finetune')
            || str_contains($base, 'weights')
            || str_contains($base, 'checkpoint')
            || ($ext === 'bin' && (str_contains($norm, 'model') || str_contains($norm, 'ollama')))
        ) {
            return $this->result(
                'ml_weights',
                'Yapay zekâ / model dosyası',
                'Eğitim veya çıkarım ağırlıkları / model checkpoint. Yeniden üretmek uzun sürebilir.',
                self::RISK_CAUTION,
                true,
                true,
                'Büyük model dosyası — yalnızca artık kullanılmıyorsa silin.',
                'Önce yedek alın; trash’e taşınır, kalıcı silinmez.',
                'file'
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
                'Yedek varsa ve uygulama kapatıldıysa trash’e taşıyabilirsiniz.',
                'file'
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
                'Mümkünse `git gc --aggressive` kullanın; doğrudan silmek repoyu bozabilir.',
                'file'
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
                'Build pipeline kullanıyorsanız sonra SDK’yı yeniden kurun.',
                'file'
            );
        }

        // node_modules / vendor binaries / caches
        if (
            str_contains($norm, '/node_modules/')
            || (str_contains($norm, '/vendor/') && in_array($ext, ['so', 'dll', 'dylib'], true))
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
                'Silmeden önce siteyi bakım moduna almanız gerekmez; trash kullanılır.',
                'file'
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
                'Gerekirse önce son satırları inceleyin; trash’e taşınır.',
                'file'
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
                'Son yedek değilse trash’e taşıyabilirsiniz.',
                'file'
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
                'CDN/yedek kopyası yoksa silmeyin.',
                'file'
            );
        }

        // Docker / VM images (non-system path)
        if (in_array($ext, ['qcow2', 'vmdk', 'vdi', 'img'], true)) {
            return $this->result(
                'image',
                'Disk / konteyner imajı',
                'Sanal disk veya imaj dosyası.',
                self::RISK_CAUTION,
                true,
                true,
                'İmaj dosyası — kullanımda olmadığından emin olun.',
                'Çalışan konteyner/VM bağlıysa silmeyin.',
                'file'
            );
        }

        // PHP / source — blocked
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
                'File Manager’dan bilinçli düzenleyin.',
                'file'
            );
        }

        // Hosting altında bilinmeyen dosya — caution + deletable
        if (preg_match('#/(data/www|public_html|var/www)/#', $norm)) {
            $sizeHint = $bytes !== null && $bytes > 500_000_000 ? ' Çok büyük (>500 MB).' : '';

            return $this->result(
                'hosting_file',
                'Hosting dosyası',
                'Site dizinindeki büyük dosya.'.$sizeHint,
                self::RISK_CAUTION,
                true,
                true,
                'Hosting dosyası — onay ile trash’e alınabilir.',
                'Ne işe yaradığını doğruladıktan sonra silin.',
                'file'
            );
        }

        return $this->result(
            'other',
            'Diğer dosya',
            'Tür otomatik sınıflandırılamadı. Silmeden önce doğrulayın.',
            self::RISK_CAUTION,
            true,
            true,
            'Bilinmeyen tür — onay gerekir.',
            'İçeriği ve yolu okuyup emin olduktan sonra trash’e alın.',
            'file'
        );
    }

    /**
     * du -sh / find size listing çıktısını parse et.
     *
     * @return list<array{path: string, bytes: int, size_human: string, is_dir?: bool}>
     */
    public function parseSizeListing(string $output): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $output) ?: [];
        $rows = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $bytes = 0;
            $human = '';
            $path = '';

            // du: "29G\t/path" | "29G /path" | "1.2G\t/path"
            if (preg_match('/^([0-9]+(?:\.[0-9]+)?)([KMGTPE])i?B?\s+(\/.+)$/i', $line, $m)) {
                $human = $m[1].strtoupper($m[2]);
                $path = trim($m[3]);
                $bytes = $this->humanSizeToBytes($human);
            } elseif (preg_match('/^([0-9]+)\t(\/.+)$/', $line, $m)) {
                $bytes = (int) $m[1];
                $path = trim($m[2]);
                $human = $this->formatBytes($bytes);
            } else {
                continue;
            }

            $path = rtrim(str_replace('\\', '/', $path), '/');
            if ($path === '' || ! str_starts_with($path, '/') || strtolower(basename($path)) === 'total') {
                continue;
            }

            $rows[] = [
                'path' => $path,
                'bytes' => $bytes,
                'size_human' => $human !== '' ? $human : $this->formatBytes($bytes),
            ];
        }

        // En az 2 satır — tek satırlık rastgele çıktı false positive olmasın
        if (count($rows) < 2) {
            return [];
        }

        return $rows;
    }

    /**
     * @param  list<array{path: string, bytes?: int, size_human?: string, is_dir?: bool}>  $files
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
            $isDir = array_key_exists('is_dir', $f) ? (bool) $f['is_dir'] : null;
            if ($isDir === null && is_dir($path)) {
                $isDir = true;
            }
            $assess = $this->assess($path, $bytes, $isDir);
            $out[] = array_merge($f, [
                'name' => basename($path),
                'is_dir' => (bool) ($isDir || (($assess['kind'] ?? '') === 'dir')),
                'assessment' => $assess,
            ]);
        }

        return $out;
    }

    /**
     * Komut çıktısından large_files UI verisi üret (yeterli satır varsa).
     *
     * @return array{files: list<array<string, mixed>>, output: string}|null
     */
    public function tryBuildLargeFilesFromOutput(string $output): ?array
    {
        $parsed = $this->parseSizeListing($output);
        if ($parsed === []) {
            return null;
        }
        $files = $this->enrichMany($parsed);
        $text = "Boyut listesi (".count($files)." öğe) — tür ve silinebilirlik kartlarda.\n";
        foreach ($files as $i => $f) {
            $a = is_array($f['assessment'] ?? null) ? $f['assessment'] : [];
            $text .= sprintf(
                "%d) %s — %s [%s · %s]\n",
                $i + 1,
                $f['size_human'] ?? '',
                $f['path'] ?? '',
                $a['category_label'] ?? '?',
                $a['risk'] ?? '?'
            );
        }

        return [
            'files' => $files,
            'output' => mb_substr(trim($text), 0, 32_000),
        ];
    }

    public function humanSizeToBytes(string $human): int
    {
        $h = strtoupper(trim($human));
        $h = preg_replace('/\s+/', '', $h) ?? $h;
        $h = preg_replace('/IB$/', 'B', $h) ?? $h;
        if (preg_match('/^([0-9]+(?:\.[0-9]+)?)([KMGTPE])B?$/', $h, $m)) {
            $n = (float) $m[1];
            $mul = match ($m[2]) {
                'K' => 1024,
                'M' => 1024 ** 2,
                'G' => 1024 ** 3,
                'T' => 1024 ** 4,
                'P' => 1024 ** 5,
                'E' => 1024 ** 6,
                default => 1,
            };

            return (int) round($n * $mul);
        }
        if (preg_match('/^([0-9]+)$/', $h, $m)) {
            return (int) $m[1];
        }

        return 0;
    }

    public function formatBytes(int $bytes): string
    {
        $units = ['B', 'K', 'M', 'G', 'T'];
        $i = 0;
        $n = (float) max(0, $bytes);
        while ($n >= 1024 && $i < count($units) - 1) {
            $n /= 1024;
            $i++;
        }

        if ($i === 0) {
            return (string) (int) $n.'B';
        }

        return ($n >= 10 ? number_format($n, 0) : number_format($n, 1)).$units[$i];
    }

    private function isSystemCritical(string $norm, string $base, string $ext): bool
    {
        if (preg_match('#(^|/)swap(file)?[0-9]*$#', $norm) || str_starts_with($base, 'swapfile')) {
            return true;
        }
        if (str_contains($norm, '/var/lib/mysql/') || str_ends_with($ext, 'ibd') || in_array($ext, ['ibd', 'ibdata1', 'frm'], true)) {
            return true;
        }
        if (str_contains($norm, '/var/lib/containerd/') || str_contains($norm, '/var/lib/docker/')) {
            return true;
        }
        if (preg_match('#^/(bin|sbin|lib|lib64|boot|proc|sys|dev)(/|$)#', $norm)) {
            return true;
        }
        if (str_contains($norm, '/var/lib/postgresql/')) {
            return true;
        }

        return false;
    }

    private function isHostingSiteRoot(string $path, string $norm): bool
    {
        // .../data/www/example.com veya .../www/example.com (tek segment site adı)
        if (preg_match('#/(?:data/)?www/([^/]+)$#', $path)) {
            return true;
        }
        try {
            $web = rtrim(str_replace('\\', '/', (string) config('panelze.hosting_web_root', '')), '/');
        } catch (\Throwable) {
            $web = '';
        }
        if ($web !== '' && str_starts_with($path, $web.'/')) {
            $rel = substr($path, strlen($web) + 1);
            if ($rel !== '' && ! str_contains($rel, '/')) {
                return true;
            }
        }

        return false;
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
     *   tip: string,
     *   kind: string
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
        string $kind = 'file',
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
            'kind' => $kind,
        ];
    }
}
