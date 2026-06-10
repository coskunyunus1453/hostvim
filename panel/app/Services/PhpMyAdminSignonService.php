<?php

namespace App\Services;

use App\Models\Database;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PhpMyAdminSignonService
{
    public function __construct(
        private DatabaseService $databaseService,
    ) {}

    /**
     * Tek kullanımlık signon jetonu üretir.
     *
     * @return array{token: string, signon_url: string, expires_in: int}
     */
    public function mintForDatabase(Database $database): array
    {
        if ($database->type !== 'mysql') {
            throw new \InvalidArgumentException(__('databases.phpmyadmin_sso_mysql_only'));
        }

        $creds = $this->databaseService->mysqlCredentialsForSignon($database);
        $token = Str::random(48);
        $ttl = max(30, (int) config('panelze.phpmyadmin_signon.token_ttl', 90));

        Cache::put($this->cacheKey($token), [
            'user_id' => (int) $database->user_id,
            'database_id' => (int) $database->id,
            'username' => $creds['username'],
            'password' => $creds['password'],
            'host' => $creds['host'],
            'port' => $creds['port'],
            'db' => $creds['database'],
        ], now()->addSeconds($ttl));

        $signonUrl = url('/pma-signon?token='.urlencode($token));

        return [
            'token' => $token,
            'signon_url' => $signonUrl,
            'expires_in' => $ttl,
        ];
    }

    /**
     * @return array{username: string, password: string, host: string, port: int, db: string}|null
     */
    public function consumeToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '' || strlen($token) > 128) {
            return null;
        }

        $payload = Cache::pull($this->cacheKey($token));
        if (! is_array($payload)) {
            return null;
        }

        return [
            'username' => (string) ($payload['username'] ?? ''),
            'password' => (string) ($payload['password'] ?? ''),
            'host' => (string) ($payload['host'] ?? '127.0.0.1'),
            'port' => (int) ($payload['port'] ?? 3306),
            'db' => (string) ($payload['db'] ?? ''),
        ];
    }

    public function redirectUrlAfterSignon(string $databaseName): string
    {
        $base = rtrim((string) config('panelze.ui.phpmyadmin_url', ''), '/');
        if ($base === '') {
            $base = rtrim((string) config('app.url', ''), '/').'/phpmyadmin';
        }
        if ($databaseName !== '') {
            $base .= (str_contains($base, '?') ? '&' : '?').'db='.rawurlencode($databaseName);
        }

        return $base.'/';
    }

    private function cacheKey(string $token): string
    {
        return 'panelze.pma_signon.'.hash('sha256', $token);
    }
}
