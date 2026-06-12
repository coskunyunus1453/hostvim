<?php

namespace App\Services;

/**
 * Google Drive OAuth — once Panelze hub (panelze.com), yedek olarak panel .env.
 */
class GoogleDriveConfigService
{
    public function __construct(
        private PanelLicenseService $license,
    ) {}

    public function isConfigured(): bool
    {
        return $this->clientId() !== '' && $this->clientSecret() !== '';
    }

    public function clientId(): string
    {
        $hub = $this->hubCredentials();
        if ($hub !== null) {
            return $hub['client_id'];
        }

        return trim((string) config('panelze.google_drive.client_id', ''));
    }

    public function clientSecret(): string
    {
        $hub = $this->hubCredentials();
        if ($hub !== null) {
            return $hub['client_secret'];
        }

        return trim((string) config('panelze.google_drive.client_secret', ''));
    }

    /**
     * @return 'hub'|'env'|null
     */
    public function credentialSource(): ?string
    {
        if ($this->hubCredentials() !== null) {
            return 'hub';
        }
        $id = trim((string) config('panelze.google_drive.client_id', ''));
        $secret = trim((string) config('panelze.google_drive.client_secret', ''));

        return $id !== '' && $secret !== '' ? 'env' : null;
    }

    public function hubFeatureExpected(): bool
    {
        if (rtrim(trim((string) config('panelze.license_server', '')), '/') === '') {
            return false;
        }

        return $this->license->hasFeature('backups_pro');
    }

    /**
     * @return array{client_id: string, client_secret: string}|null
     */
    private function hubCredentials(): ?array
    {
        $hub = $this->license->hubPayload();
        if (! is_array($hub) || ! ($hub['valid'] ?? false)) {
            return null;
        }
        $gd = $hub['integrations']['google_drive'] ?? null;
        if (! is_array($gd)) {
            return null;
        }
        $id = trim((string) ($gd['client_id'] ?? ''));
        $secret = trim((string) ($gd['client_secret'] ?? ''));
        if ($id === '' || $secret === '') {
            return null;
        }

        return ['client_id' => $id, 'client_secret' => $secret];
    }
}
