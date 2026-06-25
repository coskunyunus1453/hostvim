<?php

namespace App\Filament\Resources\BusinessExpenses;

use App\Filament\Resources\BusinessExpenses\Pages\CreateBusinessExpense;
use App\Filament\Resources\BusinessExpenses\Pages\EditBusinessExpense;
use App\Filament\Resources\BusinessExpenses\Pages\ListBusinessExpenses;
use App\Filament\Resources\BusinessExpenses\Schemas\BusinessExpenseForm;
use App\Filament\Resources\BusinessExpenses\Tables\BusinessExpensesTable;
use App\Models\BusinessExpense;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BusinessExpenseResource extends Resource
{
    protected static ?string $model = BusinessExpense::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Faturalama & Ödemeler';

    protected static ?string $navigationLabel = 'Gider Kayıtları';

    protected static ?string $modelLabel = 'Gider';

    protected static ?string $pluralModelLabel = 'Giderler';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    public static function form(Schema $schema): Schema
    {
        return BusinessExpenseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BusinessExpensesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBusinessExpenses::route('/'),
            'create' => CreateBusinessExpense::route('/create'),
            'edit' => EditBusinessExpense::route('/{record}/edit'),
        ];
    }
}
