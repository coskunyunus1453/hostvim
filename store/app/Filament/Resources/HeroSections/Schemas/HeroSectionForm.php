<?php

namespace App\Filament\Resources\HeroSections\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HeroSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Hero Bölümü')->schema([
                TextInput::make('page')->label('Sayfa')->default('home'),
                Select::make('layout_variant')
                    ->label('Hero şablonu')
                    ->options([
                        'split' => 'Split — İki sütun',
                        'centered' => 'Centered — Ortalanmış',
                        'aurora' => 'Aurora — Mesh gradyan',
                    ])
                    ->default('split'),
                TextInput::make('title')->label('Başlık')->required()->columnSpanFull(),
                TextInput::make('subtitle')->label('Alt Başlık')->columnSpanFull(),
                Textarea::make('description')->label('Açıklama')->columnSpanFull(),
                TextInput::make('cta_text')->label('Birincil Buton Metni'),
                TextInput::make('cta_url')->label('Birincil Buton URL'),
                TextInput::make('secondary_cta_text')->label('İkincil Buton Metni'),
                TextInput::make('secondary_cta_url')->label('İkincil Buton URL'),
                FileUpload::make('image')->label('Görsel')->image()->directory('hero'),
                TextInput::make('stat_1_value')->label('İstatistik 1 değer'),
                TextInput::make('stat_1_label')->label('İstatistik 1 etiket'),
                TextInput::make('stat_2_value')->label('İstatistik 2 değer'),
                TextInput::make('stat_2_label')->label('İstatistik 2 etiket'),
                TextInput::make('stat_3_value')->label('İstatistik 3 değer'),
                TextInput::make('stat_3_label')->label('İstatistik 3 etiket'),
                Toggle::make('is_active')->label('Aktif')->default(true),
                TextInput::make('sort_order')->label('Sıralama')->numeric()->default(0),
            ])->columns(2),
        ]);
    }
}
