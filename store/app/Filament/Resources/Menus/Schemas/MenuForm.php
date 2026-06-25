<?php

namespace App\Filament\Resources\Menus\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Menü adı')
                ->required()
                ->maxLength(120),
            Select::make('location')
                ->label('Konum')
                ->options([
                    'header' => 'Üst Menü (Header)',
                    'footer' => 'Alt Menü (Footer)',
                ])
                ->required()
                ->unique(ignoreRecord: true)
                ->disabled(fn (?string $operation) => $operation === 'edit'),
        ]);
    }
}
