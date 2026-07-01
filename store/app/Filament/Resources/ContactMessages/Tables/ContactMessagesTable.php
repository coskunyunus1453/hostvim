<?php

namespace App\Filament\Resources\ContactMessages\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Gönderen')->searchable()->sortable()->weight('bold'),
                TextColumn::make('email')->label('E-posta')->searchable()->copyable()->icon('heroicon-o-envelope'),
                TextColumn::make('phone')->label('Telefon')->placeholder('—')->toggleable(),
                TextColumn::make('subject')->label('Konu')->searchable()->limit(40)->placeholder('—'),
                IconColumn::make('is_read')->label('Okundu')->boolean(),
                TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_read')->label('Okunma durumu'),
                SelectFilter::make('subject')
                    ->label('Konu')
                    ->options(fn () => \App\Models\ContactMessage::query()
                        ->whereNotNull('subject')
                        ->distinct()
                        ->orderBy('subject')
                        ->pluck('subject', 'subject')
                        ->all()),
            ])
            ->recordActions([
                Action::make('mark_read')
                    ->label('Okundu')
                    ->icon('heroicon-o-check')
                    ->visible(fn ($record) => ! $record->is_read)
                    ->action(fn ($record) => $record->update(['is_read' => true])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Henüz mesaj yok')
            ->emptyStateDescription('İletişim formundan gelen mesajlar burada listelenir.');
    }
}
