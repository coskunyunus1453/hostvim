<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Tek kullanımlık SSO jetonu → SPA giriş sayfası (?sso=).
 *
 * Müşteri SSO her zaman /login üzerinden tamamlanır; /admin gibi korumalı rotalara
 * yönlendirme yapılmaz (SPA oturum açılmadan ?sso= parametresi kaybolur).
 */
class WhmcsSsoRedirectController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $t = (string) $request->query('t', '');
        if ($t === '' || ! Str::isUuid($t)) {
            abort(404, 'Geçersiz bağlantı.');
        }
        if (! Cache::has('whmcs_sso:'.$t)) {
            abort(410, 'Bağlantının süresi doldu veya zaten kullanıldı.');
        }

        $loginBase = $this->resolveSsoLoginUrl(
            (string) config('panelze.whmcs_integration.sso_redirect_base', '')
        );

        $sep = str_contains($loginBase, '?') ? '&' : '?';

        return redirect()->away($loginBase.$sep.'sso='.rawurlencode($t));
    }

    private function resolveSsoLoginUrl(string $configured): string
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $fallback = $appUrl.'/login';

        if (trim($configured) === '') {
            return $fallback;
        }

        $base = rtrim(trim($configured), '/');
        $parsed = parse_url($base);
        if (! is_array($parsed) || empty($parsed['host'])) {
            return $fallback;
        }

        $appHost = strtolower((string) parse_url($appUrl, PHP_URL_HOST));
        $baseHost = strtolower((string) $parsed['host']);
        if ($appHost === '' || $baseHost !== $appHost) {
            return $fallback;
        }

        $scheme = strtolower((string) ($parsed['scheme'] ?? 'https'));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return $fallback;
        }

        $path = rtrim((string) ($parsed['path'] ?? ''), '/');
        if (str_ends_with($path, '/admin')) {
            $path = substr($path, 0, -6);
        }

        if ($path === '' || $path === '/' || ! str_ends_with($path, '/login')) {
            $path = '/login';
        }

        return $scheme.'://'.$baseHost.$path;
    }
}
