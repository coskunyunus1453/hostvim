<?php

namespace App\Filament\Resources\DomainTlds\Pages;

use App\Filament\Resources\DomainTlds\DomainTldResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDomainTld extends CreateRecord
{
    protected static string $resource = DomainTldResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['tld']) && ! str_starts_with($data['tld'], '.')) {
            $data['tld'] = '.'.$data['tld'];
        }
        if (($data['registrar_api_name'] ?? '') === '') {
            $data['registrar_api_name'] = null;
        }

        return $data;
    }
}
