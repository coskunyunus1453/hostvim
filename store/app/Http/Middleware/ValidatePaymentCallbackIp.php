<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidatePaymentCallbackIp
{
    /** @var array<string, array<int, string>> */
    protected array $allowedHosts = [
        'paytr' => ['www.paytr.com', 'paytr.com'],
        'iyzico' => ['api.iyzipay.com', 'sandbox-api.iyzipay.com'],
    ];

    public function handle(Request $request, Closure $next, string $gateway): Response
    {
        if (app()->environment('local', 'testing')) {
            return $next($request);
        }

        $referer = parse_url($request->headers->get('referer', ''), PHP_URL_HOST);
        $allowed = $this->allowedHosts[$gateway] ?? [];

        // PayTR/iyzico sunucu callback'leri referer göndermeyebilir; boş referer kabul edilir.
        if ($referer !== null && $referer !== '' && ! in_array($referer, $allowed, true)) {
            abort(403, 'Geçersiz callback kaynağı.');
        }

        return $next($request);
    }
}
