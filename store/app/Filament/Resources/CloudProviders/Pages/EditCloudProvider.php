<?php

namespace App\Filament\Resources\CloudProviders\Pages;

use App\Filament\Resources\CloudProviders\CloudProviderResource;
use App\Services\Cloud\Provider\CloudProviderResolver;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCloudProvider extends EditRecord
{
    protected static string $resource = CloudProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test')
                ->label('Bağlantıyı test et')
                ->action(function (CloudProviderResolver $resolver): void {
                    $result = $resolver->driver($this->record->api_name)->testConnection($this->record);
                    $this->record->update([
                        'last_tested_at' => now(),
                        'last_test_status' => $result['ok'] ? 'ok' : 'error',
                        'last_test_message' => $result['message'],
                    ]);
                    Notification::make()
                        ->title($result['ok'] ? 'Bağlantı başarılı' : 'Bağlantı başarısız')
                        ->body($result['message'])
                        ->{$result['ok'] ? 'success' : 'danger'}()
                        ->send();
                }),
        ];
    }
}
