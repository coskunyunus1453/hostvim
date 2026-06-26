<?php

namespace App\Filament\Resources\CloudProviders;

use App\Filament\Resources\CloudProviders\Pages\EditCloudProvider;
use App\Filament\Resources\CloudProviders\Pages\ListCloudProviders;
use App\Filament\Resources\CloudProviders\Schemas\CloudProviderForm;
use App\Filament\Resources\CloudProviders\Tables\CloudProvidersTable;
use App\Models\CloudProvider;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CloudProviderResource extends Resource
{
    protected static ?string $model = CloudProvider::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Sunucu & Altyapı';

    protected static ?string $navigationLabel = 'Bulut API Sağlayıcıları';

    protected static ?string $modelLabel = 'Bulut API';

    protected static ?string $pluralModelLabel = 'Bulut API sağlayıcıları';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    public static function form(Schema $schema): Schema
    {
        return CloudProviderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CloudProvidersTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCloudProviders::route('/'),
            'edit' => EditCloudProvider::route('/{record}/edit'),
        ];
    }
}
