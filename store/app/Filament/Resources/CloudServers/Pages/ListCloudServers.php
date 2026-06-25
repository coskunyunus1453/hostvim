<?php

namespace App\Filament\Resources\CloudServers\Pages;

use App\Filament\Resources\CloudServers\CloudServerResource;
use Filament\Resources\Pages\ListRecords;

class ListCloudServers extends ListRecords
{
    protected static string $resource = CloudServerResource::class;
}
