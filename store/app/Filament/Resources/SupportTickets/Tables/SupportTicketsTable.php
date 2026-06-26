<?php

namespace App\Filament\Resources\SupportTickets\Tables;

use App\Models\SupportTicket;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupportTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('last_reply_at', 'desc')
            ->columns([
                TextColumn::make('number')->label('No')->searchable()->sortable()->fontFamily('mono'),
                TextColumn::make('user.name')->label('Müşteri')->searchable()->sortable(),
                TextColumn::make('user.email')->label('E-posta')->searchable()->toggleable(),
                TextColumn::make('subject')->label('Konu')->searchable()->limit(40),
                TextColumn::make('department')->label('Departman')
                    ->formatStateUsing(fn (string $state): string => SupportTicket::departmentLabel($state)),
                TextColumn::make('status')->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => SupportTicket::statusLabel($state))
                    ->color(fn (string $state): string => match ($state) {
                        SupportTicket::STATUS_OPEN, SupportTicket::STATUS_CUSTOMER_REPLY => 'warning',
                        SupportTicket::STATUS_ANSWERED => 'success',
                        SupportTicket::STATUS_ON_HOLD => 'gray',
                        SupportTicket::STATUS_CLOSED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('priority')->label('Öncelik')
                    ->formatStateUsing(fn (string $state): string => SupportTicket::priorityLabel($state)),
                TextColumn::make('last_reply_at')->label('Son mesaj')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Durum')->options([
                    SupportTicket::STATUS_OPEN => 'Açık',
                    SupportTicket::STATUS_CUSTOMER_REPLY => 'Müşteri yanıtı',
                    SupportTicket::STATUS_ANSWERED => 'Yanıtlandı',
                    SupportTicket::STATUS_ON_HOLD => 'Beklemede',
                    SupportTicket::STATUS_CLOSED => 'Kapalı',
                ]),
                SelectFilter::make('department')->label('Departman')->options([
                    'general' => 'Genel',
                    'technical' => 'Teknik',
                    'billing' => 'Fatura',
                    'sales' => 'Satış',
                ]),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
