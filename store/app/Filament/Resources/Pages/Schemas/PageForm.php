<?php

namespace App\Filament\Resources\Pages\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sayfa İçeriği')->schema([
                TextInput::make('title')->label('Başlık')->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                TextInput::make('slug')->label('URL Slug')->required()->unique(ignoreRecord: true),
                Textarea::make('excerpt')->label('Özet')->rows(3)->columnSpanFull(),
                RichEditor::make('content')->label('İçerik')->columnSpanFull(),
            ])->columns(2),

            Section::make('Menü & Yayın')->schema([
                Toggle::make('is_published')->label('Yayında')->default(false),
                Toggle::make('show_in_menu')->label('Menüde Göster'),
                Toggle::make('no_index')->label('Arama motorlarında gizle (noindex)'),
                TextInput::make('menu_label')->label('Menü Etiketi'),
                TextInput::make('sort_order')->label('Sıralama')->numeric()->default(0),
            ])->columns(2),

            Section::make('SEO')->schema([
                TextInput::make('meta_title')->label('Meta Başlık'),
                Textarea::make('meta_description')->label('Meta Açıklama')->columnSpanFull(),
                TextInput::make('meta_keywords')->label('Anahtar Kelimeler')->columnSpanFull(),
                FileUpload::make('og_image')->label('OG Görseli')->image()->directory('seo/pages'),
            ])->columns(2),
        ]);
    }
}
