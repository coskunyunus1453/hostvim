<?php

namespace App\Filament\Resources\DomainRegistrars\Pages;

use App\Filament\Resources\DomainRegistrars\DomainRegistrarResource;
use Filament\Resources\Pages\ListRecords;

class ListDomainRegistrars extends ListRecords
{
    protected static string $resource = DomainRegistrarResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
