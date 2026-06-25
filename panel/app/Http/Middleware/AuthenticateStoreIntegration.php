<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateStoreIntegration
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('panelze.store_integration.secret', '');
        if ($expected === '') {
            abort(response()->json([
                'message' => 'Mağaza entegrasyonu yapılandırılmadı (PANELZE_STORE_SECRET).',
            ], 503));
        }

        $auth = (string) $request->header('Authorization', '');
        $token = '';
        if (str_starts_with($auth, 'Bearer ')) {
            $token = trim(substr($auth, 7));
        } elseif ($auth !== '') {
            $token = trim($auth);
        }

        if ($token === '') {
            $token = trim((string) $request->header('X-Panelze-Store-Token', ''));
        }

        if ($token === '' || ! hash_equals($expected, $token)) {
            abort(response()->json([
                'message' => 'Geçersiz mağaza entegrasyon kimlik doğrulaması.',
            ], 401));
        }

        return $next($request);
    }
}
