<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PanelLicenseService;
use Illuminate\Http\JsonResponse;

/**
 * Canlı ortamda harici araç URL’leri (.env ile yapılandırılır).
 */
class UiLinksController extends Controller
{
    public function __construct(
        private PanelLicenseService $panelLicense,
    ) {}

    public function show(): JsonResponse
    {
        return response()->json([
            'phpmyadmin_url' => (string) config('hostvim.ui.phpmyadmin_url', ''),
            'adminer_url' => (string) config('hostvim.ui.adminer_url', ''),
            'features' => [
                'phpmyadmin_auto_login' => $this->panelLicense->hasPhpMyAdminAutoLogin(),
                'license_valid' => $this->panelLicense->isLicenseValid(),
                'license_pro' => $this->panelLicense->isProPlan(),
            ],
        ]);
    }
}
