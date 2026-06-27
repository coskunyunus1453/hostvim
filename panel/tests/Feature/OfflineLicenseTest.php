<?php

namespace Tests\Feature;

use App\Services\OfflineLicenseService;
use Tests\TestCase;

class OfflineLicenseTest extends TestCase
{
    private OfflineLicenseService $svc;

    private string $public;

    private string $secret;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new OfflineLicenseService;
        $kp = $this->svc->generateKeypair();
        $this->public = $kp['public'];
        $this->secret = $kp['secret'];
        config(['panelze.license.public_key' => $this->public]);
    }

    public function test_valid_key_passes_with_features_and_plan(): void
    {
        $key = $this->svc->issue([
            'to' => 'Acme',
            'plan' => 'enterprise',
            'dom' => ['panel.acme.com'],
            'exp' => time() + 86400,
            'feat' => ['phpmyadmin_sso', 'security_pro'],
        ], $this->secret);

        $r = $this->svc->verify($key, 'panel.acme.com');

        $this->assertTrue($r['valid']);
        $this->assertSame('enterprise', $r['plan']);
        $this->assertTrue($r['features']['phpmyadmin_sso']['enabled']);
        $this->assertSame('offline', $r['source']);
    }

    public function test_perpetual_key_has_null_expiry(): void
    {
        $key = $this->svc->issue(['plan' => 'pro', 'exp' => 0], $this->secret);
        $r = $this->svc->verify($key);
        $this->assertTrue($r['valid']);
        $this->assertNull($r['expires_at']);
    }

    public function test_expired_key_is_invalid(): void
    {
        $key = $this->svc->issue(['plan' => 'pro', 'exp' => time() - 30 * 86400, 'grace' => 0], $this->secret);
        $r = $this->svc->verify($key);
        $this->assertFalse($r['valid']);
        $this->assertSame('expired', $r['code']);
    }

    public function test_grace_period_still_valid(): void
    {
        $key = $this->svc->issue(['plan' => 'pro', 'exp' => time() - 3600, 'grace' => 14], $this->secret);
        $r = $this->svc->verify($key);
        $this->assertTrue($r['valid']);
        $this->assertSame('grace', $r['status']);
    }

    public function test_domain_mismatch_is_invalid(): void
    {
        $key = $this->svc->issue(['plan' => 'pro', 'dom' => ['panel.acme.com'], 'exp' => 0], $this->secret);
        $r = $this->svc->verify($key, 'baska.com');
        $this->assertFalse($r['valid']);
        $this->assertSame('domain_mismatch', $r['code']);
    }

    public function test_wildcard_domain_matches_subdomain(): void
    {
        $key = $this->svc->issue(['plan' => 'pro', 'dom' => ['*.acme.com'], 'exp' => 0], $this->secret);
        $this->assertTrue($this->svc->verify($key, 'panel.acme.com')['valid']);
    }

    public function test_tampered_signature_is_invalid(): void
    {
        $key = $this->svc->issue(['plan' => 'pro', 'exp' => 0], $this->secret);
        // İmza parçasının ilk karakterini değiştir (uzunluğu koruyarak).
        $parts = explode('.', $key);
        $sig = $parts[2];
        $parts[2] = ($sig[0] === 'A' ? 'B' : 'A').substr($sig, 1);
        $r = $this->svc->verify(implode('.', $parts));
        $this->assertFalse($r['valid']);
        $this->assertContains($r['code'], ['signature_invalid', 'malformed']);
    }

    public function test_wrong_public_key_rejects(): void
    {
        $key = $this->svc->issue(['plan' => 'pro', 'exp' => 0], $this->secret);
        $other = $this->svc->generateKeypair();
        $r = $this->svc->verify($key, null, $other['public']);
        $this->assertFalse($r['valid']);
        $this->assertSame('signature_invalid', $r['code']);
    }

    public function test_malformed_keys_are_invalid(): void
    {
        foreach (['', 'abc', 'PLZ1.x', 'PLZ1.x.y', 'NOPE.a.b'] as $bad) {
            $this->assertFalse($this->svc->verify($bad)['valid'], "beklenen geçersiz: {$bad}");
        }
    }
}
