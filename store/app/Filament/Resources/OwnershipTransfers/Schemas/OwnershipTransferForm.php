<?php

namespace App\Filament\Resources\OwnershipTransfers\Schemas;

use App\Models\OwnershipTransferRequest;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OwnershipTransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Devir Talebi')->schema([
                TextInput::make('number')->label('Talep No')->disabled(),
                TextInput::make('status')->label('Durum')
                    ->formatStateUsing(fn (?string $state): string => OwnershipTransferRequest::statusLabel((string) $state))
                    ->disabled(),
                TextInput::make('type')->label('Tür')
                    ->formatStateUsing(fn (?string $state): string => $state === OwnershipTransferRequest::TYPE_HOSTING ? 'Hosting' : 'Alan adı')
                    ->disabled(),
                TextInput::make('subject_domain')->label('Domain / Hizmet')->disabled(),
                TextInput::make('user.email')->label('Mevcut sahip (kaynak)')->disabled(),
                TextInput::make('target_email')->label('Devralacak (hedef)')->disabled(),
                Textarea::make('customer_note')->label('Müşteri notu')->disabled()->columnSpanFull(),
            ])->columns(2),

            Section::make('İşlem Bilgisi')->schema([
                TextInput::make('panel_synced')->label('Panel senkron')
                    ->formatStateUsing(fn ($state): string => $state ? 'Evet' : 'Hayır')
                    ->disabled(),
                TextInput::make('processed_at')->label('İşlem tarihi')->disabled(),
                Textarea::make('panel_sync_error')->label('Panel senkron hatası')->disabled()->columnSpanFull(),
                Textarea::make('admin_note')->label('Admin notu / red gerekçesi')->disabled()->columnSpanFull(),
            ])->columns(2),
        ]);
    }
}
