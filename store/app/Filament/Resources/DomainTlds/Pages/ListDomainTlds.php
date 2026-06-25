<?php

namespace App\Filament\Resources\DomainTlds\Pages;

use App\Filament\Resources\DomainTlds\DomainTldResource;
use App\Services\Domain\DomainPricingSyncService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListDomainTlds extends ListRecords
{
    protected static string $resource = DomainTldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncAll')
                ->label('Tüm API\'lerden fiyat çek')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalDescription('Aktif ve yapılandırılmış tüm registrar API\'lerinden fiyatlar çekilir; her TLD için en ucuz toptan fiyat + marj uygulanır.')
                ->action(function (DomainPricingSyncService $sync): void {
                    $result = $sync->syncAll();
                    $body = "Güncellenen TLD: {$result['updated']}, yeni: {$result['created']}";
                    if ($result['errors'] !== []) {
                        $body .= ' | Hatalar: '.implode('; ', $result['errors']);
                    }
                    Notification::make()->title('Fiyat senkronu tamamlandı')->body($body)->success()->send();
                }),
        ];
    }
}
