<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminEmail = env('PANELZE_ADMIN_EMAIL', 'admin@panelze.local');
        $adminPassword = env('PANELZE_ADMIN_PASSWORD', 'password');

        $admin = User::query()->updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Admin',
                'password' => Hash::make($adminPassword),
                'email_verified_at' => now(),
            ]
        );
        if (env('PANELZE_ADMIN_PASSWORD')) {
            $admin->password = Hash::make($adminPassword);
            $admin->save();
        }
        $admin->forceFill(['is_admin' => true])->save();

        $this->call(LandingSettingsSeeder::class);
        $this->call(ContentSeeder::class);
        $this->call(NavMenuSeeder::class);
        $this->call(LegalSitePagesSeeder::class);
        $this->call(LegalNavFooterSeeder::class);
        $this->call(SaasBootstrapSeeder::class);
    }
}
