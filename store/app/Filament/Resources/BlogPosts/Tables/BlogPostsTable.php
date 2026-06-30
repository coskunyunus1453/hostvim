<?php

namespace App\Filament\Resources\BlogPosts\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BlogPostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                ImageColumn::make('featured_image')->label('Görsel')->disk('public')
                    ->height(48)->width(72)->extraImgAttributes(['class' => 'rounded-md object-cover'])
                    ->placeholder('—'),
                TextColumn::make('title')->label('Başlık')->searchable()->sortable()->limit(50),
                TextColumn::make('category.name')->label('Kategori')->sortable()->placeholder('—'),
                TextColumn::make('author.name')->label('Yazar')->toggleable(),
                IconColumn::make('is_published')->label('Yayında')->boolean(),
                TextColumn::make('published_at')->label('Yayın')->dateTime('d.m.Y H:i')->sortable()->placeholder('—'),
            ])
            ->filters([])
            ->recordActions([
                Action::make('view_on_site')
                    ->label('Görüntüle')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn ($record): string => route('blog.show', $record->slug), shouldOpenInNewTab: true)
                    ->visible(fn ($record): bool => (bool) $record->is_published),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
