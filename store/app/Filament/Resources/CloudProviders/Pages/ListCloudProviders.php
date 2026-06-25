<?php

namespace App\Filament\Resources\CloudProviders\Pages;

use App\Filament\Resources\CloudProviders\CloudProviderResource;
use Filament\Resources\Pages\ListRecords;

class ListCloudProviders extends ListRecords
{
    protected static string $resource = CloudProviderResource::class;
}
