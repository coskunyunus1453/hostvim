<?php

namespace App\Services\Ai;

use App\Models\Domain;
use App\Models\User;
use App\Services\EngineApiService;
use Illuminate\Support\Facades\File;

/**
 * PanelZeka güvenli silme çöpü: site .panelze-trash + merkezi .panelze-ai-trash.
 */
class PanelZekaTrashService
{
    public function __construct(
        private EngineApiService $engine,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(User $user, int $limit = 100): array
    {
        $limit = max(1, min(200, $limit));
        $items = [];

        $domains = $user->isAdmin()
            ? Domain::query()->orderBy('name')->get(['id', 'name', 'document_root', 'user_id'])
            : $user->domains()->orderBy('name')->get(['id', 'name', 'document_root', 'user_id']);

        foreach ($domains as $domain) {
            $list = $this->engine->listFilesResult($domain->name, '.panelze-trash/meta', 200, 0, 'mtime', 'desc');
            if (($list['error'] ?? null) !== null) {
                continue;
            }
            foreach ($list['entries'] ?? [] as $e) {
                $name = (string) ($e['name'] ?? '');
                if ($name === '' || ! str_ends_with($name, '.json') || ! empty($e['is_dir'])) {
                    continue;
                }
                $id = substr($name, 0, -5);
                try {
                    $raw = $this->engine->readFile($domain->name, '.panelze-trash/meta/'.$name);
                    $meta = json_decode($raw, true);
                } catch (\Throwable) {
                    continue;
                }
                if (! is_array($meta)) {
                    continue;
                }
                // PanelZeka kaynaklı veya son 14 gün (görünürlük)
                $source = (string) ($meta['source'] ?? '');
                $deletedAt = (string) ($meta['deleted_at'] ?? '');
                $recent = $deletedAt !== '' && strtotime($deletedAt) >= strtotime('-14 days');
                if ($source !== 'panelzeka_safe_delete' && ! $recent) {
                    continue;
                }

                $size = $this->itemSizeBytes($domain->name, $id);
                $items[] = [
                    'id' => $id,
                    'domain_id' => $domain->id,
                    'domain' => $domain->name,
                    'name' => (string) ($meta['name'] ?? $id),
                    'original_path' => (string) ($meta['original_path'] ?? ''),
                    'absolute_path' => (string) ($meta['absolute_path'] ?? ''),
                    'deleted_at' => $deletedAt,
                    'source' => $source !== '' ? $source : 'file_manager',
                    'location' => 'site_trash',
                    'size' => $size,
                    'size_human' => $this->formatBytes($size),
                ];
            }
        }

        // Merkezi AI trash
        $aiRoot = dirname(base_path()).'/data/.panelze-ai-trash';
        if (is_dir($aiRoot)) {
            foreach (scandir($aiRoot) ?: [] as $stamp) {
                if ($stamp === '.' || $stamp === '..') {
                    continue;
                }
                $dir = $aiRoot.'/'.$stamp;
                if (! is_dir($dir)) {
                    continue;
                }
                $metaPath = $dir.'/meta.json';
                $meta = is_file($metaPath) ? json_decode((string) file_get_contents($metaPath), true) : null;
                $meta = is_array($meta) ? $meta : [];
                $files = array_values(array_filter(scandir($dir) ?: [], fn ($f) => $f !== '.' && $f !== '..' && $f !== 'meta.json'));
                $bytes = 0;
                foreach ($files as $f) {
                    $p = $dir.'/'.$f;
                    if (is_file($p)) {
                        $bytes += (int) (filesize($p) ?: 0);
                    }
                }
                $items[] = [
                    'id' => 'ai:'.$stamp,
                    'domain_id' => null,
                    'domain' => null,
                    'name' => $files[0] ?? $stamp,
                    'original_path' => (string) ($meta['original_path'] ?? ''),
                    'absolute_path' => (string) ($meta['original_path'] ?? ''),
                    'deleted_at' => (string) ($meta['deleted_at'] ?? ''),
                    'source' => 'panelzeka_ai_trash',
                    'location' => 'ai_trash',
                    'size' => $bytes,
                    'size_human' => $this->formatBytes($bytes),
                ];
            }
        }

        usort($items, function ($a, $b) {
            return strcmp((string) ($b['deleted_at'] ?? ''), (string) ($a['deleted_at'] ?? ''));
        });

        return array_slice($items, 0, $limit);
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function destroy(User $user, string $id, ?int $domainId = null): array
    {
        if (str_starts_with($id, 'ai:')) {
            if (! $user->isAdmin()) {
                return ['ok' => false, 'message' => 'Merkezi AI çöpü yalnızca yönetici silebilir.'];
            }
            $stamp = substr($id, 3);
            $dir = dirname(base_path()).'/data/.panelze-ai-trash/'.$stamp;
            if (! is_dir($dir) || str_contains($stamp, '..')) {
                return ['ok' => false, 'message' => 'AI çöp kaydı bulunamadı.'];
            }
            File::deleteDirectory($dir);

            return ['ok' => true, 'message' => 'AI çöp kaydı kalıcı silindi.'];
        }

        $domain = $this->resolveDomain($user, $domainId);
        if ($domain === null) {
            return ['ok' => false, 'message' => 'Domain gerekli.'];
        }

        $item = '.panelze-trash/items/'.$id;
        $meta = '.panelze-trash/meta/'.$id.'.json';
        $r1 = $this->engine->deleteFile($domain->name, $item);
        $this->engine->deleteFile($domain->name, $meta);
        if (! empty($r1['error'])) {
            return ['ok' => false, 'message' => (string) $r1['error']];
        }

        $held = $this->deletedButHeldHint($domain->name, $id);

        return [
            'ok' => true,
            'message' => 'Çöp öğesi kalıcı silindi ('.$domain->name.').'
                .($held !== null ? ' '.$held : ''),
        ];
    }

    /**
     * Silinen ama süreç tarafından hâlâ açık tutulan dosya uyarısı.
     */
    private function deletedButHeldHint(string $domain, string $id): ?string
    {
        $web = rtrim(str_replace('\\', '/', (string) config('panelze.hosting_web_root', '')), '/');
        if ($web === '') {
            return null;
        }
        $needle = $web.'/'.$domain.'/.panelze-trash/items/'.$id;
        $heldGb = 0.0;
        foreach (glob('/proc/[0-9]*/fd/*') ?: [] as $fd) {
            try {
                $link = @readlink($fd);
                if (! is_string($link) || ! str_contains($link, '(deleted)')) {
                    continue;
                }
                if (! str_contains($link, $id) && ! str_contains($link, $needle)) {
                    continue;
                }
                $size = @filesize($fd);
                if (is_int($size) && $size > 50_000_000) {
                    $heldGb = max($heldGb, $size / 1024 / 1024 / 1024);
                }
            } catch (\Throwable) {
                continue;
            }
        }
        if ($heldGb < 0.05) {
            return null;
        }

        return sprintf(
            'Uyarı: ~%.1f GB hâlâ bir süreç tarafından açık (silindi ama diskte tutuluyor). İlgili uygulamayı/PM2 sürecini yeniden başlatın; aksi halde dashboard disk kullanımı düşmez.',
            $heldGb
        );
    }

    /**
     * @return array{ok: bool, message: string, purged: int}
     */
    public function emptyAll(User $user): array
    {
        $items = $this->listForUser($user, 200);
        $purged = 0;
        foreach ($items as $item) {
            $r = $this->destroy($user, (string) $item['id'], isset($item['domain_id']) ? (int) $item['domain_id'] : null);
            if ($r['ok']) {
                $purged++;
            }
        }

        return [
            'ok' => true,
            'message' => sprintf('%d çöp öğesi kalıcı silindi.', $purged),
            'purged' => $purged,
        ];
    }

    private function resolveDomain(User $user, ?int $domainId): ?Domain
    {
        if ($domainId === null || $domainId <= 0) {
            return null;
        }
        $q = Domain::query()->where('id', $domainId);
        if (! $user->isAdmin()) {
            $q->where('user_id', $user->id);
        }

        return $q->first();
    }

    private function itemSizeBytes(string $domain, string $id): int
    {
        $web = rtrim(str_replace('\\', '/', (string) config('panelze.hosting_web_root', '')), '/');
        if ($web === '') {
            return 0;
        }
        $path = $web.'/'.$domain.'/.panelze-trash/items/'.$id;
        if (is_file($path)) {
            return (int) (filesize($path) ?: 0);
        }
        if (is_dir($path)) {
            $sum = 0;
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if ($f->isFile()) {
                    $sum += $f->getSize();
                }
            }

            return $sum;
        }

        return 0;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $n = (float) max(0, $bytes);
        while ($n >= 1024 && $i < count($units) - 1) {
            $n /= 1024;
            $i++;
        }

        return ($i === 0 ? (string) (int) $n : number_format($n, $n >= 10 ? 1 : 2)).' '.$units[$i];
    }
}
