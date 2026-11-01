<?php

namespace Database\Seeders;

use App\Models\Domain;
use App\Models\HostingPackage;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

/**
 * Public ürün demosu — gerçek müşteri sitelerinden tamamen ayrı user_id scope.
 *
 *   php artisan db:seed --class=PublicDemoSeeder
 *   php artisan panelze:reset-public-demo
 */
class PublicDemoSeeder extends Seeder
{
    public const DEMO_EMAIL = 'demo@panelze.com';

    public const DEMO_PASSWORD = 'DemoPanelze2026!';

    public const DEMO_NAME = 'Panelze Demo';

    /** @var list<array{name:string,title:string,blurb:string}> */
    public const SHOWCASE_SITES = [
        [
            'name' => 'demo-shop.panelze.test',
            'title' => 'Demo Shop',
            'blurb' => 'Örnek e-ticaret sitesi — gerçek müşteri verisi yok.',
        ],
        [
            'name' => 'demo-blog.panelze.test',
            'title' => 'Demo Blog',
            'blurb' => 'Örnek içerik sitesi — yalnızca tanıtım amaçlı.',
        ],
        [
            'name' => 'demo-app.panelze.test',
            'title' => 'Demo App',
            'blurb' => 'Örnek uygulama kökü — dosya yöneticisini deneyin.',
        ],
    ];

    public function run(): void
    {
        $package = HostingPackage::query()->where('is_active', true)->orderBy('sort_order')->first()
            ?? HostingPackage::query()->orderBy('id')->first();

        $role = Role::query()->where('name', 'user')->where('guard_name', 'web')->first();
        if ($role === null) {
            $this->command?->error('user rolü yok — önce PanelRolesAndPermissionsSeeder çalıştırın.');

            return;
        }

        $user = User::updateOrCreate(
            ['email' => self::DEMO_EMAIL],
            [
                'name' => self::DEMO_NAME,
                'password' => Hash::make(self::DEMO_PASSWORD),
                'locale' => 'tr',
                'status' => 'active',
                'is_demo' => true,
                'hosting_package_id' => $package?->id,
                'hosting_package_manual_override' => true,
                'email_verified_at' => now(),
                'force_password_change' => false,
                'onboarding_completed_at' => now(),
                'two_factor_enabled' => false,
                'two_factor_secret' => null,
            ],
        );
        $user->syncRoles([$role->name]);

        // Demo kullanıcının eski sitelerini temizle (yalnızca ona ait olanlar)
        $keep = array_column(self::SHOWCASE_SITES, 'name');
        $user->domains()->whereNotIn('name', $keep)->each(function (Domain $d): void {
            $d->delete();
        });

        $webRoot = rtrim((string) config('panelze.hosting_web_root', '/var/www/hostvim/data/www'), '/');

        foreach (self::SHOWCASE_SITES as $site) {
            $docRoot = $webRoot.'/'.$site['name'].'/public_html';
            $this->writePlaceholderSite($docRoot, $site['title'], $site['blurb']);

            Domain::updateOrCreate(
                ['name' => $site['name']],
                [
                    'user_id' => $user->id,
                    'document_root' => $docRoot,
                    'php_version' => '8.3',
                    'server_type' => 'nginx',
                    'status' => 'active',
                    'ssl_enabled' => false,
                    'is_primary' => $site['name'] === self::SHOWCASE_SITES[0]['name'],
                ],
            );
        }

        $this->command?->info('Public demo hazır:');
        $this->command?->line('  E-posta: '.self::DEMO_EMAIL);
        $this->command?->line('  Şifre:   '.self::DEMO_PASSWORD);
        $this->command?->line('  Siteler: '.implode(', ', $keep));
    }

    private function writePlaceholderSite(string $docRoot, string $title, string $blurb): void
    {
        File::ensureDirectoryExists($docRoot);
        $html = <<<HTML
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$title} — Panelze Demo</title>
  <style>
    body{font-family:ui-sans-serif,system-ui,sans-serif;margin:0;min-height:100vh;display:grid;place-items:center;
      background:radial-gradient(800px 400px at 20% 0%,#0ea5e922,transparent),#0f172a;color:#e2e8f0}
    main{max-width:36rem;padding:2rem;text-align:center}
    h1{font-size:1.75rem;margin:0 0 .75rem}
    p{opacity:.85;line-height:1.55}
    .badge{display:inline-block;margin-bottom:1rem;padding:.35rem .75rem;border-radius:999px;
      background:#0369a1;font-size:.75rem;font-weight:600;letter-spacing:.04em}
  </style>
</head>
<body>
  <main>
    <div class="badge">PANELZE PUBLIC DEMO</div>
    <h1>{$title}</h1>
    <p>{$blurb}</p>
    <p>Bu dosya yalnızca tanıtım amaçlıdır. Gerçek müşteri siteleri bu hesapta görünmez.</p>
  </main>
</body>
</html>
HTML;
        File::put($docRoot.'/index.html', $html);
        if (! File::exists($docRoot.'/index.php')) {
            File::put($docRoot.'/index.php', "<?php\nreadfile(__DIR__.'/index.html');\n");
        }
    }
}
