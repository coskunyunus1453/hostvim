<?php

namespace App\Filament\Resources\Menus\RelationManagers;

use App\Models\Page;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Menü Öğeleri';

    protected static ?string $modelLabel = 'öğe';

    protected static ?string $pluralModelLabel = 'öğeler';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')->label('Görünen ad')->required()->maxLength(120),
            Select::make('page_id')
                ->label('Site sayfası (opsiyonel)')
                ->options(fn () => Page::query()->where('is_published', true)->orderBy('title')->pluck('title', 'id'))
                ->searchable()
                ->helperText('Seçilirse özel URL yerine sayfa linki kullanılır'),
            TextInput::make('url')
                ->label('Özel URL')
                ->placeholder('/sayfa/hakkimizda veya https://...')
                ->helperText('Sayfa seçilmediyse bu adres kullanılır'),
            Select::make('target')
                ->label('Açılış')
                ->options(['_self' => 'Aynı sekme', '_blank' => 'Yeni sekme'])
                ->default('_self'),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('Sıra')->sortable(),
                TextColumn::make('label')->label('Ad')->searchable(),
                TextColumn::make('href')->label('Bağlantı')->limit(40),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
