<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Panel\PanelCustomerService;
use Illuminate\Database\Seeder;

class StoreDemoCustomerSeeder extends Seeder
{
    public const DEMO_EMAIL = 'coskunuygun52@gmail.com';

    public const DEMO_PASSWORD = '14531809';

    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => self::DEMO_EMAIL],
            [
                'name' => 'Coşkun Uygun',
                'password' => self::DEMO_PASSWORD,
                'phone' => '5551234567',
                'is_admin' => false,
                'city' => 'İstanbul',
                'address' => 'Demo Mah. Test Sok. No:1',
            ],
        );

        app(PanelCustomerService::class)->syncPanelUserId($user);

        $this->command?->info('Mağaza demo müşteri:');
        $this->command?->info('  E-posta: '.self::DEMO_EMAIL);
        $this->command?->info('  Şifre:   '.self::DEMO_PASSWORD);
        $this->command?->info('  Giriş:   '.url('/giris'));
        $this->command?->info('  Hesap:   '.url('/hesabim'));
        if ($user->panel_user_id) {
            $this->command?->info('  Panel ID: '.$user->panel_user_id);
        }
    }
}
