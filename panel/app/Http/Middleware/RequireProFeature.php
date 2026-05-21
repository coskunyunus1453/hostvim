<?php

namespace App\Http\Middleware;

use App\Services\PanelLicenseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireProFeature
{
    public function __construct(
        private PanelLicenseService $panelLicense,
    ) {}

    /**
     * @param  \Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if ($this->panelLicense->hasFeature($feature)) {
            return $next($request);
        }

        return response()->json([
            'message' => __('license.pro_feature_required', ['feature' => $feature]),
            'code' => 'pro_license_required',
            'feature' => $feature,
        ], 403);
    }
}
