<?php

namespace App\Services;

use App\Models\EmailAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class WebmailSignonService
{
    public function __construct(
        private WebmailService $webmail,
    ) {}

    /**
     * @return array{token: string, signon_url: string, webmail_url: string, expires_in: int}
     */
    public function mintForAccount(EmailAccount $account): array
    {
        $account->loadMissing('domain');
        $domain = $account->domain;
        if ($domain === null) {
            throw new \InvalidArgumentException(__('email.webmail_domain_missing'));
        }

        if ($account->status !== 'active') {
            throw new \InvalidArgumentException(__('email.webmail_account_inactive'));
        }

        $status = $this->webmail->statusForDomain($domain, true);
        if (! ($status['mail_stack_ready'] ?? false)) {
            throw new \RuntimeException(__('email.webmail_stack_not_ready'));
        }

        $webmailUrl = $status['url'] ?? null;
        if ($webmailUrl === null || $webmailUrl === '') {
            throw new \RuntimeException($status['hint'] ?? __('email.webmail_url_unavailable'));
        }

        $password = $account->password;
        if (! is_string($password) || $password === '') {
            throw new \RuntimeException(__('email.webmail_password_missing'));
        }

        $token = Str::random(48);
        $ttl = max(30, (int) config('panelze.webmail_signon.token_ttl', 90));

        Cache::put($this->cacheKey($token), [
            'user_id' => (int) $account->user_id,
            'email_account_id' => (int) $account->id,
            'email' => (string) $account->email,
            'password' => $password,
            'webmail_url' => $webmailUrl,
        ], now()->addSeconds($ttl));

        return [
            'token' => $token,
            'signon_url' => url('/webmail-signon?token='.urlencode($token)),
            'webmail_url' => $webmailUrl,
            'expires_in' => $ttl,
        ];
    }

    /**
     * @return array{email: string, password: string, webmail_url: string}|null
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

        $email = (string) ($payload['email'] ?? '');
        $password = (string) ($payload['password'] ?? '');
        $webmailUrl = rtrim((string) ($payload['webmail_url'] ?? ''), '/');

        if ($email === '' || $password === '' || $webmailUrl === '') {
            return null;
        }

        return [
            'email' => $email,
            'password' => $password,
            'webmail_url' => $webmailUrl,
        ];
    }

    private function cacheKey(string $token): string
    {
        return 'panelze.webmail_signon.'.hash('sha256', $token);
    }
}
