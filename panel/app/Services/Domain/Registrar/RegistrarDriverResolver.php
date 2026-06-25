<?php

namespace App\Services\Domain\Registrar;

use App\Services\Billing\BillingSettings;

class RegistrarDriverResolver
{
    public function __construct(
        private BillingSettings $settings,
        private ManualRegistrarDriver $manual,
        private ResellerClubRegistrarDriver $resellerClub,
    ) {}

    public function driver(): RegistrarDriverInterface
    {
        $mode = (string) $this->settings->get('domain_registrar', 'manual');

        if ($mode === 'resellerclub' && $this->resellerClub->isConfigured()) {
            return $this->resellerClub;
        }

        return $this->manual;
    }

    public function usesResellerClub(): bool
    {
        return (string) $this->settings->get('domain_registrar', 'manual') === 'resellerclub'
            && $this->resellerClub->isConfigured();
    }
}
