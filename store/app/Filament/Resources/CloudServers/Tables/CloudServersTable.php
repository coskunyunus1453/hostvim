<?php

namespace App\Filament\Resources\CloudServers\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CloudServersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.order_number')->label('Sipariş')->searchable(),
                TextColumn::make('hostname')->label('Hostname')->searchable(),
                TextColumn::make('provider_api')->label('API')->badge(),
                TextColumn::make('ipv4')->label('IPv4')->copyable(),
                TextColumn::make('region')->label('Bölge'),
                TextColumn::make('plan')->label('Plan')->toggleable(),
                TextColumn::make('status')->label('Durum')->badge(),
                TextColumn::make('provisioned_at')->label('Kurulum')->dateTime('d.m.Y H:i'),
            ])
            ->defaultSort('id', 'desc');
    }
}
