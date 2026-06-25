<?php

namespace App\Filament\Resources\Campaigns\Pages;

use App\Filament\Resources\Campaigns\CampaignResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCampaign extends EditRecord
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
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

    protected function afterSave(): void
    {
        \App\Services\CampaignService::clearCache();
        app(\App\Services\CacheInvalidator::class)->forCampaignSaved();
    }

    protected function afterDelete(): void
    {
        \App\Services\CampaignService::clearCache();
        app(\App\Services\CacheInvalidator::class)->forCampaignSaved();
    }
}
