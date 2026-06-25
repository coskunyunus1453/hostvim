<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\BlogPosts\BlogPostResource;
use App\Models\BlogPost;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestBlogPosts extends BaseWidget
{
    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 1;

    protected static ?string $heading = 'Son Blog Yazıları';

    public function table(Table $table): Table
    {
        return $table
            ->query(BlogPost::query()->latest('updated_at')->limit(8))
            ->heading(static::$heading)
            ->columns([
                TextColumn::make('title')->label('Başlık')->limit(35)->searchable(),
                TextColumn::make('category.name')->label('Kategori')->placeholder('—'),
                IconColumn::make('is_published')->label('Yayında')->boolean(),
                TextColumn::make('views')->label('Görüntülenme')->numeric(),
                TextColumn::make('updated_at')->label('Güncelleme')->since(),
            ])
            ->recordUrl(fn (BlogPost $record): string => BlogPostResource::getUrl('edit', ['record' => $record]))
            ->paginated(false);
    }
}
