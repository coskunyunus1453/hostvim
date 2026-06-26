<?php

namespace App\Filament\Resources\DomainTlds\Pages;

use App\Filament\Resources\DomainTlds\DomainTldResource;
use Filament\Resources\Pages\EditRecord;

class EditDomainTld extends EditRecord
{
    protected static string $resource = DomainTldResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
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
