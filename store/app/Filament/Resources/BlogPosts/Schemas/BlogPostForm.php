<?php

namespace App\Filament\Resources\BlogPosts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BlogPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Blog Yazısı')->schema([
                TextInput::make('title')->label('Başlık')->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                TextInput::make('slug')->label('URL Slug')->required()->unique(ignoreRecord: true),
                Select::make('blog_category_id')->label('Kategori')->relationship('category', 'name')->searchable()->preload(),
                Select::make('user_id')->label('Yazar')->relationship('author', 'name')->default(fn () => auth()->id()),
                Textarea::make('excerpt')->label('Özet')->rows(3)->columnSpanFull(),
                RichEditor::make('content')->label('İçerik')->columnSpanFull(),
                FileUpload::make('featured_image')->label('Kapak Görseli')->image()->directory('blog')->columnSpanFull(),
            ])->columns(2),

            Section::make('Yayın & SEO')->schema([
                Toggle::make('is_published')->label('Yayında')->default(false),
                Toggle::make('no_index')->label('Arama motorlarında gizle (noindex)'),
                DateTimePicker::make('published_at')->label('Yayın Tarihi'),
                TextInput::make('meta_title')->label('Meta Başlık'),
                Textarea::make('meta_description')->label('Meta Açıklama')->columnSpanFull(),
                TextInput::make('meta_keywords')->label('Anahtar Kelimeler')->columnSpanFull(),
                FileUpload::make('og_image')->label('OG Görseli (boşsa kapak kullanılır)')->image()->directory('seo/blog'),
            ])->columns(2),
        ]);
    }
}
