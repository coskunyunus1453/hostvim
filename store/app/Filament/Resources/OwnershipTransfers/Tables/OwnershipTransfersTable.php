<?php

namespace App\Filament\Resources\OwnershipTransfers\Tables;

use App\Models\OwnershipTransferRequest;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OwnershipTransfersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('number')->label('No')->searchable()->sortable()->fontFamily('mono'),
                TextColumn::make('type')->label('Tür')->badge()
                    ->formatStateUsing(fn (string $state): string => $state === OwnershipTransferRequest::TYPE_HOSTING ? 'Hosting' : 'Alan adı')
                    ->color(fn (string $state): string => $state === OwnershipTransferRequest::TYPE_HOSTING ? 'info' : 'gray'),
                TextColumn::make('subject_domain')->label('Domain / Hizmet')->searchable()->limit(32),
                TextColumn::make('user.email')->label('Kaynak')->searchable()->toggleable(),
                TextColumn::make('target_email')->label('Hedef')->searchable(),
                TextColumn::make('status')->label('Durum')->badge()
                    ->formatStateUsing(fn (string $state): string => OwnershipTransferRequest::statusLabel($state))
                    ->color(fn (string $state): string => match ($state) {
                        OwnershipTransferRequest::STATUS_PENDING => 'warning',
                        OwnershipTransferRequest::STATUS_APPROVED => 'success',
                        OwnershipTransferRequest::STATUS_REJECTED => 'danger',
                        OwnershipTransferRequest::STATUS_CANCELLED => 'gray',
                        default => 'gray',
                    }),
                IconColumn::make('panel_synced')->label('Panel')->boolean()->toggleable(),
                TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Durum')->options([
                    OwnershipTransferRequest::STATUS_PENDING => 'Beklemede',
                    OwnershipTransferRequest::STATUS_APPROVED => 'Onaylandı',
                    OwnershipTransferRequest::STATUS_REJECTED => 'Reddedildi',
                    OwnershipTransferRequest::STATUS_CANCELLED => 'İptal edildi',
                ]),
                SelectFilter::make('type')->label('Tür')->options([
                    OwnershipTransferRequest::TYPE_DOMAIN => 'Alan adı',
                    OwnershipTransferRequest::TYPE_HOSTING => 'Hosting',
                ]),
            ])
            ->recordActions([
                EditAction::make()->label('İncele'),
            ]);
    }
}
