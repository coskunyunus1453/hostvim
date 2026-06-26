<?php

namespace App\Filament\Resources\SupportTickets\Pages;

use App\Filament\Resources\SupportTickets\SupportTicketResource;
use App\Models\SupportTicket;
use App\Services\SupportTicketService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\EditRecord;

class EditSupportTicket extends EditRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')
                ->label('Yanıtla')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn (): bool => $this->record instanceof SupportTicket && $this->record->isOpen())
                ->form([
                    Textarea::make('body')
                        ->label('Yanıt')
                        ->required()
                        ->rows(6)
                        ->maxLength(10000),
                ])
                ->action(function (array $data, SupportTicketService $support): void {
                    /** @var SupportTicket $ticket */
                    $ticket = $this->record;
                    $user = auth()->user();
                    abort_unless($user !== null, 403);

                    $support->reply($ticket, $user, $data['body'], isStaff: true);
                    $this->refreshFormData(['status', 'last_reply_at', 'last_reply_by']);
                    $this->fillForm();
                }),
        ];
    }
}
