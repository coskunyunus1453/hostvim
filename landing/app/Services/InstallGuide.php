<?php

namespace App\Services;

use App\Models\LandingSiteSetting;

/**
 * Müşteriye gösterilecek gerçek kurulum komutları — deploy/ betikleriyle uyumlu.
 */
final class InstallGuide
{
    /**
     * @return array<string, string>
     */
    public static function settings(): array
    {
        return [
            'get_url' => self::resolve('landing.install_get_url', 'panelze.install_one_liner_url'),
            'community_script' => self::resolve('landing.install_community_script', 'panelze.install_community_script'),
            'pro_script' => self::resolve('landing.install_pro_script', 'panelze.install_pro_script'),
            'remote_script' => self::resolve('landing.install_remote_url', 'panelze.install_remote_url'),
            'motor_script' => self::resolve('landing.install_motor_script', 'panelze.install_motor_script'),
            'repo_url' => self::resolve('landing.install_repo_url', 'panelze.repo_url'),
            'repo_branch' => self::resolve('landing.install_repo_branch', 'panelze.repo_branch'),
            'install_home' => self::resolve('landing.install_home', 'panelze.install_home'),
            'admin_login_file' => self::resolve('landing.install_admin_login_file', 'panelze.admin_login_file'),
        ];
    }

    public static function oneLiner(): string
    {
        $url = self::settings()['get_url'];

        return "curl -fsSL {$url} | bash";
    }

    public static function community(): string
    {
        $url = self::settings()['community_script'];

        return "curl -fsSL {$url} | sudo bash";
    }

    public static function pro(string $licensePlaceholder = 'hv_...'): string
    {
        $url = self::settings()['pro_script'];

        return "HOSTVIM_LICENSE_KEY=\"{$licensePlaceholder}\" curl -fsSL {$url} | sudo bash";
    }

    public static function remote(): string
    {
        $url = self::settings()['remote_script'];

        return "curl -fsSL {$url} | sudo bash";
    }

    public static function manualGitClone(): string
    {
        $s = self::settings();
        $home = rtrim($s['install_home'], '/');
        $parent = dirname($home);
        $repo = $s['repo_url'];
        $branch = $s['repo_branch'];

        return <<<BASH
sudo mkdir -p {$parent}
sudo chown "\$USER":"\$USER" {$parent}
git clone {$repo} {$home}
cd {$home}
git checkout {$branch}
sudo bash deploy/bootstrap/install-production.sh
BASH;
    }

    /** @return array{label: string, command: string, note?: string}[] */
    public static function sectionsForLocale(string $locale): array
    {
        $s = self::settings();
        $home = rtrim($s['install_home'], '/');
        $branch = $s['repo_branch'];
        $repo = $s['repo_url'];
        $adminFile = $s['admin_login_file'];

        if ($locale === 'en') {
            return [
                [
                    'label' => 'One-liner (recommended)',
                    'command' => self::oneLiner(),
                    'note' => 'Runs on Debian/Ubuntu as root or sudo. Equivalent to the Community installer via get.panelze.sh.',
                ],
                [
                    'label' => 'Community (Freemium hosting panel)',
                    'command' => self::community(),
                    'note' => 'Sets APP_PROFILE=customer, clones '.$repo.' (branch '.$branch.'), runs install-production.sh.',
                ],
                [
                    'label' => 'Pro (licensed)',
                    'command' => self::pro('hv_YOUR_KEY'),
                    'note' => 'Replace hv_YOUR_KEY with the key from your purchase email or SaaS dashboard.',
                ],
                [
                    'label' => 'Remote install (git + bootstrap)',
                    'command' => self::remote(),
                    'note' => 'Clones into '.$home.' and executes deploy/bootstrap/install-production.sh.',
                ],
                [
                    'label' => 'Manual (operator)',
                    'command' => self::manualGitClone(),
                    'note' => 'Use when you already have git access and want full control before running bootstrap.',
                ],
                [
                    'label' => 'Panel update (after git pull)',
                    'command' => <<<BASH
cd {$home}
git fetch origin
git checkout {$branch}
git pull --ff-only origin {$branch}
sudo -E bash deploy/scripts/deploy-panel.sh
BASH,
                    'note' => 'Composer, migrations, frontend build — does not rebuild Engine unless you do it separately.',
                ],
                [
                    'label' => 'Rebuild Engine binary',
                    'command' => <<<BASH
cd {$home}/engine
sudo go build -buildvcs=false -o /usr/local/bin/hostvim-engine ./cmd/hostvim-engine
sudo systemctl restart hostvim-engine
BASH,
                    'note' => 'Run when Go/engine code changed.',
                ],
                [
                    'label' => 'Post-install repair',
                    'command' => 'sudo hostvim-post-install',
                    'note' => 'Fixes common MySQL credential drift, permissions, and service wiring after upgrades.',
                ],
                [
                    'label' => 'First admin credentials',
                    'command' => "sudo cat {$adminFile}",
                    'note' => 'Written at the end of install.sh — change the password on first login.',
                ],
            ];
        }

        return [
            [
                'label' => 'Tek satır (önerilen)',
                'command' => self::oneLiner(),
                'note' => 'Debian/Ubuntu sunucuda root veya sudo ile çalıştırın. get.panelze.sh → Community kurulum betiğini çağırır.',
            ],
            [
                'label' => 'Community (Freemium barındırma paneli)',
                'command' => self::community(),
                'note' => 'APP_PROFILE=customer; '.$repo.' deposunu (dal: '.$branch.') klonlar ve install-production.sh çalıştırır.',
            ],
            [
                'label' => 'Pro (lisanslı)',
                'command' => self::pro('hv_ANAHTARINIZ'),
                'note' => 'hv_ANAHTARINIZ yerine satın alma e-postasındaki veya SaaS panelindeki anahtarı yazın.',
            ],
            [
                'label' => 'Uzak kurulum (git + bootstrap)',
                'command' => self::remote(),
                'note' => $home.' dizinine klonlar ve deploy/bootstrap/install-production.sh çalıştırır.',
            ],
            [
                'label' => 'Elle kurulum (operatör)',
                'command' => self::manualGitClone(),
                'note' => 'Git erişiminiz varken bootstrap öncesi tam kontrol istediğinizde.',
            ],
            [
                'label' => 'Panel güncelleme (git pull sonrası)',
                'command' => <<<BASH
cd {$home}
git fetch origin
git checkout {$branch}
git pull --ff-only origin {$branch}
sudo -E bash deploy/scripts/deploy-panel.sh
BASH,
                'note' => 'Composer, migration ve frontend build — Engine ayrıca derlenmelidir.',
            ],
            [
                'label' => 'Engine yeniden derleme',
                'command' => <<<BASH
cd {$home}/engine
sudo go build -buildvcs=false -o /usr/local/bin/hostvim-engine ./cmd/hostvim-engine
sudo systemctl restart hostvim-engine
BASH,
                'note' => 'Go/engine kodu değiştiyse çalıştırın.',
            ],
            [
                'label' => 'Kurulum sonrası onarım',
                'command' => 'sudo hostvim-post-install',
                'note' => 'MySQL kimlik bilgisi, izinler ve servis eşlemesi sorunlarını giderir.',
            ],
            [
                'label' => 'İlk yönetici bilgisi',
                'command' => "sudo cat {$adminFile}",
                'note' => 'install.sh sonunda oluşturulur — ilk girişte parolayı değiştirin.',
            ],
        ];
    }

    private static function resolve(string $settingKey, string $configKey): string
    {
        $fromDb = LandingSiteSetting::getValue($settingKey, '');
        if (is_string($fromDb) && trim($fromDb) !== '') {
            return trim($fromDb);
        }

        return trim((string) config($configKey, ''));
    }
}
