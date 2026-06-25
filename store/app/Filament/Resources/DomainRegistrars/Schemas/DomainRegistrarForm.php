<?php

namespace App\Filament\Resources\DomainRegistrars\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DomainRegistrarForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sağlayıcı')->schema([
                Placeholder::make('api_name_display')
                    ->label('API Adı (domain api name)')
                    ->content(fn ($record) => $record?->api_name ?? '—'),
                Placeholder::make('tagline')
                    ->label('Öne çıkan özellik')
                    ->content(fn ($record) => config('domain_registrars.providers.'.$record?->api_name.'.tagline', '—')),
                Placeholder::make('highlight')
                    ->label('Neden seçmelisiniz?')
                    ->content(fn ($record) => config('domain_registrars.providers.'.$record?->api_name.'.highlight', '—'))
                    ->columnSpanFull(),
                Toggle::make('is_enabled')->label('Aktif')->helperText('Kapalı API\'ler fiyat karşılaştırmasına dahil edilmez.'),
                TextInput::make('sort_order')->label('Öncelik sırası')->numeric()->default(0)
                    ->helperText('Düşük değer = önce denenir (eşit fiyatta).'),
            ])->columns(2),

            Section::make('API Kimlik Bilgileri')
                ->description(fn ($record) => 'Dokümantasyon: '.(config('domain_registrars.providers.'.$record?->api_name.'.docs_url') ?: '—'))
                ->schema(function ($record): array {
                    if ($record === null) {
                        return [];
                    }

                    $fields = config('domain_registrars.providers.'.$record->api_name.'.credential_fields', []);
                    $components = [];

                    foreach ($fields as $key => $field) {
                        $input = TextInput::make('credentials.'.$key)
                            ->label($field['label'] ?? $key)
                            ->placeholder($field['placeholder'] ?? null);

                        if (($field['type'] ?? 'text') === 'password') {
                            $input->password()->revealable();
                        }

                        if ($field['required'] ?? false) {
                            $input->required();
                        }

                        $components[] = $input;
                    }

                    return $components;
                })
                ->columns(2),

            Section::make('Senkron Durumu')->schema([
                Placeholder::make('last_synced_at')
                    ->label('Son senkron')
                    ->content(fn ($record) => $record?->last_synced_at?->format('d.m.Y H:i') ?? 'Henüz yok'),
                Placeholder::make('last_sync_status')
                    ->label('Durum')
                    ->content(fn ($record) => $record?->last_sync_status ?? '—'),
                Placeholder::make('last_sync_message')
                    ->label('Mesaj')
                    ->content(fn ($record) => $record?->last_sync_message ?? '—')
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }
}
