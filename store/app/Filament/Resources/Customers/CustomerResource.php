<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Customers\RelationManagers\DomainsRelationManager;
use App\Filament\Resources\Customers\RelationManagers\OrdersRelationManager;
use App\Filament\Resources\Customers\RelationManagers\TicketsRelationManager;
use App\Filament\Resources\Customers\Tables\CustomersTable;
use App\Models\User;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Müşteriler & Siparişler';

    protected static ?string $navigationLabel = 'Müşteriler';

    protected static ?string $modelLabel = 'Müşteri';

    protected static ?string $pluralModelLabel = 'Müşteriler';

    protected static ?int $navigationSort = 0;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Müşteri Bilgileri')->schema([
                TextEntry::make('name')->label('Ad Soyad'),
                TextEntry::make('email')->label('E-posta')->copyable(),
                TextEntry::make('phone')->label('Telefon')->placeholder('—'),
                TextEntry::make('company')->label('Şirket')->placeholder('—'),
                TextEntry::make('panel_user_id')->label('Panel Hesabı')->badge()
                    ->formatStateUsing(fn ($state): string => $state ? 'Bağlı (#'.$state.')' : 'Yok')
                    ->color(fn ($state): string => $state ? 'success' : 'gray'),
                TextEntry::make('created_at')->label('Kayıt Tarihi')->dateTime('d.m.Y H:i'),
            ])->columns(3),

            Section::make('Özet')->schema([
                TextEntry::make('orders_total')->label('Toplam Sipariş')->badge()->color('info')
                    ->state(fn (User $record): int => $record->orders()->count()),
                TextEntry::make('paid_total')->label('Toplam Harcama')->money('TRY')->weight('bold')
                    ->state(fn (User $record): float => (float) $record->orders()->where('payment_status', 'paid')->sum('total')),
                TextEntry::make('domains_total')->label('Alan Adı')->badge()->color('primary')
                    ->state(fn (User $record): int => $record->domainNames()->count()),
                TextEntry::make('open_tickets')->label('Açık Destek Talebi')->badge()
                    ->state(fn (User $record): int => $record->supportTickets()->where('status', '!=', 'closed')->count())
                    ->color(fn ($state): string => $state > 0 ? 'danger' : 'success'),
            ])->columns(4),

            Section::make('Adres & Fatura Bilgileri')->schema([
                TextEntry::make('address')->label('Adres')->placeholder('—')->columnSpanFull(),
                TextEntry::make('city')->label('İl')->placeholder('—'),
                TextEntry::make('district')->label('İlçe')->placeholder('—'),
                TextEntry::make('postal_code')->label('Posta Kodu')->placeholder('—'),
                TextEntry::make('country')->label('Ülke')->placeholder('—'),
                TextEntry::make('tax_office')->label('Vergi Dairesi')->placeholder('—'),
                TextEntry::make('tax_number')->label('Vergi / TC No')->placeholder('—'),
            ])->columns(3)->collapsible(),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            OrdersRelationManager::class,
            DomainsRelationManager::class,
            TicketsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'view' => ViewCustomer::route('/{record}'),
        ];
    }
}
