<?php

namespace App\Filament\Resources\PaymentMethods\Pages;

use App\Filament\Resources\PaymentMethods\PaymentMethodResource;
use App\Filament\Resources\PaymentMethods\Schemas\PaymentMethodForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPaymentMethod extends EditRecord
{
    protected static string $resource = PaymentMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $code = (string) ($data['code'] ?? '');
        $config = is_array($data['config'] ?? null) ? $data['config'] : [];

        $data['config'] = array_merge(
            PaymentMethodForm::defaultConfig($code),
            $config,
        );

        if (array_key_exists('test_mode', $data['config'])) {
            $data['config']['test_mode'] = filter_var($data['config']['test_mode'], FILTER_VALIDATE_BOOLEAN);
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $code = (string) ($data['code'] ?? $this->record->code);
        $existing = is_array($this->record->config) ? $this->record->config : [];
        $incoming = is_array($data['config'] ?? null) ? $data['config'] : [];

        foreach (['merchant_key', 'merchant_salt', 'secret_key', 'client_secret', 'api_token', 'webhook_secret'] as $secretKey) {
            if (! array_key_exists($secretKey, $incoming) || ! filled($incoming[$secretKey])) {
                if (isset($existing[$secretKey])) {
                    $incoming[$secretKey] = $existing[$secretKey];
                }
            }
        }

        $data['config'] = array_merge(
            PaymentMethodForm::defaultConfig($code),
            $incoming,
        );

        return $data;
    }
}
