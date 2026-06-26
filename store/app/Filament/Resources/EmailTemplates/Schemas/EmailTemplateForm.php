<?php

namespace App\Filament\Resources\EmailTemplates\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class EmailTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('E-posta Şablonu')->schema([
                TextInput::make('name')->label('Şablon Adı')->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                TextInput::make('slug')->label('Slug')->required()->unique(ignoreRecord: true),
                TextInput::make('subject')->label('Konu')->required()->columnSpanFull(),
                RichEditor::make('body')->label('İçerik (gövde)')->columnSpanFull()
                    ->helperText('Otomatik marka layout (logo, site adı, footer) eklenir. Değişkenler: {customer_name}, {order_number}, {site_name}, {site_url}, {support_email}, {primary_color}, {reset_url}…'),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ])->columns(2),
        ]);
    }
}
