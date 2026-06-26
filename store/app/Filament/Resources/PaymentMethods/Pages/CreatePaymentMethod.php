<?php

namespace App\Filament\Resources\PaymentMethods\Pages;

use App\Filament\Resources\PaymentMethods\PaymentMethodResource;
use App\Filament\Resources\PaymentMethods\Schemas\PaymentMethodForm;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentMethod extends CreateRecord
{
    protected static string $resource = PaymentMethodResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $code = (string) ($data['code'] ?? '');
        $data['config'] = array_merge(
            PaymentMethodForm::defaultConfig($code),
            is_array($data['config'] ?? null) ? $data['config'] : [],
        );

        return $data;
    }
}

