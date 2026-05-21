<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PanelLicenseService;
use App\Services\ProFeatureCatalogService;
use Illuminate\Http\JsonResponse;

/**
 * Canlı ortamda harici araç URL’leri (.env ile yapılandırılır).
 */
class UiLinksController extends Controller
{
    public function __construct(
        private PanelLicenseService $panelLicense,
        private ProFeatureCatalogService $proCatalog,
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
                'plan' => $this->panelLicense->planCode(),
                'expires_at' => $this->panelLicense->expiresAt(),
            ],
            'modules' => $this->proCatalog->modulesForUi($this->panelLicense),
        ]);
    }
}
