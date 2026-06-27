<?php

namespace Tests\Feature;

use App\Services\LicenseHubClient;
use App\Services\OfflineLicenseService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LicenseHubActivateTest extends TestCase
{
    public function test_activate_posts_key_and_host_and_parses_signed_key(): void
    {
        config([
            'panelze.license_server' => 'https://hub.example.com',
            'panelze.license_server_api_secret' => 'shh',
        ]);

        Http::fake([
            'hub.example.com/api/v1/license/activate' => Http::response([
                'valid' => true,
                'plan' => 'pro',
                'signed_key' => 'PLZ1.payload.sig',
                'bound_host' => 'panel.customer.com',
            ], 200),
        ]);

        $result = (new LicenseHubClient)->activate('hv_key123', 'panel.customer.com');

        $this->assertTrue($result['valid']);
        $this->assertSame('PLZ1.payload.sig', $result['signed_key']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://hub.example.com/api/v1/license/activate'
                && $request['key'] === 'hv_key123'
                && $request['host'] === 'panel.customer.com'
                && $request->hasHeader('Authorization', 'Bearer shh');
        });
    }

    public function test_activate_returns_empty_when_hub_not_configured(): void
    {
        config(['panelze.license_server' => '']);

        $this->assertSame([], (new LicenseHubClient)->activate('hv_key', 'host.com'));
    }

    public function test_signed_key_from_hub_verifies_offline_and_is_domain_bound(): void
    {
        // Hub'ın ürettiği imzalı anahtar panelin OfflineLicenseService'i ile
        // doğrulanmalı (aynı keypair) ve yalnızca bağlı host'ta geçerli olmalı.
        $offline = new OfflineLicenseService;
        $kp = $offline->generateKeypair();

        $signed = $offline->issue([
            'lid' => 'HV-1',
            'plan' => 'pro',
            'feat' => ['phpmyadmin_sso'],
            'dom' => ['panel.customer.com'],
            'exp' => 0,
        ], $kp['secret']);

        $ok = $offline->verify($signed, 'panel.customer.com', $kp['public']);
        $this->assertTrue($ok['valid']);
        $this->assertSame('pro', $ok['plan']);

        $bad = $offline->verify($signed, 'evil.com', $kp['public']);
        $this->assertFalse($bad['valid']);
        $this->assertSame('domain_mismatch', $bad['code']);
    }
}
