<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AddonsRelationManager extends RelationManager
{
    protected static string $relationship = 'addons';

    protected static ?string $title = 'Sipariş Eklentileri';

    protected static ?string $modelLabel = 'eklenti';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Eklenti adı')->required()->maxLength(120),
            Textarea::make('description')->label('Açıklama')->rows(2)->columnSpanFull(),
            Select::make('billing_mode')
                ->label('Fiyatlandırma')
                ->options([
                    'match_parent' => 'Ana ürün dönemiyle aynı',
                    'monthly' => 'Aylık',
                    'yearly' => 'Yıllık',
                    'onetime' => 'Tek seferlik',
                ])
                ->default('match_parent')
                ->required(),
            TextInput::make('price_monthly')->label('Fiyat — Aylık')->numeric()->prefix('₺'),
            TextInput::make('price_yearly')->label('Fiyat — Yıllık')->numeric()->prefix('₺'),
            TextInput::make('price_onetime')->label('Fiyat — Tek sefer')->numeric()->prefix('₺'),
            TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('Sıra')->sortable(),
                TextColumn::make('name')->label('Ad')->searchable(),
                TextColumn::make('billing_mode')->label('Mod'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->headerActions([CreateAction::make()])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
