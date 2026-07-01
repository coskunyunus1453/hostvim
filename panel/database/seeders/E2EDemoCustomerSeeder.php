<?php

namespace Database\Seeders;

use App\Models\Domain;
use App\Models\DomainRegistration;
use App\Models\HostingPackage;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\OrderService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Uçtan uca test müşterisi: hosting + domain kaydı + sipariş.
 *
 * Çalıştırma: HOSTVIM_SEED_E2E=1 php artisan db:seed --class=E2EDemoCustomerSeeder
 */
class E2EDemoCustomerSeeder extends Seeder
{
    public const DEMO_EMAIL = 'e2e-demo@panelze.local';

    public const DEMO_PASSWORD = 'DemoHostvim2026!Aa';

    public const DEMO_NAME = 'Demo Müşteri';

    public function run(): void
    {
        if (app()->isProduction() && ! filter_var((string) env('HOSTVIM_SEED_E2E_FORCE', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->command?->warn('E2E demo seed production ortamında engellendi (HOSTVIM_SEED_E2E_FORCE=1 gerekir).');

            return;
        }

        $professional = HostingPackage::query()->where('slug', 'professional')->first()
            ?? HostingPackage::query()->where('is_active', true)->orderBy('sort_order')->first();

        if ($professional === null) {
            $this->command?->warn('Hosting paketi bulunamadı — önce DatabaseSeeder çalıştırın.');

            return;
        }

        $role = Role::query()->where('name', 'user')->where('guard_name', 'web')->firstOrFail();

        $suffix = substr(bin2hex(random_bytes(3)), 0, 6);
        $serviceDomain = "demo-site-{$suffix}.com";
        $extraDomain = "demo-extra-{$suffix}.com";

        $user = User::updateOrCreate(
            ['email' => self::DEMO_EMAIL],
            [
                'name' => self::DEMO_NAME,
                'password' => Hash::make(self::DEMO_PASSWORD),
                'locale' => 'tr',
                'status' => 'active',
                'hosting_package_id' => $professional->id,
                'hosting_package_manual_override' => true,
                'email_verified_at' => now(),
                'force_password_change' => false,
                'onboarding_completed_at' => now(),
            ],
        );
        $user->syncRoles([$role->name]);

        $storeOrderNumber = 'HV-E2E-DEMO-'.strtoupper($suffix);

        $existingOrder = Order::query()->where('store_order_number', $storeOrderNumber)->first();
        if ($existingOrder === null) {
            try {
                /** @var OrderService $orderService */
                $orderService = app(OrderService::class);
                /** @var InvoiceService $invoiceService */
                $invoiceService = app(InvoiceService::class);

                $placed = $orderService->place($user, [
                    [
                        'item_type' => 'hosting',
                        'package_id' => $professional->id,
                        'billing_cycle' => 'yearly',
                        'domain' => $serviceDomain,
                    ],
                    [
                        'item_type' => 'domain_register',
                        'domain' => $extraDomain,
                        'domain_years' => 1,
                    ],
                ]);

                $order = $placed['order'];
                $invoice = $placed['invoice'];

                $order->forceFill([
                    'store_order_number' => $storeOrderNumber,
                    'notes' => 'E2E demo sipariş — otomatik seed',
                ])->save();

                $invoiceService->markPaid($invoice->fresh(), 'e2e_seed', 'seed-'.$storeOrderNumber);
            } catch (\Throwable $e) {
                report($e);
                $this->command?->warn('Provizyon atlandı (engine yok olabilir): '.$e->getMessage());
            }
        } else {
            $storeOrderNumber = (string) $existingOrder->store_order_number;
            $serviceDomain = $existingOrder->items()->where('item_type', 'hosting')->value('domain') ?? $serviceDomain;
            $extraDomain = $existingOrder->items()->where('item_type', 'domain_register')->value('domain') ?? $extraDomain;
        }

        DomainRegistration::query()->updateOrCreate(
            ['domain' => $extraDomain],
            [
                'user_id' => $user->id,
                'years' => 1,
                'status' => DomainRegistration::STATUS_ACTIVE,
                'registrar' => 'manual',
                'source_registrar' => 'panelze',
                'expires_at' => Carbon::now()->addYear(),
                'auto_renew' => true,
                'locked' => false,
                'notes' => 'E2E demo kayıt',
            ],
        );

        if (! $user->domains()->where('name', $serviceDomain)->exists()) {
            Domain::query()->updateOrCreate(
                ['name' => $serviceDomain],
                [
                    'user_id' => $user->id,
                    'status' => 'active',
                    'php_version' => '8.2',
                    'server_type' => 'nginx',
                    'document_root' => 'public_html',
                ],
            );
        }

        $this->command?->info('E2E demo müşteri hazır:');
        $this->command?->line('  E-posta: '.self::DEMO_EMAIL);
        $this->command?->line('  Şifre:   '.self::DEMO_PASSWORD);
        $this->command?->line('  Hosting domain: '.$serviceDomain);
        $this->command?->line('  Kayıtlı domain: '.$extraDomain);
        $this->command?->line('  Mağaza sipariş no: '.$storeOrderNumber);
        $this->command?->line('  Panel kullanıcı ID: '.$user->id);
    }
}
