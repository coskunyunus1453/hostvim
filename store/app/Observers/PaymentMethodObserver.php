<?php

namespace App\Observers;

use App\Models\PaymentMethod;
use App\Services\Panel\PanelSettingsSyncService;

class PaymentMethodObserver
{
    public function saved(PaymentMethod $paymentMethod): void
    {
        if (! in_array($paymentMethod->code, ['paytr', 'iyzico', 'stripe', 'bank_transfer'], true)) {
            return;
        }

        app(PanelSettingsSyncService::class)->syncBillingSafe();
    }
}
