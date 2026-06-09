<?php

namespace App\Services;

use App\Models\BackupDestination;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleDriveService
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const DRIVE_UPLOAD = 'https://www.googleapis.com/upload/drive/v3/files';

    private const DRIVE_API = 'https://www.googleapis.com/drive/v3/files';

    private const SCOPE = 'https://www.googleapis.com/auth/drive.file';

    public function isConfigured(): bool
    {
        return $this->clientId() !== '' && $this->clientSecret() !== '';
    }

    public function redirectUri(): string
    {
        $custom = trim((string) config('hostvim.google_drive.redirect_uri', ''));
        if ($custom !== '') {
            return $custom;
        }

        return rtrim((string) config('app.url'), '/').'/backups/google-callback';
    }

    /**
     * @return array{url: string, state: string}
     */
    public function authorizationUrl(int $userId): array
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException(__('backups.google_drive_not_configured'));
        }
        $state = Str::random(40);
        Cache::put($this->stateCacheKey($state), $userId, now()->addMinutes(15));

        $query = http_build_query([
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return ['url' => self::AUTH_URL.'?'.$query, 'state' => $state];
    }

    /**
     * @return array{ok: bool, error?: string, destination?: BackupDestination}
     */
    public function completeOAuth(int $userId, string $code, string $state, string $destinationName = 'Google Drive'): array
    {
        $cachedUser = Cache::pull($this->stateCacheKey($state));
        if ((int) $cachedUser !== $userId) {
            return ['ok' => false, 'error' => __('backups.google_drive_state_invalid')];
        }

        $tokens = $this->exchangeCode($code);
        if (! ($tokens['ok'] ?? false)) {
            return ['ok' => false, 'error' => (string) ($tokens['error'] ?? 'oauth_failed')];
        }

        BackupDestination::query()
            ->where('user_id', $userId)
            ->where('driver', 'google_drive')
            ->update(['is_default' => false]);

        $dest = BackupDestination::create([
            'user_id' => $userId,
            'name' => $destinationName !== '' ? $destinationName : 'Google Drive',
            'driver' => 'google_drive',
            'config' => [
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'expires_at' => $tokens['expires_at'] ?? null,
                'email' => $tokens['email'] ?? null,
                'folder_name' => (string) config('hostvim.google_drive.folder_name', 'Panelze Backups'),
                'folder_id' => null,
            ],
            'is_default' => true,
            'is_active' => true,
        ]);

        $folderId = $this->ensureFolder($dest);
        if ($folderId !== '') {
            $cfg = (array) ($dest->config ?? []);
            $cfg['folder_id'] = $folderId;
            $dest->update(['config' => $cfg]);
        }

        return ['ok' => true, 'destination' => $dest->fresh()];
    }

    /**
     * @return array{ok: bool, error?: string, access_token?: string, refresh_token?: string|null, expires_at?: int|null, email?: string|null}
     */
    private function exchangeCode(string $code): array
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ]);
        if (! $response->successful()) {
            return ['ok' => false, 'error' => (string) ($response->json('error_description') ?? $response->body())];
        }
        $data = $response->json();
        $email = $this->fetchUserEmail((string) ($data['access_token'] ?? ''));

        return [
            'ok' => true,
            'access_token' => (string) ($data['access_token'] ?? ''),
            'refresh_token' => $data['refresh_token'] ?? null,
            'expires_at' => isset($data['expires_in']) ? time() + (int) $data['expires_in'] - 30 : null,
            'email' => $email,
        ];
    }

    public function accessToken(BackupDestination $dest): string
    {
        $cfg = (array) ($dest->config ?? []);
        $expiresAt = (int) ($cfg['expires_at'] ?? 0);
        if (($cfg['access_token'] ?? '') !== '' && $expiresAt > time()) {
            return (string) $cfg['access_token'];
        }
        $refresh = (string) ($cfg['refresh_token'] ?? '');
        if ($refresh === '') {
            throw new \RuntimeException(__('backups.google_drive_token_expired'));
        }
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'refresh_token' => $refresh,
            'grant_type' => 'refresh_token',
        ]);
        if (! $response->successful()) {
            throw new \RuntimeException(__('backups.google_drive_token_expired'));
        }
        $data = $response->json();
        $cfg['access_token'] = (string) ($data['access_token'] ?? '');
        $cfg['expires_at'] = isset($data['expires_in']) ? time() + (int) $data['expires_in'] - 30 : null;
        $dest->update(['config' => $cfg]);

        return (string) $cfg['access_token'];
    }

    public function ensureFolder(BackupDestination $dest): string
    {
        $cfg = (array) ($dest->config ?? []);
        $folderId = trim((string) ($cfg['folder_id'] ?? ''));
        if ($folderId !== '') {
            return $folderId;
        }
        $folderName = trim((string) ($cfg['folder_name'] ?? 'Panelze Backups'));
        $token = $this->accessToken($dest);
        $q = sprintf(
            "mimeType='application/vnd.google-apps.folder' and name='%s' and trashed=false",
            str_replace("'", "\\'", $folderName)
        );
        $search = Http::withToken($token)->get(self::DRIVE_API, [
            'q' => $q,
            'fields' => 'files(id,name)',
            'pageSize' => 1,
        ]);
        if ($search->successful()) {
            $files = (array) ($search->json('files') ?? []);
            if ($files !== []) {
                return (string) ($files[0]['id'] ?? '');
            }
        }
        $create = Http::withToken($token)->post(self::DRIVE_API, [
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
        ]);
        if (! $create->successful()) {
            return '';
        }

        return (string) ($create->json('id') ?? '');
    }

    /**
     * @return array{ok: bool, error?: string, file_id?: string, remote_path?: string}
     */
    public function uploadBackupFile(BackupDestination $dest, string $localPath, string $remoteName): array
    {
        if (! is_readable($localPath)) {
            return ['ok' => false, 'error' => 'local file not readable'];
        }
        $token = $this->accessToken($dest);
        $folderId = $this->ensureFolder($dest);
        $metadata = [
            'name' => $remoteName,
        ];
        if ($folderId !== '') {
            $metadata['parents'] = [$folderId];
        }
        $boundary = 'hostvim_'.Str::random(16);
        $body = "--{$boundary}\r\n"
            ."Content-Type: application/json; charset=UTF-8\r\n\r\n"
            .json_encode($metadata, JSON_UNESCAPED_UNICODE)
            ."\r\n--{$boundary}\r\n"
            ."Content-Type: application/gzip\r\n\r\n"
            .file_get_contents($localPath)
            ."\r\n--{$boundary}--";

        $response = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'multipart/related; boundary='.$boundary])
            ->withBody($body, 'multipart/related; boundary='.$boundary)
            ->post(self::DRIVE_UPLOAD.'?uploadType=multipart&fields=id,name');

        if (! $response->successful()) {
            return ['ok' => false, 'error' => (string) ($response->json('error.message') ?? $response->body())];
        }
        $fileId = (string) ($response->json('id') ?? '');
        $remotePath = 'google_drive:'.$fileId;

        return ['ok' => true, 'file_id' => $fileId, 'remote_path' => $remotePath];
    }

    /**
     * @return array{ok: bool, error?: string, path?: string}
     */
    public function downloadToTemp(BackupDestination $dest, string $fileId): array
    {
        $token = $this->accessToken($dest);
        $response = Http::withToken($token)
            ->withOptions(['stream' => true])
            ->get(self::DRIVE_API.'/'.rawurlencode($fileId), ['alt' => 'media']);
        if (! $response->successful()) {
            return ['ok' => false, 'error' => __('backups.remote_restore_download_failed')];
        }
        $tmp = tempnam(sys_get_temp_dir(), 'hv_gdrive_');
        if ($tmp === false) {
            return ['ok' => false, 'error' => __('backups.remote_restore_download_failed')];
        }
        $out = fopen($tmp, 'wb');
        if ($out === false) {
            @unlink($tmp);

            return ['ok' => false, 'error' => __('backups.remote_restore_download_failed')];
        }
        fwrite($out, $response->body());
        fclose($out);

        return ['ok' => true, 'path' => $tmp];
    }

    /**
     * @return list<array{id: string, name: string, size: int|null, modified_at: string|null}>
     */
    public function listBackupFiles(BackupDestination $dest, ?string $domainFilter = null): array
    {
        $token = $this->accessToken($dest);
        $folderId = $this->ensureFolder($dest);
        $qParts = ["trashed=false", "mimeType!='application/vnd.google-apps.folder'"];
        if ($folderId !== '') {
            $qParts[] = "'{$folderId}' in parents";
        }
        if ($domainFilter !== null && trim($domainFilter) !== '') {
            $qParts[] = "name contains '".str_replace("'", "\\'", trim($domainFilter))."'";
        }
        $response = Http::withToken($token)->get(self::DRIVE_API, [
            'q' => implode(' and ', $qParts),
            'fields' => 'files(id,name,size,modifiedTime)',
            'orderBy' => 'modifiedTime desc',
            'pageSize' => 100,
        ]);
        if (! $response->successful()) {
            return [];
        }

        return array_map(static function (array $f): array {
            return [
                'id' => (string) ($f['id'] ?? ''),
                'name' => (string) ($f['name'] ?? ''),
                'size' => isset($f['size']) ? (int) $f['size'] : null,
                'modified_at' => isset($f['modifiedTime']) ? (string) $f['modifiedTime'] : null,
            ];
        }, (array) ($response->json('files') ?? []));
    }

    private function fetchUserEmail(string $accessToken): ?string
    {
        if ($accessToken === '') {
            return null;
        }
        $response = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v2/userinfo');
        if (! $response->successful()) {
            return null;
        }

        return $response->json('email');
    }

    private function clientId(): string
    {
        return trim((string) config('hostvim.google_drive.client_id', ''));
    }

    private function clientSecret(): string
    {
        return trim((string) config('hostvim.google_drive.client_secret', ''));
    }

    private function stateCacheKey(string $state): string
    {
        return 'panelze:gdrive_oauth:'.$state;
    }
}
