<?php

namespace App\Filament\Resources\DomainNames;

use App\Filament\Resources\DomainNames\Pages\ListDomainNames;
use App\Filament\Resources\DomainNames\Tables\DomainNamesTable;
use App\Models\DomainName;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DomainNameResource extends Resource
{
    protected static ?string $model = DomainName::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Domain Yönetimi';

    protected static ?string $navigationLabel = 'Domainler';

    protected static ?string $modelLabel = 'Domain';

    protected static ?string $pluralModelLabel = 'Domainler';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return DomainNamesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDomainNames::route('/'),
        ];
    }
}
