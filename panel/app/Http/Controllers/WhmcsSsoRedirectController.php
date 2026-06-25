<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * WHMCS SSO: tarayıcı dostu URL → SPA (?sso=) yönlendirmesi.
 */
class WhmcsSsoRedirectController extends Controller
{
    public function redirect(Request $request): RedirectResponse|\Illuminate\Http\Response
    {
        $t = (string) $request->query('t', '');
        if ($t === '' || ! Str::isUuid($t)) {
            abort(404, 'Geçersiz bağlantı.');
        }
        if (! Cache::has('whmcs_sso:'.$t)) {
            abort(410, 'Bağlantının süresi doldu veya zaten kullanıldı.');
        }

        $base = $this->resolveSsoRedirectBase(
            (string) config('panelze.whmcs_integration.sso_redirect_base', '')
        );

        $sep = str_contains($base, '?') ? '&' : '?';

        return redirect()->away($base.$sep.'sso='.rawurlencode($t));
    }

    private function resolveSsoRedirectBase(string $configured): string
    {
        $fallback = rtrim((string) config('app.url'), '/').'/admin';
        $base = trim($configured) !== '' ? trim($configured) : $fallback;

        $parsed = parse_url($base);
        if (! is_array($parsed) || empty($parsed['host'])) {
            return $fallback;
        }

        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $baseHost = strtolower((string) $parsed['host']);
        if ($appHost === '' || $baseHost !== $appHost) {
            return $fallback;
        }

        $scheme = strtolower((string) ($parsed['scheme'] ?? 'https'));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return $fallback;
        }

        $path = (string) ($parsed['path'] ?? '');
        $query = isset($parsed['query']) ? '?'.$parsed['query'] : '';

        return $scheme.'://'.$baseHost.$path.$query;
    }
}
