<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingSiteSetting;
use App\Services\PanelIntegrationsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IntegrationsSettingsController extends Controller
{
    public function __construct(
        private PanelIntegrationsService $integrations,
    ) {}

    public function edit(): View
    {
        $secret = $this->integrations->googleDriveClientSecret();

        return view('admin.integrations-settings.edit', [
            'googleDriveEnabled' => $this->integrations->isGoogleDriveEnabled(),
            'googleDriveClientId' => $this->integrations->googleDriveClientId(),
            'googleDriveSecretConfigured' => $secret !== '',
            'googleDriveSecretMask' => $secret !== '' ? str_repeat('•', min(16, strlen($secret))) : '',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'google_drive_enabled' => ['nullable', 'boolean'],
            'google_drive_client_id' => ['nullable', 'string', 'max:255'],
            'google_drive_client_secret' => ['nullable', 'string', 'max:512'],
        ]);

        LandingSiteSetting::put(
            PanelIntegrationsService::KEY_ENABLED,
            $request->boolean('google_drive_enabled') ? '1' : '0',
        );
        LandingSiteSetting::put(
            PanelIntegrationsService::KEY_CLIENT_ID,
            trim((string) ($validated['google_drive_client_id'] ?? '')),
        );

        $newSecret = trim((string) ($validated['google_drive_client_secret'] ?? ''));
        if ($newSecret !== '') {
            LandingSiteSetting::put(PanelIntegrationsService::KEY_CLIENT_SECRET, $newSecret);
        }

        return redirect()
            ->route('admin.integrations-settings.edit')
            ->with('status', 'Panel entegrasyon ayarlari kaydedildi.');
    }
}
