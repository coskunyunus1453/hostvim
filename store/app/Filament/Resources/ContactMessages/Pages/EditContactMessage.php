<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditContactMessage extends EditRecord
{
    protected static string $resource = ContactMessageResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if (! $this->record->is_read) {
            $this->record->update(['is_read' => true]);
            $this->refreshFormData(['is_read']);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply_email')
                ->label('E-posta ile yanıtla')
                ->icon('heroicon-o-paper-airplane')
                ->url(fn () => 'mailto:'.$this->record->email
                    .'?subject='.rawurlencode('Re: '.($this->record->subject ?: 'Hostvim İletişim'))
                    .'&body='.rawurlencode("Merhaba {$this->record->name},\n\n"))
                ->openUrlInNewTab(),
            Action::make('mark_replied')
                ->label('Yanıtlandı işaretle')
                ->icon('heroicon-o-check-circle')
                ->visible(fn () => $this->record->replied_at === null)
                ->action(function (): void {
                    $this->record->update(['replied_at' => now(), 'is_read' => true]);
                    $this->refreshFormData(['replied_at', 'is_read']);
                    Notification::make()->title('Yanıtlandı olarak işaretlendi')->success()->send();
                }),
            DeleteAction::make(),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Mesaj kaydedildi';
    }
}
