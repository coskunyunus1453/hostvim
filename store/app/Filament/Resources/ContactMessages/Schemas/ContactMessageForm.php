<?php

namespace App\Filament\Resources\ContactMessages\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Gönderen')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Ad Soyad')->disabled(),
                        TextInput::make('email')->label('E-posta')->disabled()->copyable(),
                        TextInput::make('phone')->label('Telefon')->disabled()->placeholder('—'),
                        TextInput::make('subject')->label('Konu')->disabled()->placeholder('—')->columnSpanFull(),
                    ]),
                Section::make('Mesaj')
                    ->schema([
                        Textarea::make('message')
                            ->label('Mesaj içeriği')
                            ->disabled()
                            ->rows(8)
                            ->columnSpanFull(),
                    ]),
                Section::make('Yönetim')
                    ->schema([
                        Toggle::make('is_read')->label('Okundu olarak işaretle'),
                        DateTimePicker::make('replied_at')
                            ->label('Yanıtlanma tarihi')
                            ->seconds(false)
                            ->native(false),
                        Textarea::make('admin_note')
                            ->label('Admin notu')
                            ->rows(4)
                            ->placeholder('İç not, yanıt özeti veya takip bilgisi…')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
