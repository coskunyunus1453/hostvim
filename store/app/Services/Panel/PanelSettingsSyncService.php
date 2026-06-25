<?php

namespace App\Services\Panel;

use App\Models\PaymentMethod;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Log;

class PanelSettingsSyncService
{
    public function __construct(private PanelzeApiService $api) {}

    public function syncBillingSafe(): bool
    {
        return $this->syncSafe(['billing' => $this->buildBillingPayload()]);
    }

    public function syncMailSafe(): bool
    {
        return $this->syncSafe(['mail' => $this->buildMailPayload()]);
    }

    public function syncAllSafe(): bool
    {
        return $this->syncSafe([
            'billing' => $this->buildBillingPayload(),
            'mail' => $this->buildMailPayload(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function syncSafe(array $payload): bool
    {
        if (! $this->api->isConfigured()) {
            return false;
        }

        try {
            $this->api->syncSettings($payload);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Panelze ayar senkronu başarısız', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /** @return array<string, mixed> */
    public function buildBillingPayload(): array
    {
        $settings = SiteSetting::query()
            ->whereIn('group', ['billing', 'contact', 'domain', 'general'])
            ->pluck('value', 'key');

        $paytr = PaymentMethod::query()->where('code', 'paytr')->first();
        $iyzico = PaymentMethod::query()->where('code', 'iyzico')->first();
        $stripe = PaymentMethod::query()->where('code', 'stripe')->first();
        $bank = PaymentMethod::query()->where('code', 'bank_transfer')->first();

        $paytrConfig = is_array($paytr?->config) ? $paytr->config : [];
        $iyzicoConfig = is_array($iyzico?->config) ? $iyzico->config : [];

        $defaultProvider = 'manual';
        if ($paytr?->is_active) {
            $defaultProvider = 'paytr';
        } elseif ($iyzico?->is_active) {
            $defaultProvider = 'iyzico';
        } elseif ($stripe?->is_active) {
            $defaultProvider = 'stripe';
        }

        return array_filter([
            'enabled' => $this->bool($settings->get('billing.enabled'), true),
            'currency' => strtoupper((string) ($settings->get('billing.currency') ?: 'TRY')),
            'tax_rate' => (float) ($settings->get('billing.tax_rate') ?: 20),
            'tax_inclusive' => $this->bool($settings->get('billing.tax_inclusive'), false),
            'due_days' => (int) ($settings->get('billing.due_days') ?: 7),
            'renew_generate_days_before' => (int) ($settings->get('billing.renew_generate_days_before') ?: 10),
            'suspend_after_days' => (int) ($settings->get('billing.suspend_after_days') ?: 3),
            'terminate_after_days' => (int) ($settings->get('billing.terminate_after_days') ?: 15),
            'auto_suspend' => $this->bool($settings->get('billing.auto_suspend'), true),
            'auto_terminate' => $this->bool($settings->get('billing.auto_terminate'), false),
            'company_name' => (string) ($settings->get('billing.company_name') ?: $settings->get('site_name') ?: ''),
            'company_tax_id' => (string) ($settings->get('billing.company_tax_id') ?: ''),
            'company_address' => (string) ($settings->get('billing.company_address') ?: ''),
            'support_email' => (string) ($settings->get('contact_email') ?: $settings->get('billing.support_email') ?: ''),
            'payment_instructions' => (string) ($bank?->instructions ?: $settings->get('billing.payment_instructions') ?: ''),
            'payment_provider' => (string) ($settings->get('billing.payment_provider') ?: $defaultProvider),
            'paytr_enabled' => (bool) $paytr?->is_active,
            'iyzico_enabled' => (bool) $iyzico?->is_active,
            'stripe_enabled' => (bool) $stripe?->is_active,
            'paytr_merchant_id' => (string) ($paytrConfig['merchant_id'] ?? ''),
            'paytr_merchant_key' => (string) ($paytrConfig['merchant_key'] ?? ''),
            'paytr_merchant_salt' => (string) ($paytrConfig['merchant_salt'] ?? ''),
            'paytr_test_mode' => $this->bool($paytrConfig['test_mode'] ?? false, false),
            'iyzico_api_key' => (string) ($iyzicoConfig['api_key'] ?? ''),
            'iyzico_secret_key' => (string) ($iyzicoConfig['secret_key'] ?? ''),
            'iyzico_test_mode' => $this->bool($iyzicoConfig['test_mode'] ?? false, false),
            'domain_register_enabled' => $this->bool($settings->get('domain_register_enabled'), true),
            'domain_registrar' => 'manual',
        ], static fn ($v) => $v !== null);
    }

    /** @return array<string, mixed> */
    public function buildMailPayload(): array
    {
        $settings = SiteSetting::query()
            ->where('key', 'like', 'outbound_mail.%')
            ->pluck('value', 'key');

        $password = $settings->get('outbound_mail.smtp_password');
        $plainPassword = null;
        if (is_string($password) && $password !== '') {
            try {
                $plainPassword = decrypt($password);
            } catch (\Throwable) {
                $plainPassword = $password;
            }
        }

        $payload = [
            'driver' => (string) ($settings->get('outbound_mail.driver') ?: 'smtp'),
            'smtp_host' => (string) ($settings->get('outbound_mail.smtp_host') ?: ''),
            'smtp_port' => (int) ($settings->get('outbound_mail.smtp_port') ?: 587),
            'smtp_username' => (string) ($settings->get('outbound_mail.smtp_username') ?: ''),
            'smtp_encryption' => (string) ($settings->get('outbound_mail.smtp_encryption') ?: ''),
            'from_address' => (string) ($settings->get('outbound_mail.from_address') ?: ''),
            'from_name' => (string) ($settings->get('outbound_mail.from_name') ?: ''),
        ];

        if (is_string($plainPassword) && $plainPassword !== '') {
            $payload['smtp_password'] = $plainPassword;
        }

        return $payload;
    }

    private function bool(mixed $value, bool $default): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
