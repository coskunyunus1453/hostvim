<?php

namespace App\Services\Integrations;

use App\Models\PanelSetting;
use App\Services\Billing\BillingSettings;
use App\Services\OutboundMailConfigurator;
use Illuminate\Support\Facades\Cache;

class StoreSettingsApplier
{
    public function __construct(private BillingSettings $billing) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{billing: bool, mail: bool}
     */
    public function apply(array $payload): array
    {
        $result = ['billing' => false, 'mail' => false];

        if (! empty($payload['billing']) && is_array($payload['billing'])) {
            $this->billing->update($payload['billing']);
            $result['billing'] = true;
        }

        if (! empty($payload['mail']) && is_array($payload['mail'])) {
            $this->applyMail($payload['mail']);
            $result['mail'] = true;
        }

        return $result;
    }

    /** @param array<string, mixed> $mail */
    private function applyMail(array $mail): void
    {
        $set = function (string $key, ?string $value): void {
            PanelSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '']
            );
        };

        if (isset($mail['driver']) && is_string($mail['driver'])) {
            $set('outbound_mail.driver', $mail['driver']);
        }

        foreach ([
            'smtp_host' => 'outbound_mail.smtp_host',
            'smtp_username' => 'outbound_mail.smtp_username',
            'smtp_encryption' => 'outbound_mail.smtp_encryption',
            'from_address' => 'outbound_mail.from_address',
            'from_name' => 'outbound_mail.from_name',
        ] as $in => $out) {
            if (array_key_exists($in, $mail)) {
                $set($out, is_string($mail[$in]) ? $mail[$in] : '');
            }
        }

        if (isset($mail['smtp_port'])) {
            $set('outbound_mail.smtp_port', (string) (int) $mail['smtp_port']);
        }

        if (! empty($mail['smtp_password']) && is_string($mail['smtp_password'])) {
            $set('outbound_mail.smtp_password', $mail['smtp_password']);
        }

        if (! empty($mail['clear_smtp_password'])) {
            $set('outbound_mail.smtp_password', '');
        }

        Cache::forget('outbound_mail.config');
        OutboundMailConfigurator::forgetCache();
        OutboundMailConfigurator::apply();
    }
}
