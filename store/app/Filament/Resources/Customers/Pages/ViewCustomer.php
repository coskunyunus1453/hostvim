<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Services\CustomerDeletionService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        $deletion = app(CustomerDeletionService::class);

        return [
            DeleteAction::make()
                ->label('Müşteriyi sil')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->visible(fn (): bool => $deletion->canDelete($this->getRecord()))
                ->modalHeading('Müşteriyi sil')
                ->modalDescription(fn (): string => $deletion->modalDescription($this->getRecord()))
                ->successRedirectUrl(CustomerResource::getUrl('index'))
                ->successNotificationTitle('Müşteri silindi'),
        ];
    }
}
