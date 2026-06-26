<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\DomainNames\DomainNameResource;
use App\Models\DomainName;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DomainsRelationManager extends RelationManager
{
    protected static string $relationship = 'domainNames';

    protected static ?string $title = 'Alan Adları';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('domain')->label('Domain')->weight('bold')->searchable(),
                TextColumn::make('registrar_api')->label('Sağlayıcı')->badge()->placeholder('—'),
                TextColumn::make('status')->label('Durum')->badge()->placeholder('—')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'registered', 'active' => 'Kayıtlı',
                        'registering' => 'Kaydediliyor',
                        'pendingTransfer' => 'Transfer Bekliyor',
                        'expired' => 'Süresi Doldu',
                        'failed' => 'Başarısız',
                        default => $state ?: '—',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'registered', 'active' => 'success',
                        'registering' => 'info',
                        'pendingTransfer' => 'warning',
                        'expired', 'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('expires_at')->label('Bitiş')->date('d.m.Y')->placeholder('—')->sortable(),
            ])
            ->defaultSort('expires_at', 'asc')
            ->recordUrl(fn (): string => DomainNameResource::getUrl('index'))
            ->paginated([10, 25]);
    }
}
