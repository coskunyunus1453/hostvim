<?php

namespace App\Filament\Resources\Faqs\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('question')->label('Soru')->required()->columnSpanFull(),
            Textarea::make('answer')->label('Cevap')->required()->rows(4)->columnSpanFull(),
            TextInput::make('category')->label('Kategori'),
            TextInput::make('sort_order')->label('Sıralama')->numeric()->default(0),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }
}
