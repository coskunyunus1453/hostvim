<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StorefrontAuth
{
    public static function redirectAfterLogin(Request $request): RedirectResponse
    {
        $user = $request->user();
        $default = ($user?->is_admin ?? false)
            ? url('/admin')
            : route('account.dashboard');

        $intended = $request->session()->pull('url.intended');

        if (is_string($intended) && $intended !== '' && self::isAllowedPostLoginUrl($intended, $user)) {
            return redirect()->to($intended);
        }

        return redirect()->to($default);
    }

    public static function purgeUnsafeIntendedUrl(Request $request): void
    {
        $intended = $request->session()->get('url.intended');
        if (! is_string($intended) || $intended === '') {
            return;
        }

        if (! self::isAllowedPostLoginUrl($intended, $request->user())) {
            $request->session()->forget('url.intended');
        }
    }

    public static function isAllowedPostLoginUrl(string $url, ?User $user): bool
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return false;
        }

        $host = $parts['host'] ?? null;
        if (is_string($host) && $host !== '') {
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            if (! is_string($appHost) || $appHost === '' || strcasecmp($host, $appHost) !== 0) {
                return false;
            }
        }

        $path = $parts['path'] ?? '/';
        if ($path === '' || $path === '/') {
            return true;
        }

        // Protocol-relative and other non-app paths are rejected via host check above.
        if (str_starts_with($path, '/admin') || str_contains($path, '/filament') || str_starts_with($path, '/livewire')) {
            return (bool) ($user?->is_admin);
        }

        return str_starts_with($path, '/');
    }
}
