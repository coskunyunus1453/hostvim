<?php

namespace App\Services;

use App\Models\Domain;
use Illuminate\Support\Facades\Log;

/**
 * Site dosya yapısını engine ile tarar; nginx/apache/OLS ve belge kökü önerir, sorunları düzeltir.
 */
class SiteStackAdvisor
{
    public function __construct(
        private EngineApiService $engine,
        private AutoWebConfigurator $autoWeb,
        private SiteStackTranslator $translator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function scan(Domain $domain): array
    {
        $raw = $this->engine->siteStackScan($domain->name);
        if (! empty($raw['error'])) {
            return ['error' => (string) $raw['error']];
        }

        $scan = $this->translator->localizeScan((array) ($raw['scan'] ?? []));
        $issues = (array) ($scan['issues'] ?? []);

        return [
            'domain' => $domain->name,
            'scan' => $scan,
            'issues' => $issues,
            'issue_count' => count($issues),
            'fixable_count' => count(array_filter($issues, fn ($i) => ! empty($i['fixable']))),
            'server_type' => (string) ($domain->server_type ?? 'nginx'),
            'document_root' => (string) ($domain->document_root ?? ''),
            'php_version' => (string) ($domain->php_version ?? ''),
            'summary' => $this->buildSummary($scan, $domain),
        ];
    }

    /**
     * @param  list<string>  $fixIds  boş = tüm düzeltilebilir sorunlar
     * @return array<string, mixed>
     */
    public function applyFixes(Domain $domain, array $fixIds = []): array
    {
        $report = $this->scan($domain);
        if (! empty($report['error'])) {
            return $report;
        }

        $scan = (array) ($report['scan'] ?? []);
        $issues = (array) ($report['issues'] ?? []);
        $allowAll = $fixIds === [];
        $applied = [];
        $errors = [];

        foreach ($issues as $issue) {
            if (! is_array($issue) || empty($issue['fixable'])) {
                continue;
            }
            $fixId = (string) ($issue['fix_id'] ?? '');
            if ($fixId === '' || (! $allowAll && ! in_array($fixId, $fixIds, true))) {
                continue;
            }
            try {
                $this->runFix($domain, $fixId, $scan);
                $applied[] = $fixId;
            } catch (\Throwable $e) {
                $errors[$fixId] = $e->getMessage();
                Log::warning('Site stack fix failed', ['domain' => $domain->name, 'fix' => $fixId, 'error' => $e->getMessage()]);
            }
        }

        // Belge kökü + profil her zaman güncel tespitle hizalansın
        if ($allowAll || in_array('apply_docroot', $fixIds, true) || in_array('full_stack', $fixIds, true)) {
            $auto = $this->autoWeb->detectAndApply($domain->fresh());
            if (! empty($auto['error'])) {
                $errors['apply_docroot'] = (string) $auto['error'];
            } else {
                $applied[] = 'apply_docroot';
                $report['auto_web'] = $auto;
            }
        }

        $domain->refresh();
        $after = $this->scan($domain);

        return [
            'applied' => array_values(array_unique($applied)),
            'errors' => $errors,
            'before' => $report,
            'after' => $after,
            'domain' => $domain->fresh(),
        ];
    }

    /**
     * @param  array<string, mixed>  $scan
     */
    private function runFix(Domain $domain, string $fixId, array $scan): void
    {
        switch ($fixId) {
            case 'remove_user_ini':
                $res = $this->engine->deleteFile($domain->name, '.user.ini');
                if (! empty($res['error'])) {
                    throw new \RuntimeException((string) $res['error']);
                }
                break;
            case 'configure_node':
                $profile = (string) ($scan['profile'] ?? 'node');
                $resp = $this->engine->autoConfigureNodeApp($domain->name, $profile);
                if (! empty($resp['error'])) {
                    throw new \RuntimeException((string) $resp['error']);
                }
                break;
            case 'nginx_perf_standard':
                if (strtolower((string) ($domain->server_type ?? '')) !== 'nginx') {
                    return;
                }
                $perf = $this->engine->setSitePerformance($domain->name, 'standard');
                if (! empty($perf['error'])) {
                    throw new \RuntimeException((string) $perf['error']);
                }
                break;
            case 'apply_docroot':
                // detectAndApply üst fonksiyonda
                break;
            default:
                break;
        }
    }

    /**
     * @param  array<string, mixed>  $scan
     */
    private function buildSummary(array $scan, Domain $domain): string
    {
        $profile = (string) ($scan['profile'] ?? 'standard');
        $runtime = (string) ($scan['runtime'] ?? 'php');
        $server = strtolower((string) ($domain->server_type ?? 'nginx'));
        $conf = (string) ($scan['confidence'] ?? 'low');

        return __('domains.stack_summary', [
            'profile' => $profile,
            'runtime' => $runtime,
            'server' => $server,
            'confidence' => $conf,
        ]);
    }
}
