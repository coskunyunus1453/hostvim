<?php

namespace Tests\Feature;

use App\Models\HostingPackage;
use App\Models\Role;
use App\Models\User;
use App\Services\HostingQuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class HostingQuotaTest extends TestCase
{
    use RefreshDatabase;

    private function quota(): HostingQuotaService
    {
        return app(HostingQuotaService::class);
    }

    public function test_admin_has_unlimited_quota(): void
    {
        Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('admin');

        // Admin icin limit yok: istisna atmamali.
        $this->quota()->ensureCanCreateDomain($user);
        $this->assertTrue(true);
    }

    public function test_user_without_package_is_unlimited(): void
    {
        $user = User::factory()->create();

        $this->quota()->ensureCanCreateDomain($user);
        $this->assertTrue(true);
    }

    public function test_domain_limit_is_enforced(): void
    {
        $package = HostingPackage::query()->create([
            'name' => 'Mini',
            'slug' => 'mini',
            'price_monthly' => 10,
            'price_yearly' => 100,
            'is_active' => true,
            'max_domains' => 1,
        ]);
        $user = User::factory()->create(['hosting_package_id' => $package->id]);
        $user->domains()->create(['name' => 'birinci.com', 'document_root' => '/home/test/birinci']);

        // Limit (1) dolu -> ikinci alan adi icin 422 atmali.
        $this->expectException(HttpException::class);
        $this->quota()->ensureCanCreateDomain($user);
    }

    public function test_under_limit_is_allowed(): void
    {
        $package = HostingPackage::query()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price_monthly' => 10,
            'price_yearly' => 100,
            'is_active' => true,
            'max_domains' => 5,
        ]);
        $user = User::factory()->create(['hosting_package_id' => $package->id]);
        $user->domains()->create(['name' => 'tek.com', 'document_root' => '/home/test/tek']);

        // 1/5 dolu -> istisna atmamali.
        $this->quota()->ensureCanCreateDomain($user);
        $this->assertTrue(true);
    }

    public function test_unlimited_when_max_domains_negative(): void
    {
        $package = HostingPackage::query()->create([
            'name' => 'Sinirsiz',
            'slug' => 'sinirsiz',
            'price_monthly' => 10,
            'price_yearly' => 100,
            'is_active' => true,
            'max_domains' => -1,
        ]);
        $user = User::factory()->create(['hosting_package_id' => $package->id]);
        $user->domains()->create(['name' => 'a.com', 'document_root' => '/home/test/a']);
        $user->domains()->create(['name' => 'b.com', 'document_root' => '/home/test/b']);

        // Negatif limit = sinirsiz.
        $this->quota()->ensureCanCreateDomain($user);
        $this->assertTrue(true);
    }
}
