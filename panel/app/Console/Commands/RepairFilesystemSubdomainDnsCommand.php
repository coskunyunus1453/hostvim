<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Services\DomainDnsBootstrapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RepairFilesystemSubdomainDnsCommand extends Command
{
    protected $signature = 'panelze:dns-repair-filesystem
                            {--domain= : Yalnızca bu parent domain}
                            {--dry-run : Kayıt oluşturma, yalnızca listele}';

    protected $description = 'Diskteki alt alan adları için eksik BIND A kayıtlarını tamamlar';

    public function handle(DomainDnsBootstrapService $bootstrap): int
    {
        $webRoot = rtrim((string) config('panelze.hosting_web_root'), '/\\');
        if ($webRoot === '' || ! is_dir($webRoot)) {
            $this->error('hosting_web_root bulunamadı: '.$webRoot);

            return self::FAILURE;
        }

        $filter = strtolower(trim((string) $this->option('domain')));
        $dryRun = (bool) $this->option('dry-run');

        $query = Domain::query()->where('status', 'active')->orderBy('name');
        if ($filter !== '') {
            $query->where('name', $filter);
        }

        $domains = $query->get();
        if ($domains->isEmpty()) {
            $this->warn('Aktif domain yok');

            return self::SUCCESS;
        }

        $totalCreated = 0;
        foreach ($domains as $domain) {
            $hostnames = $this->discoverHostnames($webRoot, $domain->name);
            if ($hostnames === []) {
                continue;
            }

            $this->line("==> {$domain->name} (".count($hostnames).' hostname)');
            foreach ($hostnames as $hostname) {
                if ($dryRun) {
                    $this->line("  [dry-run] {$hostname}");

                    continue;
                }

                $result = $bootstrap->ensureSubdomainDnsRecord($domain, $hostname);
                $created = (int) ($result['created'] ?? 0);
                if ($created > 0) {
                    $this->info("  + A kaydı: {$hostname}");
                    $totalCreated += $created;
                }
            }
        }

        $this->info("Toplam yeni kayıt: {$totalCreated}");

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function discoverHostnames(string $webRoot, string $parent): array
    {
        $parent = strtolower(trim($parent));
        $found = [];

        $subMetaDir = $webRoot.'/'.$parent.'/.panelze/subdomains';
        if (is_dir($subMetaDir)) {
            foreach (glob($subMetaDir.'/*.json') ?: [] as $file) {
                $raw = json_decode((string) file_get_contents($file), true);
                $hostname = strtolower(trim((string) ($raw['hostname'] ?? '')));
                if ($hostname !== '' && str_ends_with($hostname, '.'.$parent)) {
                    $found[$hostname] = true;
                }
            }
        }

        $suffix = '.'.$parent;
        foreach (File::directories($webRoot) as $dir) {
            $name = strtolower(basename($dir));
            if (str_ends_with($name, $suffix) && $name !== $parent) {
                $found[$name] = true;
            }
        }

        $hostnames = array_keys($found);
        sort($hostnames);

        return $hostnames;
    }
}
