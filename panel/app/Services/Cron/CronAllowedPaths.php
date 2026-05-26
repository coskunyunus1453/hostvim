<?php

namespace App\Services\Cron;

use App\Models\User;

class CronAllowedPaths
{
    /** Sistem ikilileri — site kökü kontrolüne tabi değil. */
    private const SYSTEM_PREFIXES = [
        '/usr/bin/',
        '/usr/local/bin/',
        '/bin/',
        '/sbin/',
    ];

    /**
     * @return list<string>
     */
    public static function rootsFor(?User $user = null): array
    {
        $roots = [];

        $configured = rtrim(str_replace('\\', '/', (string) config('hostvim.hosting_web_root', '')), '/');
        if ($configured !== '') {
            $roots[] = $configured;
        }

        $panel = rtrim(str_replace('\\', '/', base_path()), '/');
        if ($panel !== '') {
            $roots[] = $panel;
        }

        foreach (['/var/www/hostvim/data/www', '/var/www/data/www', '/home'] as $guess) {
            if (is_dir($guess)) {
                $roots[] = $guess;
            }
        }

        if ($user !== null) {
            $wwwBases = array_values(array_unique(array_filter($roots)));
            $domains = $user->domains()->get(['document_root', 'name']);
            foreach ($domains as $domain) {
                $name = strtolower(trim((string) $domain->name));
                if ($name === '') {
                    continue;
                }

                foreach ($wwwBases as $base) {
                    $site = $base.'/'.$name;
                    $roots[] = $site;
                    $roots[] = $site.'/public_html';
                    $roots[] = $site.'/public_html/public';
                }

                $doc = rtrim(str_replace('\\', '/', (string) $domain->document_root), '/');
                if ($doc === '') {
                    continue;
                }
                $roots[] = $doc;
                $parent = dirname($doc);
                if ($parent !== '.' && $parent !== '/') {
                    $roots[] = $parent;
                }
                $siteRoot = dirname($parent);
                if ($siteRoot !== '.' && $siteRoot !== '/') {
                    $roots[] = $siteRoot;
                }
            }
        }

        return array_values(array_unique(array_filter($roots)));
    }

    public static function isSystemBinaryPath(string $path): bool
    {
        $normalized = str_replace('\\', '/', $path);
        foreach (self::SYSTEM_PREFIXES as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /** Cron çıktısını yok saymak için standart hedefler (>> /dev/null 2>&1). */
    public static function isSafeDevSink(string $path): bool
    {
        $normalized = rtrim(str_replace('\\', '/', $path), '/');

        return $normalized === '/dev/null' || $normalized === '/dev/zero';
    }

    public static function isAllowed(string $path, ?User $user = null): bool
    {
        if (self::isSystemBinaryPath($path)) {
            return true;
        }

        if ($user !== null && self::matchesOwnedDomainSegment($path, $user)) {
            return true;
        }

        $normalized = str_replace('\\', '/', $path);
        $real = @realpath($path);
        $realNorm = is_string($real) ? str_replace('\\', '/', $real) : null;

        foreach (self::rootsFor($user) as $root) {
            if ($root === '') {
                continue;
            }
            if ($normalized === $root || str_starts_with($normalized.'/', $root.'/')) {
                return true;
            }
            if ($realNorm !== null) {
                $rootReal = @realpath($root);
                if (is_string($rootReal)) {
                    $rootRealNorm = str_replace('\\', '/', $rootReal);
                    if ($realNorm === $rootRealNorm || str_starts_with($realNorm.'/', $rootRealNorm.'/')) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private static function matchesOwnedDomainSegment(string $path, User $user): bool
    {
        $normalized = str_replace('\\', '/', $path);
        $names = $user->domains()->pluck('name')->map(fn ($n) => strtolower(trim((string) $n)))->filter();

        foreach ($names as $name) {
            if ($name === '') {
                continue;
            }
            if (preg_match('#/(?:data/www|www|home/[^/]+)/'.preg_quote($name, '#').'(?:/|$)#', $normalized) === 1) {
                return true;
            }
            if (str_contains($normalized, '/'.$name.'/') || str_ends_with($normalized, '/'.$name)) {
                return true;
            }
        }

        return false;
    }
}
