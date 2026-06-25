<?php

namespace App\Filament\Resources\Campaigns\Pages;

use App\Filament\Resources\Campaigns\CampaignResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCampaign extends CreateRecord
{
    protected static string $resource = CampaignResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['billing_cycles'])) {
            $data['billing_cycles'] = null;
        }

        if (($data['applies_to'] ?? 'all') === 'all') {
            $data['target_ids'] = null;
        }

        if (! empty($data['code'])) {
            $data['code'] = strtoupper(trim($data['code']));
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        \App\Services\CampaignService::clearCache();
        app(\App\Services\CacheInvalidator::class)->forCampaignSaved();
    }
}
