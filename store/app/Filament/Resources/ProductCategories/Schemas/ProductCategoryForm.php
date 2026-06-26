<?php

namespace App\Filament\Resources\ProductCategories\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Kategori')->schema([
                TextInput::make('name')->label('Ad')->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                TextInput::make('slug')->label('Slug')->required()->unique(ignoreRecord: true),
                Textarea::make('description')->label('Açıklama')->columnSpanFull(),
                TextInput::make('icon')->label('İkon (heroicon adı)'),
                ColorPicker::make('color')->label('Renk')->default('#C2410C'),
                Toggle::make('is_active')->label('Aktif')->default(true),
                Toggle::make('no_index')->label('Arama motorlarında gizle (noindex)'),
                TextInput::make('sort_order')->label('Sıralama')->numeric()->default(0),
                TextInput::make('meta_title')->label('Meta Başlık'),
                Textarea::make('meta_description')->label('Meta Açıklama')->columnSpanFull(),
                TextInput::make('meta_keywords')->label('Anahtar Kelimeler')->columnSpanFull(),
                FileUpload::make('og_image')->label('OG Görseli')->image()->directory('seo/categories'),
            ])->columns(2),
        ]);
    }
}
