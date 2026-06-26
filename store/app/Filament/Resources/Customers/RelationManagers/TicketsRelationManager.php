<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\SupportTickets\SupportTicketResource;
use App\Models\SupportTicket;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'supportTickets';

    protected static ?string $title = 'Destek Talepleri';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->label('No')->weight('bold')->searchable(),
                TextColumn::make('subject')->label('Konu')->wrap()->limit(60)->searchable(),
                TextColumn::make('department')->label('Departman')->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? SupportTicket::departmentLabel($state) : '—'),
                TextColumn::make('status')->label('Durum')->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? SupportTicket::statusLabel($state) : '—')
                    ->color(fn (?string $state): string => match ($state) {
                        'open', 'customer_reply' => 'danger',
                        'answered' => 'success',
                        'on_hold' => 'warning',
                        'closed' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('priority')->label('Öncelik')
                    ->formatStateUsing(fn (?string $state): string => $state ? SupportTicket::priorityLabel($state) : '—')
                    ->badge()->color(fn (?string $state): string => match ($state) {
                        'high' => 'danger',
                        'medium' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('last_reply_at')->label('Son Yanıt')->since()->placeholder('—'),
            ])
            ->defaultSort('last_reply_at', 'desc')
            ->recordUrl(fn (SupportTicket $record): string => SupportTicketResource::getUrl('edit', ['record' => $record]))
            ->paginated([10, 25]);
    }
}
