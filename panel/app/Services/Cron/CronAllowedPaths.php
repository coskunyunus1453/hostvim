<?php

namespace App\Services\Cron;

use App\Models\Domain;
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
    public static function rootsFor(?User $user = null, ?Domain $domain = null): array
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

        foreach (['/var/www/panelze/data/www', '/var/www/data/www', '/home'] as $guess) {
            if (is_dir($guess)) {
                $roots[] = $guess;
            }
        }

        if ($domain !== null) {
            $roots = array_merge($roots, self::rootsFromDomainRecord($domain));
        }

        if ($user !== null) {
            $wwwBases = array_values(array_unique(array_filter($roots)));
            $domains = $user->domains()->get(['document_root', 'name']);
            foreach ($domains as $owned) {
                $roots = array_merge($roots, self::rootsFromDomainRecord($owned));
                $name = strtolower(trim((string) $owned->name));
                if ($name === '') {
                    continue;
                }

                foreach ($wwwBases as $base) {
                    $site = $base.'/'.$name;
                    $roots[] = $site;
                    $roots[] = $site.'/public_html';
                    $roots[] = $site.'/public_html/public';
                }
            }
        }

        return array_values(array_unique(array_filter($roots)));
    }

    /**
     * @return list<string>
     */
    public static function rootsFromDomainRecord(Domain $domain): array
    {
        $roots = [];
        $name = strtolower(trim((string) $domain->name));
        $doc = rtrim(str_replace('\\', '/', (string) $domain->document_root), '/');

        if ($doc !== '') {
            $roots[] = $doc;
            $parent = dirname($doc);
            if ($parent !== '.' && $parent !== '/') {
                $roots[] = $parent;
                $grand = dirname($parent);
                if ($grand !== '.' && $grand !== '/') {
                    $roots[] = $grand;
                }
            }
        }

        $configured = rtrim(str_replace('\\', '/', (string) config('hostvim.hosting_web_root', '')), '/');
        if ($configured !== '' && $name !== '') {
            $roots[] = $configured.'/'.$name;
            $roots[] = $configured.'/'.$name.'/public_html';
            $roots[] = $configured.'/'.$name.'/public_html/public';
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

    public static function isSafeDevSink(string $path): bool
    {
        $normalized = rtrim(str_replace('\\', '/', $path), '/');

        return $normalized === '/dev/null' || $normalized === '/dev/zero';
    }

    public static function isPhpBinaryPath(string $path): bool
    {
        $normalized = rtrim(str_replace('\\', '/', $path), '/');

        return (bool) preg_match('#/(?:usr/)?bin/php\d*$#', $normalized);
    }

    public static function isAllowed(string $path, ?User $user = null, ?Domain $domain = null): bool
    {
        if (self::isSystemBinaryPath($path) || self::isPhpBinaryPath($path)) {
            return true;
        }

        if ($domain !== null && self::pathUnderDomain($path, $domain)) {
            return true;
        }

        if ($user !== null && self::matchesOwnedDomainSegment($path, $user)) {
            return true;
        }

        $normalized = str_replace('\\', '/', $path);
        $real = @realpath($path);
        $realNorm = is_string($real) ? str_replace('\\', '/', $real) : null;

        foreach (self::rootsFor($user, $domain) as $root) {
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

    public static function pathUnderDomain(string $path, Domain $domain): bool
    {
        $normalized = str_replace('\\', '/', $path);
        $name = strtolower(trim((string) $domain->name));
        if ($name !== '') {
            if (preg_match('#/(?:data/www|www|home/[^/]+)/'.preg_quote($name, '#').'(?:/|$)#', $normalized) === 1) {
                return true;
            }
            if (str_contains($normalized, '/'.$name.'/') || str_ends_with($normalized, '/'.$name)) {
                return true;
            }
        }

        foreach (self::rootsFromDomainRecord($domain) as $root) {
            if ($root === '') {
                continue;
            }
            if ($normalized === $root || str_starts_with($normalized.'/', $root.'/')) {
                return true;
            }
            $rootReal = @realpath($root);
            $pathReal = @realpath($path);
            if (is_string($rootReal) && is_string($pathReal)) {
                $rootRealNorm = str_replace('\\', '/', $rootReal);
                $pathRealNorm = str_replace('\\', '/', $pathReal);
                if ($pathRealNorm === $rootRealNorm || str_starts_with($pathRealNorm.'/', $rootRealNorm.'/')) {
                    return true;
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
