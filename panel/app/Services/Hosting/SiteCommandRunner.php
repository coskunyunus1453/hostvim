<?php

namespace App\Services\Hosting;

use Symfony\Component\Process\Process;

/**
 * Müşteri komutları (cron, git deploy) panel yetkileriyle çalışmamalı:
 * www-data'nın root yardımcılarına şifresiz sudo hakkı var.
 * Sıra: site kullanıcısına düşür (panelze-site-exec) → olmazsa en azından no_new_privs.
 */
class SiteCommandRunner
{
    private const HELPER = '/usr/local/sbin/panelze-site-exec';

    private const SETPRIV = '/usr/bin/setpriv';

    /**
     * @param  list<string>|string  $command  argv veya shell komutu
     */
    public function make(array|string $command, string $cwd, ?float $timeout = 600): Process
    {
        $argv = is_array($command) ? $command : ['/bin/bash', '-c', $command];

        if ($this->helperAvailable()) {
            return new Process(['/usr/bin/sudo', '-n', self::HELPER, $cwd, ...$argv], $cwd, $this->environment(), null, $timeout);
        }

        if (is_executable(self::SETPRIV)) {
            return new Process([self::SETPRIV, '--no-new-privs', '--', ...$argv], $cwd, $this->environment($cwd), null, $timeout);
        }

        return new Process($argv, $cwd, $this->environment($cwd), null, $timeout);
    }

    public function helperAvailable(): bool
    {
        return PHP_OS_FAMILY === 'Linux'
            && is_file(self::HELPER)
            && is_executable(self::HELPER)
            && (bool) config('panelze.site_exec_helper_enabled', true);
    }

    /**
     * Panel sırları (DB_*, APP_KEY, ENGINE_*) müşteri sürecine geçmesin.
     *
     * @return array<string, string|false>
     */
    private function environment(?string $home = null): array
    {
        $env = [
            'PATH' => '/usr/local/bin:/usr/bin:/bin',
            'LANG' => 'C.UTF-8',
            'LC_ALL' => 'C.UTF-8',
        ];
        if ($home !== null && $home !== '') {
            $env['HOME'] = $home;
        }

        foreach (array_keys(getenv()) as $key) {
            if (is_string($key) && $key !== '' && ! array_key_exists($key, $env)) {
                $env[$key] = false;
            }
        }

        return $env;
    }
}
