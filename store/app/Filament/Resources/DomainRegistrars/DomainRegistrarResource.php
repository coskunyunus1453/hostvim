<?php

namespace App\Filament\Resources\DomainRegistrars;

use App\Filament\Resources\DomainRegistrars\Pages\EditDomainRegistrar;
use App\Filament\Resources\DomainRegistrars\Pages\ListDomainRegistrars;
use App\Filament\Resources\DomainRegistrars\Schemas\DomainRegistrarForm;
use App\Filament\Resources\DomainRegistrars\Tables\DomainRegistrarsTable;
use App\Models\DomainRegistrar;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DomainRegistrarResource extends Resource
{
    protected static ?string $model = DomainRegistrar::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Domain Yönetimi';

    protected static ?string $navigationLabel = 'Domain API Sağlayıcıları';

    protected static ?string $modelLabel = 'Domain API';

    protected static ?string $pluralModelLabel = 'Domain API sağlayıcıları';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    public static function form(Schema $schema): Schema
    {
        return DomainRegistrarForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DomainRegistrarsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDomainRegistrars::route('/'),
            'edit' => EditDomainRegistrar::route('/{record}/edit'),
        ];
    }
}
