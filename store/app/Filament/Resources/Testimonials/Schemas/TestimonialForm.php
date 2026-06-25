<?php

namespace App\Filament\Resources\Testimonials\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TestimonialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Ad')->required(),
            TextInput::make('role')->label('Pozisyon'),
            TextInput::make('company')->label('Şirket'),
            Textarea::make('content')->label('Yorum')->required()->columnSpanFull(),
            TextInput::make('rating')->label('Puan (1-5)')->numeric()->default(5)->minValue(1)->maxValue(5),
            TextInput::make('sort_order')->label('Sıralama')->numeric()->default(0),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }
}
