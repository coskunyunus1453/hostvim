<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Http\Controllers\Controller;
use App\Services\Billing\BillingSettings;
use App\Services\Domain\Registrar\ResellerClubClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingSettingsController extends Controller
{
    public function __construct(
        private BillingSettings $settings,
        private ResellerClubClient $resellerClub,
    ) {}

    public function show(): JsonResponse
    {
        return response()->json(['settings' => $this->settings->all()]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'currency' => ['nullable', 'string', 'size:3'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_inclusive' => ['nullable', 'boolean'],
            'invoice_prefix' => ['nullable', 'string', 'max:10'],
            'order_prefix' => ['nullable', 'string', 'max:10'],
            'ticket_prefix' => ['nullable', 'string', 'max:10'],
            'due_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'reminder_days_before' => ['nullable', 'array'],
            'reminder_days_before.*' => ['integer', 'min:0', 'max:60'],
            'overdue_reminder_days' => ['nullable', 'array'],
            'overdue_reminder_days.*' => ['integer', 'min:0', 'max:120'],
            'renew_generate_days_before' => ['nullable', 'integer', 'min:0', 'max:60'],
            'suspend_after_days' => ['nullable', 'integer', 'min:0', 'max:120'],
            'terminate_after_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'auto_suspend' => ['nullable', 'boolean'],
            'auto_terminate' => ['nullable', 'boolean'],
            'default_php' => ['nullable', 'string', 'in:7.4,8.0,8.1,8.2,8.3,8.4'],
            'default_server_type' => ['nullable', 'string', 'in:nginx,apache,openlitespeed'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'company_tax_id' => ['nullable', 'string', 'max:60'],
            'support_email' => ['nullable', 'email', 'max:150'],
            'payment_instructions' => ['nullable', 'string', 'max:2000'],
            'payment_provider' => ['nullable', 'string', 'in:auto,paytr,iyzico,stripe,manual'],
            'paytr_enabled' => ['nullable', 'boolean'],
            'iyzico_enabled' => ['nullable', 'boolean'],
            'stripe_enabled' => ['nullable', 'boolean'],
            'paytr_merchant_id' => ['nullable', 'string', 'max:120'],
            'paytr_merchant_key' => ['nullable', 'string', 'max:255'],
            'paytr_merchant_salt' => ['nullable', 'string', 'max:255'],
            'paytr_test_mode' => ['nullable', 'boolean'],
            'paytr_debug_on' => ['nullable', 'boolean'],
            'paytr_timeout_minutes' => ['nullable', 'integer', 'min:1', 'max:120'],
            'iyzico_api_key' => ['nullable', 'string', 'max:255'],
            'iyzico_secret_key' => ['nullable', 'string', 'max:255'],
            'iyzico_test_mode' => ['nullable', 'boolean'],
            'domain_register_enabled' => ['nullable', 'boolean'],
            'domain_registrar' => ['nullable', 'string', 'in:manual,resellerclub'],
            'resellerclub_auth_userid' => ['nullable', 'string', 'max:120'],
            'resellerclub_api_key' => ['nullable', 'string', 'max:255'],
            'resellerclub_test_mode' => ['nullable', 'boolean'],
            'resellerclub_customer_id' => ['nullable', 'integer', 'min:0'],
            'resellerclub_ns1' => ['nullable', 'string', 'max:253'],
            'resellerclub_ns2' => ['nullable', 'string', 'max:253'],
        ]);

        if (isset($validated['currency'])) {
            $validated['currency'] = strtoupper($validated['currency']);
        }

        return response()->json(['settings' => $this->settings->update($validated)]);
    }

    public function testRegistrar(): JsonResponse
    {
        if (! $this->resellerClub->isConfigured()) {
            return response()->json(['ok' => false, 'message' => 'ResellerClub Auth User ID ve API Key girin.'], 422);
        }

        return response()->json($this->resellerClub->ping());
    }
}
