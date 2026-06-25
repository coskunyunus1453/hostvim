<?php

namespace App\Filament\Resources\BusinessExpenses\Schemas;

use App\Models\BusinessExpense;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BusinessExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Gider Bilgisi')->schema([
                DatePicker::make('expense_date')
                    ->label('Gider Tarihi')
                    ->required()
                    ->default(now()),
                Select::make('category')
                    ->label('Kategori')
                    ->options(BusinessExpense::CATEGORIES)
                    ->required()
                    ->searchable(),
                TextInput::make('title')
                    ->label('Başlık')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->label('Açıklama')
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('amount')
                    ->label('Tutar')
                    ->numeric()
                    ->required()
                    ->prefix('₺')
                    ->minValue(0),
                TextInput::make('vendor')
                    ->label('Tedarikçi / Firma')
                    ->maxLength(255),
                TextInput::make('reference')
                    ->label('Fatura / Referans No')
                    ->maxLength(120),
                Toggle::make('is_recurring')
                    ->label('Tekrarlayan gider')
                    ->helperText('Aylık sabit giderler için işaretleyin.'),
            ])->columns(2),
        ]);
    }
}
