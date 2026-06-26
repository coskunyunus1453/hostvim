<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestMessages extends BaseWidget
{
    protected static ?int $sort = 7;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Son İletişim Mesajları';

    public function table(Table $table): Table
    {
        return $table
            ->query(ContactMessage::query()->latest()->limit(6))
            ->heading(static::$heading)
            ->columns([
                TextColumn::make('name')->label('Gönderen')->searchable(),
                TextColumn::make('email')->label('E-posta'),
                TextColumn::make('subject')->label('Konu')->limit(40)->placeholder('—'),
                IconColumn::make('is_read')->label('Okundu')->boolean(),
                TextColumn::make('created_at')->label('Tarih')->since(),
            ])
            ->recordUrl(fn (ContactMessage $record): string => ContactMessageResource::getUrl('edit', ['record' => $record]))
            ->paginated(false);
    }
}
