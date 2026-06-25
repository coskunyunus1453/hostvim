<?php

namespace App\Filament\Resources\CloudServers;

use App\Filament\Resources\CloudServers\Pages\ListCloudServers;
use App\Filament\Resources\CloudServers\Tables\CloudServersTable;
use App\Models\CloudServer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CloudServerResource extends Resource
{
    protected static ?string $model = CloudServer::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Sunucu & Altyapı';

    protected static ?string $navigationLabel = 'Kurulan Sunucular';

    protected static ?string $modelLabel = 'Sunucu';

    protected static ?string $pluralModelLabel = 'Kurulan sunucular';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return CloudServersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCloudServers::route('/'),
        ];
    }
}
