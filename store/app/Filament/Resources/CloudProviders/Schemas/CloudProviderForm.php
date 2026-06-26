<?php

namespace App\Filament\Resources\CloudProviders\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CloudProviderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sağlayıcı')->schema([
                Placeholder::make('api_name_display')
                    ->label('API Adı')
                    ->content(fn ($record) => $record?->api_name ?? '—'),
                Placeholder::make('tagline')
                    ->label('Öne çıkan özellik')
                    ->content(fn ($record) => config('cloud_providers.providers.'.$record?->api_name.'.tagline', '—')),
                Placeholder::make('highlight')
                    ->label('Neden seçmelisiniz?')
                    ->content(fn ($record) => config('cloud_providers.providers.'.$record?->api_name.'.highlight', '—'))
                    ->columnSpanFull(),
                Toggle::make('is_enabled')->label('Aktif'),
                TextInput::make('sort_order')->label('Öncelik')->numeric()->default(0),
            ])->columns(2),

            Section::make('API Kimlik Bilgileri')
                ->description(fn ($record) => 'Dokümantasyon: '.(config('cloud_providers.providers.'.$record?->api_name.'.docs_url') ?: '—'))
                ->schema(function ($record): array {
                    if ($record === null) {
                        return [];
                    }
                    $fields = config('cloud_providers.providers.'.$record->api_name.'.credential_fields', []);
                    $components = [];
                    foreach ($fields as $key => $field) {
                        $input = TextInput::make('credentials.'.$key)
                            ->label($field['label'] ?? $key);
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

            Section::make('Test')->schema([
                Placeholder::make('last_tested_at')
                    ->label('Son test')
                    ->content(fn ($record) => $record?->last_tested_at?->format('d.m.Y H:i') ?? '—'),
                Placeholder::make('last_test_status')
                    ->label('Durum')
                    ->content(fn ($record) => $record?->last_test_status ?? '—'),
                Placeholder::make('last_test_message')
                    ->label('Mesaj')
                    ->content(fn ($record) => $record?->last_test_message ?? '—')
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }
}
