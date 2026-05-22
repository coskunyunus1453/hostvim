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
        private DomainService $domains,
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

        $engineDocRoot = (string) ($scan['current_doc_root'] ?? '');
        $panelDocRoot = (string) ($domain->document_root ?? '');

        $docrootAligned = ! empty($scan['docroot_aligned'])
            || ($engineDocRoot !== '' && (string) ($scan['recommended_doc_root'] ?? '') !== ''
                && rtrim($engineDocRoot, '/') === rtrim((string) $scan['recommended_doc_root'], '/'));

        return [
            'domain' => $domain->name,
            'scan' => $scan,
            'issues' => $issues,
            'issue_count' => count($issues),
            'fixable_count' => count(array_filter($issues, fn ($i) => ! empty($i['fixable']))),
            'docroot_aligned' => $docrootAligned,
            'server_type' => (string) ($domain->server_type ?? 'nginx'),
            'document_root' => $panelDocRoot,
            'engine_document_root' => $engineDocRoot,
            'document_root_synced' => $panelDocRoot !== '' && $engineDocRoot !== '' && $panelDocRoot === $engineDocRoot,
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
            if ($fixId === '' || $fixId === 'apply_docroot' || (! $allowAll && ! in_array($fixId, $fixIds, true))) {
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

        $needsDocroot = $allowAll
            || in_array('apply_docroot', $fixIds, true)
            || in_array('full_stack', $fixIds, true)
            || $this->scanHasFixable($issues, 'apply_docroot');

        if ($needsDocroot && $this->documentRootsAligned($scan)) {
            $needsDocroot = false;
        }

        if ($needsDocroot) {
            $doc = $this->applyDocumentRoot($domain, $scan);
            if (! empty($doc['error'])) {
                $errors['apply_docroot'] = (string) $doc['error'];
            } else {
                $applied[] = 'apply_docroot';
                $report['document_root_apply'] = $doc;
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
     * @param  list<array<string, mixed>>  $issues
     */
    /**
     * @param  array<string, mixed>  $scan
     */
    private function documentRootsAligned(array $scan): bool
    {
        $cur = rtrim((string) ($scan['current_doc_root'] ?? ''), '/');
        $rec = rtrim((string) ($scan['recommended_doc_root'] ?? ''), '/');

        return $cur !== '' && $rec !== '' && $cur === $rec;
    }

    /**
     * @param  list<array<string, mixed>>  $issues
     */
    private function scanHasFixable(array $issues, string $fixId): bool
    {
        foreach ($issues as $issue) {
            if (is_array($issue) && ($issue['fix_id'] ?? '') === $fixId && ! empty($issue['fixable'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Engine belge kökü + vhost + panel DB kaydını hizalar.
     *
     * @param  array<string, mixed>  $scan
     * @return array<string, mixed>
     */
    private function applyDocumentRoot(Domain $domain, array $scan): array
    {
        $variant = (string) ($scan['recommended_variant'] ?? 'root');
        if (! in_array($variant, ['root', 'public'], true)) {
            $variant = 'root';
        }
        $profile = (string) ($scan['profile'] ?? 'standard');

        $result = $this->domains->setDocumentRootVariant($domain, $variant, $profile);

        if ($variant === 'public' && empty($result['error'])) {
            $norm = $this->engine->normalizeSitePublicUrls($domain->name);
            if (! empty($norm['changed']) && is_array($norm['changed'])) {
                $result['env_normalized'] = $norm['changed'];
            }
            $this->engine->ensureLaravelStorageLink($domain->name);
        }

        return $result;
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
            case 'storage_symlink':
                $res = $this->engine->ensureLaravelStorageLink($domain->name);
                if (! empty($res['error'])) {
                    throw new \RuntimeException((string) $res['error']);
                }
                break;
            case 'normalize_app_url':
                $res = $this->engine->normalizeSitePublicUrls($domain->name);
                if (! empty($res['error'])) {
                    throw new \RuntimeException((string) $res['error']);
                }
                break;
            case 'apply_docroot':
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
