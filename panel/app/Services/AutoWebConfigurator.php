<?php

namespace App\Services;

use App\Models\Domain;
use Illuminate\Support\Facades\Log;

class AutoWebConfigurator
{
    public function __construct(
        private EngineApiService $engine,
    ) {}

    /**
     * Site dosyalarından profil çıkarır ve güvenli web ayarını uygular.
     *
     * @return array{profile:string,variant:string,applied:bool,error?:string}
     */
    public function detectAndApply(Domain $domain): array
    {
        [$rootEntries, $publicEntries, $publicHtmlEntries, $publicHtmlPublicEntries] = $this->readDetectionEntries($domain);

        $profile = $this->detectProfile($rootEntries, $publicEntries, $publicHtmlEntries, $publicHtmlPublicEntries);
        $variant = in_array($profile, ['laravel', 'symfony', 'codeigniter4'], true) ? 'public' : 'root';

        $resp = $this->engine->setSiteDocumentRoot($domain->name, $variant, $profile, null);
        if (! empty($resp['error'])) {
            return [
                'profile' => $profile,
                'variant' => $variant,
                'applied' => false,
                'error' => (string) $resp['error'],
            ];
        }

        if (strtolower((string) ($domain->server_type ?? 'nginx')) === 'nginx') {
            $perf = $this->engine->setSitePerformance($domain->name, 'standard');
            if (! empty($perf['error'])) {
                Log::warning('Auto web config: performance apply failed', [
                    'domain' => $domain->name,
                    'error' => $perf['error'],
                ]);
            }
        }

        $nodeConfigured = false;
        if (in_array($profile, ['nextjs', 'nuxt', 'strapi', 'n8n', 'node'], true)) {
            $node = $this->engine->autoConfigureNodeApp($domain->name, $profile);
            $nodeConfigured = empty($node['error']);
            if (! $nodeConfigured) {
                Log::warning('Auto web config: node auto-configure failed', [
                    'domain' => $domain->name,
                    'error' => $node['error'] ?? 'unknown',
                ]);
            }
        }

        return [
            'profile' => $profile,
            'variant' => $variant,
            'applied' => true,
            'node_configured' => $nodeConfigured,
        ];
    }

    /**
     * @return array{0:array<int,array<string,mixed>>,1:array<int,array<string,mixed>>,2:array<int,array<string,mixed>>,3:array<int,array<string,mixed>>}
     */
    private function readDetectionEntries(Domain $domain): array
    {
        $read = function (string $path) use ($domain): array {
            $res = $this->engine->listFilesResult($domain->name, $path, 300, 0, 'name', 'asc');
            if (! empty($res['error'])) {
                return [];
            }

            return (array) ($res['entries'] ?? []);
        };

        return [
            $read(''),
            $read('public'),
            $read('public_html'),
            $read('public_html/public'),
        ];
    }

    /**
     * @param  array<int,array<string,mixed>>  $root
     * @param  array<int,array<string,mixed>>  $public
     * @param  array<int,array<string,mixed>>  $publicHtml
     * @param  array<int,array<string,mixed>>  $publicHtmlPublic
     */
    private function detectProfile(array $root, array $public, array $publicHtml, array $publicHtmlPublic): string
    {
        $hasName = function (array $arr, string $name): bool {
            $n = strtolower($name);
            foreach ($arr as $e) {
                if (strtolower((string) ($e['name'] ?? '')) === $n) {
                    return true;
                }
            }

            return false;
        };
        $hasDir = function (array $arr, string $name): bool {
            $n = strtolower($name);
            foreach ($arr as $e) {
                if (strtolower((string) ($e['type'] ?? '')) === 'directory' && strtolower((string) ($e['name'] ?? '')) === $n) {
                    return true;
                }
            }

            return false;
        };
        $hasNameAny = fn (string $n): bool => $hasName($root, $n) || $hasName($publicHtml, $n);
        $hasDirAny = fn (string $n): bool => $hasDir($root, $n) || $hasDir($publicHtml, $n);
        $hasPublicIndex = $hasName($public, 'index.php') || $hasName($publicHtmlPublic, 'index.php');

        $hasLaravel = (($hasNameAny('artisan') || $hasNameAny('.env')) && $hasPublicIndex);
        if ($hasLaravel) {
            return 'laravel';
        }
        if ($hasDirAny('bin') && $hasNameAny('composer.json') && $hasDirAny('config') && $hasDirAny('src') && $hasPublicIndex) {
            return 'symfony';
        }
        $hasCI3 = $hasDirAny('application') && $hasDirAny('system') && ($hasName($publicHtml, 'index.php') || $hasName($root, 'index.php'));
        $hasCI4 = $hasDirAny('app') && $hasDirAny('writable') && $hasPublicIndex && $hasNameAny('spark');
        if ($hasCI4) {
            return 'codeigniter4';
        }
        if ($hasCI3) {
            return 'codeigniter3';
        }
        if ($hasNameAny('config/settings.inc.php') && $hasDirAny('classes') && $hasDirAny('themes')) {
            return 'prestashop';
        }
        if ($hasNameAny('wp-config.php') || $hasDirAny('wp-content')) {
            if ($hasDirAny('wp-content/plugins/woocommerce')) {
                return 'woocommerce';
            }

            return 'wordpress';
        }
        if ($hasNameAny('LocalSettings.php') && $hasDirAny('includes')) {
            return 'mediawiki';
        }
        if ($hasNameAny('config.php') && $hasDirAny('lib') && $hasNameAny('version.php')) {
            return 'moodle';
        }
        if ($hasDirAny('core') && $hasDirAny('sites')) {
            return 'drupal';
        }
        if ($hasNameAny('configuration.php') && $hasDirAny('administrator')) {
            return 'joomla';
        }
        if ($hasNameAny('config.php') && $hasDirAny('catalog') && $hasDirAny('admin')) {
            return 'opencart';
        }
        if ($hasDirAny('app') && $hasDirAny('vendor') && $hasNameAny('composer.json')) {
            return 'magento';
        }
        if ($hasNameAny('package.json')) {
            if ($hasDirAny('.next') || $hasNameAny('next.config.js') || $hasNameAny('next.config.mjs') || $hasNameAny('next.config.ts')) {
                return 'nextjs';
            }
            if ($hasNameAny('nuxt.config.ts') || $hasNameAny('nuxt.config.js')) {
                return 'nuxt';
            }
            if (($hasNameAny('strapi.config.ts') || $hasNameAny('strapi.config.js')) && $hasDirAny('src') && $hasDirAny('config')) {
                return 'strapi';
            }
            if ($hasNameAny('n8n.config.js') || $hasNameAny('n8n.json')) {
                return 'n8n';
            }

            return 'node';
        }
        if ($hasNameAny('.htaccess') && ! $hasNameAny('nginx.conf') && ! $hasNameAny('.nginx.conf')) {
            return 'htaccess';
        }

        return 'standard';
    }
}
