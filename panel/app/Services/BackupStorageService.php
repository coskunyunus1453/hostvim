<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\BackupDestination;
use Illuminate\Support\Facades\Storage;

class BackupStorageService
{
    public function __construct(
        private GoogleDriveService $googleDrive,
    ) {}

    /**
     * @return array{ok: bool, error?: string, remote_path?: string, remote_file_id?: string|null}
     */
    public function syncBackup(Backup $backup): array
    {
        if (! $backup->destination_id) {
            return ['ok' => false, 'error' => 'destination not selected'];
        }
        $dest = BackupDestination::query()->find($backup->destination_id);
        if (! $dest || ! $dest->is_active) {
            return ['ok' => false, 'error' => 'destination not active'];
        }
        $sourcePath = trim((string) $backup->file_path);
        if ($sourcePath === '' || ! is_file($sourcePath)) {
            return ['ok' => false, 'error' => 'backup file not found'];
        }

        $domainName = $backup->domain?->name ?? 'site';
        $baseName = basename($sourcePath);
        if (! str_contains($baseName, $domainName)) {
            $baseName = preg_replace('/\.tar\.gz$/i', '', $baseName) ?? $baseName;
            $baseName = $domainName.'_'.$baseName.'_'.date('Ymd_His').'.tar.gz';
        }

        if ($dest->driver === 'google_drive') {
            $up = $this->googleDrive->uploadBackupFile($dest, $sourcePath, $baseName);
            if (! ($up['ok'] ?? false)) {
                return ['ok' => false, 'error' => (string) ($up['error'] ?? 'google drive upload failed')];
            }

            return [
                'ok' => true,
                'remote_path' => $up['remote_path'] ?? null,
                'remote_file_id' => $up['file_id'] ?? null,
            ];
        }

        $cfg = (array) ($dest->config ?? []);
        $remotePath = trim((string) ($cfg['path'] ?? 'backups')).'/'.$baseName;
        $remotePath = ltrim(str_replace('\\', '/', $remotePath), '/');

        try {
            $disk = $this->buildDestinationDisk($dest);
            $stream = fopen($sourcePath, 'rb');
            if ($stream === false) {
                return ['ok' => false, 'error' => 'backup stream open failed'];
            }
            $ok = $disk->put($remotePath, $stream);
            fclose($stream);
            if (! $ok) {
                return ['ok' => false, 'error' => 'remote write failed'];
            }

            return ['ok' => true, 'remote_path' => $remotePath, 'remote_file_id' => null];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array{ok: bool, path?: string, error?: string}
     */
    public function fetchRemoteToTemp(BackupDestination $dest, string $remoteKey, ?string $fileId = null): array
    {
        if ($dest->driver === 'google_drive') {
            $id = $fileId ?: $this->parseGoogleFileId($remoteKey);
            if ($id === '') {
                return ['ok' => false, 'error' => __('backups.remote_restore_not_found')];
            }

            return $this->googleDrive->downloadToTemp($dest, $id);
        }

        try {
            $disk = $this->buildDestinationDisk($dest);
            if (! $disk->exists($remoteKey)) {
                return ['ok' => false, 'error' => __('backups.remote_restore_not_found')];
            }
            $in = $disk->readStream($remoteKey);
            if ($in === false) {
                return ['ok' => false, 'error' => __('backups.remote_restore_download_failed')];
            }
            $tmp = tempnam(sys_get_temp_dir(), 'hv_restore_');
            if ($tmp === false) {
                if (is_resource($in)) {
                    fclose($in);
                }

                return ['ok' => false, 'error' => __('backups.remote_restore_download_failed')];
            }
            $out = fopen($tmp, 'wb');
            if ($out === false) {
                if (is_resource($in)) {
                    fclose($in);
                }
                @unlink($tmp);

                return ['ok' => false, 'error' => __('backups.remote_restore_download_failed')];
            }
            stream_copy_to_stream($in, $out);
            if (is_resource($in)) {
                fclose($in);
            }
            fclose($out);

            return ['ok' => true, 'path' => $tmp];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listRemoteFiles(BackupDestination $dest, ?string $domainFilter = null): array
    {
        if ($dest->driver === 'google_drive') {
            return $this->googleDrive->listBackupFiles($dest, $domainFilter);
        }

        $cfg = (array) ($dest->config ?? []);
        $prefix = trim((string) ($cfg['path'] ?? 'backups'), '/');
        try {
            $disk = $this->buildDestinationDisk($dest);
            $files = $disk->files($prefix);

            return array_values(array_map(static function (string $path) use ($disk): array {
                return [
                    'id' => $path,
                    'name' => basename($path),
                    'size' => $disk->size($path),
                    'modified_at' => date('c', $disk->lastModified($path)),
                ];
            }, array_filter($files, static function (string $path) use ($domainFilter): bool {
                if ($domainFilter === null || trim($domainFilter) === '') {
                    return str_ends_with(strtolower($path), '.tar.gz');
                }

                return str_contains(strtolower($path), strtolower($domainFilter))
                    && str_ends_with(strtolower($path), '.tar.gz');
            })));
        } catch (\Throwable) {
            return [];
        }
    }

    public function buildDestinationDisk(BackupDestination $dest)
    {
        $cfg = (array) ($dest->config ?? []);
        if ($dest->driver === 's3') {
            return Storage::build([
                'driver' => 's3',
                'key' => (string) ($cfg['access_key'] ?? ''),
                'secret' => (string) ($cfg['secret_key'] ?? ''),
                'region' => (string) ($cfg['region'] ?? 'us-east-1'),
                'bucket' => (string) ($cfg['bucket'] ?? ''),
                'throw' => true,
            ]);
        }
        if ($dest->driver === 'ftp') {
            return Storage::build([
                'driver' => 'ftp',
                'host' => (string) ($cfg['host'] ?? ''),
                'username' => (string) ($cfg['username'] ?? ''),
                'password' => (string) ($cfg['password'] ?? ''),
                'root' => (string) ($cfg['path'] ?? '/'),
                'throw' => true,
            ]);
        }

        return Storage::build([
            'driver' => 'local',
            'root' => (string) ($cfg['path'] ?? storage_path('app/backups')),
            'throw' => true,
        ]);
    }

    public function parseGoogleFileId(string $remoteKey): string
    {
        $remoteKey = trim($remoteKey);
        if (str_starts_with($remoteKey, 'google_drive:')) {
            return substr($remoteKey, strlen('google_drive:'));
        }

        return $remoteKey;
    }
}
