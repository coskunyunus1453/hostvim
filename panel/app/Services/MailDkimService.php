<?php

namespace App\Services;

class MailDkimService
{
    private const KEY_ROOT = '/etc/opendkim/keys';

    /**
     * Alan adı için DKIM TXT değeri (DNS); yoksa null.
     */
    public function txtRecordValueForDomain(string $domain): ?string
    {
        $domain = strtolower(rtrim(trim($domain), '.'));
        if ($domain === '' || ! preg_match('/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$/', $domain)) {
            return null;
        }

        $txtFile = self::KEY_ROOT.'/'.$domain.'/default.txt';
        if (! is_readable($txtFile)) {
            $this->syncDomainKeys([$domain]);

            if (! is_readable($txtFile)) {
                return null;
            }
        }

        $raw = file_get_contents($txtFile);
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        if (preg_match_all('/"([^"]*)"/', $raw, $parts) && ! empty($parts[1])) {
            $value = trim(implode('', $parts[1]));

            return $value !== '' ? $value : null;
        }

        return null;
    }

    /**
     * @param  list<string>  $domains
     */
    public function syncDomainKeys(array $domains): void
    {
        $domains = array_values(array_filter(array_map(
            static fn (string $d) => strtolower(rtrim(trim($d), '.')),
            $domains
        )));

        if ($domains === []) {
            return;
        }

        $script = $this->provisionScriptPath();
        if ($script === null) {
            return;
        }

        $stateDir = $this->engineStateDir();
        if ($stateDir === null) {
            return;
        }

        $cmd = sprintf('sudo -n %s %s 2>/dev/null', escapeshellarg($script), escapeshellarg($stateDir));
        @exec($cmd);
    }

    private function provisionScriptPath(): ?string
    {
        $webRoot = rtrim((string) config('panelze.hosting_web_root'), DIRECTORY_SEPARATOR);
        $panelzeHome = dirname(dirname($webRoot));
        foreach (['/usr/local/sbin/panelze-mail-dkim-sync', $panelzeHome.'/deploy/host/panelze-mail-dkim-sync.sh'] as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    private function engineStateDir(): ?string
    {
        $panelRoot = realpath(dirname(public_path()));
        if ($panelRoot === false) {
            return null;
        }
        $candidates = [
            dirname($panelRoot).'/data/engine-state',
            $panelRoot.'/../data/engine-state',
            dirname($panelRoot).'/engine-state',
        ];
        foreach ($candidates as $dir) {
            $resolved = realpath($dir);
            if ($resolved !== false && is_dir($resolved.'/mail')) {
                return $resolved;
            }
        }

        return null;
    }
}
