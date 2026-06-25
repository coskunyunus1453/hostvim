<?php

namespace App\Filament\Resources\DomainTlds;

use App\Filament\Resources\DomainTlds\Pages\CreateDomainTld;
use App\Filament\Resources\DomainTlds\Pages\EditDomainTld;
use App\Filament\Resources\DomainTlds\Pages\ListDomainTlds;
use App\Filament\Resources\DomainTlds\Schemas\DomainTldForm;
use App\Filament\Resources\DomainTlds\Tables\DomainTldsTable;
use App\Models\DomainTld;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DomainTldResource extends Resource
{
    protected static ?string $model = DomainTld::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Domain Yönetimi';

    protected static ?string $navigationLabel = 'TLD Fiyatları';

    protected static ?string $modelLabel = 'TLD';

    protected static ?string $pluralModelLabel = 'TLD fiyatları';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    public static function form(Schema $schema): Schema
    {
        return DomainTldForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DomainTldsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDomainTlds::route('/'),
            'create' => CreateDomainTld::route('/create'),
            'edit' => EditDomainTld::route('/{record}/edit'),
        ];
    }
}
