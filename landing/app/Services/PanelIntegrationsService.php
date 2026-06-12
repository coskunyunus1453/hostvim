<?php

namespace App\Services;

use App\Models\LandingSiteSetting;

/**
 * Panel örneklerine lisans API üzerinden dağıtılan entegrasyon ayarları.
 */
class PanelIntegrationsService
{
    public const KEY_ENABLED = 'integrations.google_drive.enabled';

    public const KEY_CLIENT_ID = 'integrations.google_drive.client_id';

    public const KEY_CLIENT_SECRET = 'integrations.google_drive.client_secret';

    public function isGoogleDriveEnabled(): bool
    {
        $stored = LandingSiteSetting::getValue(self::KEY_ENABLED, '1');

        return trim((string) $stored) !== '0';
    }

    public function googleDriveClientId(): string
    {
        return trim((string) LandingSiteSetting::getValue(self::KEY_CLIENT_ID, ''));
    }

    public function googleDriveClientSecret(): string
    {
        return trim((string) LandingSiteSetting::getValue(self::KEY_CLIENT_SECRET, ''));
    }

    public function isGoogleDriveConfigured(): bool
    {
        return $this->isGoogleDriveEnabled()
            && $this->googleDriveClientId() !== ''
            && $this->googleDriveClientSecret() !== '';
    }

    /**
     * Lisans validate yanıtına eklenecek Google Drive OAuth bilgisi.
     *
     * @return array{client_id: string, client_secret: string}|null
     */
    public function googleDriveForPanel(): ?array
    {
        if (! $this->isGoogleDriveConfigured()) {
            return null;
        }

        return [
            'client_id' => $this->googleDriveClientId(),
            'client_secret' => $this->googleDriveClientSecret(),
        ];
    }
}
