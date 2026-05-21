<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SpeedTestService
{
    public function downloadBytes(): int
    {
        return max(256_000, min(5_000_000, (int) config('hostvim.curious.speed_download_bytes', 2_097_152)));
    }

    public function uploadMaxBytes(): int
    {
        return max(256_000, min(5_000_000, (int) config('hostvim.curious.speed_upload_max_bytes', 2_097_152)));
    }

    public function pingPayload(): array
    {
        return [
            'server_time' => (int) round(microtime(true) * 1000),
        ];
    }

    /**
     * @return array{token: string, bytes: int, expires_in: int}
     */
    public function prepareDownload(int $userId): array
    {
        $this->purgeUserDir($userId);

        $bytes = $this->downloadBytes();
        $token = Str::random(40);
        $dir = $this->userDir($userId);
        File::ensureDirectoryExists($dir);
        $path = $dir.DIRECTORY_SEPARATOR.$token.'.bin';

        $chunk = 64 * 1024;
        $fh = fopen($path, 'wb');
        if ($fh === false) {
            throw new \RuntimeException(__('curious.speed_file_failed'));
        }
        $written = 0;
        while ($written < $bytes) {
            $n = min($chunk, $bytes - $written);
            $buf = random_bytes($n);
            fwrite($fh, $buf);
            $written += $n;
        }
        fclose($fh);

        $ttl = max(60, (int) config('hostvim.curious.speed_token_ttl', 300));
        cache()->put($this->cacheKey($token), [
            'user_id' => $userId,
            'path' => $path,
            'bytes' => $bytes,
        ], now()->addSeconds($ttl));

        return [
            'token' => $token,
            'bytes' => $bytes,
            'expires_in' => $ttl,
        ];
    }

    /**
     * @return array{path: string, bytes: int, mime: string}|null
     */
    public function consumeDownload(string $token, int $userId): ?array
    {
        $payload = cache()->pull($this->cacheKey($token));
        if (! is_array($payload) || (int) ($payload['user_id'] ?? 0) !== $userId) {
            return null;
        }
        $path = (string) ($payload['path'] ?? '');
        if ($path === '' || ! is_file($path)) {
            return null;
        }

        return [
            'path' => $path,
            'bytes' => (int) ($payload['bytes'] ?? filesize($path)),
            'mime' => 'application/octet-stream',
        ];
    }

    /**
     * @return array{bytes: int, duration_ms: int}
     */
    public function handleUpload(int $userId, string $tempPath): array
    {
        $max = $this->uploadMaxBytes();
        $size = is_file($tempPath) ? (int) filesize($tempPath) : 0;
        if ($size > $max) {
            @unlink($tempPath);

            throw new \InvalidArgumentException(__('curious.speed_upload_too_large', ['max_mb' => round($max / 1048576, 1)]));
        }

        @unlink($tempPath);
        $this->purgeUserDir($userId);

        return [
            'bytes' => $size,
            'duration_ms' => 0,
        ];
    }

    public function purgeUserDir(int $userId): void
    {
        $dir = $this->userDir($userId);
        if (is_dir($dir)) {
            File::deleteDirectory($dir);
        }
    }

    public function deleteFileAfterStream(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function userDir(int $userId): string
    {
        return storage_path('app/speedtest/user-'.$userId);
    }

    private function cacheKey(string $token): string
    {
        return 'hostvim.speedtest.'.hash('sha256', $token);
    }
}
