<?php

namespace App\Filament\Resources\OwnershipTransfers\Pages;

use App\Filament\Resources\OwnershipTransfers\OwnershipTransferResource;
use App\Models\OwnershipTransferRequest;
use App\Services\OwnershipTransferService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditOwnershipTransfer extends EditRecord
{
    protected static string $resource = OwnershipTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Onayla ve devret')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->record instanceof OwnershipTransferRequest && $this->record->isPending())
                ->requiresConfirmation()
                ->modalHeading('Devri onayla')
                ->modalDescription('Sahiplik hedef hesaba aktarılacak ve mümkünse Panelze tarafı da güncellenecektir. Bu işlem geri alınamaz.')
                ->action(function (OwnershipTransferService $service): void {
                    /** @var OwnershipTransferRequest $record */
                    $record = $this->record;

                    try {
                        $service->approve($record, auth()->user());
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title('Devir yapılamadı')
                            ->body(collect($e->errors())->flatten()->implode(' '))
                            ->danger()
                            ->send();

                        return;
                    } catch (\Throwable $e) {
                        Notification::make()->title('Hata')->body($e->getMessage())->danger()->send();

                        return;
                    }

                    $fresh = $record->fresh();
                    if ($fresh && $fresh->panel_synced) {
                        Notification::make()->title('Devir tamamlandı')->success()->send();
                    } else {
                        Notification::make()
                            ->title('Devir yapıldı (panel senkron uyarısı)')
                            ->body('Mağaza tarafı güncellendi ancak Panelze senkronu tamamlanamadı: '.($fresh?->panel_sync_error ?? 'bilinmiyor'))
                            ->warning()
                            ->send();
                    }

                    $this->refreshFormData(['status', 'panel_synced', 'panel_sync_error', 'processed_at', 'admin_note']);
                }),

            Action::make('reject')
                ->label('Reddet')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record instanceof OwnershipTransferRequest && $this->record->isPending())
                ->form([
                    Textarea::make('reason')
                        ->label('Red gerekçesi')
                        ->required()
                        ->rows(3)
                        ->maxLength(1000),
                ])
                ->action(function (array $data, OwnershipTransferService $service): void {
                    /** @var OwnershipTransferRequest $record */
                    $record = $this->record;

                    try {
                        $service->reject($record, $data['reason'], auth()->user());
                    } catch (\Throwable $e) {
                        Notification::make()->title('Hata')->body($e->getMessage())->danger()->send();

                        return;
                    }

                    Notification::make()->title('Talep reddedildi')->success()->send();
                    $this->refreshFormData(['status', 'admin_note', 'processed_at']);
                }),
        ];
    }
}
