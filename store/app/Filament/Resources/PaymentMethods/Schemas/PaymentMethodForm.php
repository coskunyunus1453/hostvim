<?php

namespace App\Filament\Resources\PaymentMethods\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PaymentMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ödeme Yöntemi')->schema([
                Select::make('code')
                    ->label('Sağlayıcı')
                    ->options([
                        'paytr' => 'PayTR — Türkiye kart / taksit',
                        'iyzico' => 'iyzico — Türkiye kart',
                        'stripe' => 'Stripe — Global kart (Visa, MC, Amex)',
                        'paypal' => 'PayPal — PayPal bakiye / kart',
                        'payoneer' => 'Payoneer Checkout — Global kart',
                        'bank_transfer' => 'Havale / EFT',
                    ])
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->live()
                    ->columnSpanFull(),
                TextInput::make('name')->label('Görünen Ad')->required()->columnSpanFull(),
                Textarea::make('description')->label('Açıklama')->rows(2)->columnSpanFull(),
                Textarea::make('instructions')
                    ->label('Havale Talimatları')
                    ->rows(4)
                    ->visible(fn (Get $get): bool => $get('code') === 'bank_transfer')
                    ->columnSpanFull(),
                Toggle::make('is_active')->label('Aktif')->default(true),
                TextInput::make('sort_order')->label('Sıralama')->numeric()->default(0),
                TextInput::make('config.commission_rate')
                    ->label('Komisyon oranı (%)')
                    ->numeric()->minValue(0)->maxValue(100)->step(0.01)
                    ->suffix('%')
                    ->default(0)
                    ->helperText('Sağlayıcının işlem başına kestiği komisyon. Kârlılık raporundaki tahmini komisyon hesabında kullanılır. Havale için 0 bırakın.'),
            ])->columns(2),

            Section::make('PayTR API')
                ->visible(fn (Get $get): bool => $get('code') === 'paytr')
                ->schema(self::paytrFields())
                ->columns(2),

            Section::make('iyzico API')
                ->visible(fn (Get $get): bool => $get('code') === 'iyzico')
                ->schema(self::iyzicoFields())
                ->columns(2),

            Section::make('Stripe API')
                ->description('Stripe Dashboard → Developers → API keys. Webhook: checkout.session.completed')
                ->visible(fn (Get $get): bool => $get('code') === 'stripe')
                ->schema(self::stripeFields())
                ->columns(2),

            Section::make('PayPal API')
                ->description('developer.paypal.com → Apps → REST API credentials')
                ->visible(fn (Get $get): bool => $get('code') === 'paypal')
                ->schema(self::paypalFields())
                ->columns(2),

            Section::make('Payoneer Checkout API')
                ->description('checkoutdocs.payoneer.com — API username, token ve program ID')
                ->visible(fn (Get $get): bool => $get('code') === 'payoneer')
                ->schema(self::payoneerFields())
                ->columns(2),
        ]);
    }

    /** @return list<\Filament\Forms\Components\Component> */
    protected static function paytrFields(): array
    {
        return [
            TextInput::make('config.merchant_id')->label('Merchant ID'),
            TextInput::make('config.merchant_key')->label('Merchant Key')->password()->revealable()
                ->dehydrated(fn ($state) => filled($state)),
            TextInput::make('config.merchant_salt')->label('Merchant Salt')->password()->revealable()
                ->dehydrated(fn ($state) => filled($state)),
            Toggle::make('config.test_mode')->label('Test modu')->default(true),
        ];
    }

    /** @return list<\Filament\Forms\Components\Component> */
    protected static function iyzicoFields(): array
    {
        return [
            TextInput::make('config.api_key')->label('API Key'),
            TextInput::make('config.secret_key')->label('Secret Key')->password()->revealable()
                ->dehydrated(fn ($state) => filled($state)),
            Toggle::make('config.test_mode')->label('Sandbox')->default(true),
        ];
    }

    /** @return list<\Filament\Forms\Components\Component> */
    protected static function stripeFields(): array
    {
        return [
            TextInput::make('config.publishable_key')->label('Publishable key')->helperText('pk_live_... veya pk_test_...'),
            TextInput::make('config.secret_key')->label('Secret key')->password()->revealable()
                ->dehydrated(fn ($state) => filled($state)),
            TextInput::make('config.webhook_secret')->label('Webhook signing secret')->password()->revealable()
                ->dehydrated(fn ($state) => filled($state))
                ->helperText('whsec_... — endpoint: '.url('/odeme/stripe/webhook')),
            Toggle::make('config.test_mode')->label('Test modu')->default(true),
        ];
    }

    /** @return list<\Filament\Forms\Components\Component> */
    protected static function paypalFields(): array
    {
        return [
            TextInput::make('config.client_id')->label('Client ID'),
            TextInput::make('config.client_secret')->label('Client Secret')->password()->revealable()
                ->dehydrated(fn ($state) => filled($state)),
            Toggle::make('config.test_mode')->label('Sandbox')->default(true),
        ];
    }

    /** @return list<\Filament\Forms\Components\Component> */
    protected static function payoneerFields(): array
    {
        return [
            TextInput::make('config.api_username')->label('API kullanıcı adı'),
            TextInput::make('config.api_token')->label('API token')->password()->revealable()
                ->dehydrated(fn ($state) => filled($state)),
            TextInput::make('config.program_id')->label('Program / Store ID'),
            Select::make('config.default_currency')->label('Varsayılan para birimi')->options([
                'USD' => 'USD',
                'EUR' => 'EUR',
                'GBP' => 'GBP',
                'TRY' => 'TRY',
            ])->default('USD'),
            Toggle::make('config.test_mode')->label('Sandbox')->default(true),
        ];
    }

    /** @return array<string, mixed> */
    public static function defaultConfig(string $code): array
    {
        return match ($code) {
            'paytr' => ['merchant_id' => '', 'merchant_key' => '', 'merchant_salt' => '', 'test_mode' => true],
            'iyzico' => ['api_key' => '', 'secret_key' => '', 'test_mode' => true],
            'stripe' => ['publishable_key' => '', 'secret_key' => '', 'webhook_secret' => '', 'test_mode' => true],
            'paypal' => ['client_id' => '', 'client_secret' => '', 'test_mode' => true],
            'payoneer' => ['api_username' => '', 'api_token' => '', 'program_id' => '', 'default_currency' => 'USD', 'test_mode' => true],
            default => [],
        };
    }
}
