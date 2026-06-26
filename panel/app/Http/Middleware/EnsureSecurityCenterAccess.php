<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sunucu güvenlik merkezi yalnızca yönetici, vendor veya bayi rollerine açıktır.
 * Müşteri (user) rolüne security:read verilse bile sunucu metrikleri sızmasın.
 */
class EnsureSecurityCenterAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user !== null && ($user->isAdmin() || $user->hasRole('reseller'))) {
            return $next($request);
        }

        abort(403, __('security.access_denied'));
    }
}
