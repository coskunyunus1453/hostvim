<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Mağaza tarafında E2E demo siparişi (panel demo@hostvim.com ile eşleşir).
 *
 * HOSTVIM_SEED_E2E=1 php artisan db:seed --class=E2EDemoStoreSeeder
 */
class E2EDemoStoreSeeder extends Seeder
{
    public const DEMO_EMAIL = 'demo@hostvim.com';

    public const DEMO_PASSWORD = 'DemoHostvim2026!Aa';

    public const DEMO_NAME = 'Demo Müşteri';

    public function run(): void
    {
        if (app()->isProduction() && ! filter_var((string) env('HOSTVIM_SEED_E2E_FORCE', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->command?->warn('E2E demo seed production ortamında engellendi (HOSTVIM_SEED_E2E_FORCE=1 gerekir).');

            return;
        }

        $panelPackageId = (int) env('HOSTVIM_PANEL_PACKAGE_PROFESSIONAL_ID', 0);

        $profesyonel = Product::where('slug', 'profesyonel')->first();
        if ($profesyonel && $panelPackageId > 0) {
            $profesyonel->update(['panel_package_id' => $panelPackageId, 'provision_type' => 'hosting']);
        }

        foreach (['baslangic' => env('HOSTVIM_PANEL_PACKAGE_STARTER_ID'), 'kurumsal' => env('HOSTVIM_PANEL_PACKAGE_ENTERPRISE_ID')] as $slug => $id) {
            if (! $id) {
                continue;
            }
            Product::where('slug', $slug)->update([
                'panel_package_id' => (int) $id,
                'provision_type' => 'hosting',
            ]);
        }

        if ($profesyonel === null) {
            $this->command?->warn('Profesyonel ürün bulunamadı — önce DatabaseSeeder çalıştırın.');

            return;
        }

        $demoUser = User::updateOrCreate(
            ['email' => self::DEMO_EMAIL],
            [
                'name' => self::DEMO_NAME,
                'password' => Hash::make(self::DEMO_PASSWORD),
                'phone' => '+90 532 000 00 00',
                'company' => 'Demo Şirket A.Ş.',
            ],
        );

        $payment = PaymentMethod::where('code', 'bank_transfer')->first();
        $orderNumber = 'HV-E2E-STORE-DEMO';

        $order = Order::updateOrCreate(
            ['order_number' => $orderNumber],
            [
                'user_id' => $demoUser->id,
                'payment_method_id' => $payment?->id,
                'status' => 'completed',
                'payment_status' => 'paid',
                'payment_reference' => 'e2e-demo-transfer',
                'subtotal' => 1298.00,
                'discount_amount' => 0,
                'total' => 1298.00,
                'currency' => 'TRY',
                'billing_cycle' => 'yearly',
                'customer_name' => self::DEMO_NAME,
                'customer_email' => self::DEMO_EMAIL,
                'customer_phone' => '+90 532 000 00 00',
                'customer_company' => 'Demo Şirket A.Ş.',
                'panel_provision_status' => 'completed',
                'panel_provisioned_at' => now(),
                'notes' => 'E2E demo mağaza siparişi',
            ],
        );

        if ($order->items()->count() === 0) {
            OrderItem::create([
                'order_id' => $order->id,
                'item_type' => 'hosting',
                'product_id' => $profesyonel->id,
                'product_name' => $profesyonel->name,
                'quantity' => 1,
                'unit_price' => 999.00,
                'total' => 999.00,
                'billing_cycle' => 'yearly',
                'service_domain' => 'demo-site-hostvim.com',
            ]);

            $domainProduct = Product::updateOrCreate(
                ['slug' => 'domain-com-kayit'],
                [
                    'product_category_id' => $profesyonel->product_category_id,
                    'provision_type' => 'domain',
                    'name' => '.com Domain Kaydı',
                    'short_description' => '1 yıllık .com alan adı',
                    'price_yearly' => 299.00,
                    'currency' => 'TRY',
                    'is_active' => true,
                    'sort_order' => 99,
                ],
            );

            OrderItem::create([
                'order_id' => $order->id,
                'item_type' => 'domain',
                'product_id' => $domainProduct->id,
                'product_name' => $domainProduct->name,
                'quantity' => 1,
                'unit_price' => 299.00,
                'total' => 299.00,
                'billing_cycle' => 'yearly',
                'domain_name' => 'demo-extra-hostvim.com',
                'domain_years' => 1,
            ]);
        }

        $this->command?->info('E2E mağaza siparişi hazır: '.$orderNumber);
        $this->command?->line('  Müşteri: '.self::DEMO_EMAIL.' / '.self::DEMO_PASSWORD);
    }
}
