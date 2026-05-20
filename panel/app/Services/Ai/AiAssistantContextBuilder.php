<?php

namespace App\Services\Ai;

use App\Models\CronJob;
use App\Models\Database;
use App\Models\Domain;
use App\Models\User;
use App\Services\EngineApiService;
use App\Services\HostingQuotaService;
use App\Services\SystemStatsNormalizer;
use Illuminate\Support\Facades\Cache;

class AiAssistantContextBuilder
{
    public function __construct(
        private EngineApiService $engine,
        private HostingQuotaService $quota,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(
        User $user,
        string $contextMode,
        ?Domain $domain = null,
        ?string $filePath = null,
        ?string $fileContent = null,
    ): array {
        $locale = str_starts_with((string) ($user->locale ?: app()->getLocale()), 'en') ? 'en' : 'tr';

        $ctx = [
            'assistant_name' => 'PanelZeka',
            'mode' => $contextMode,
            'locale' => $locale,
            'timezone' => config('app.timezone', 'UTC'),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->isAdmin(),
            ],
            'panel_routes' => PanelZekaKnowledge::routes($user->isAdmin()),
            'account' => [
                'domains_count' => $user->domains()->count(),
                'databases_count' => $user->databases()->count(),
                'cron_jobs_count' => CronJob::query()->where('user_id', $user->id)->where('is_system', false)->count(),
                'quota' => $this->quota->cronQuotaSummary($user),
            ],
            'domains' => $user->domains()
                ->select(['id', 'name', 'status', 'php_version', 'server_type', 'ssl_enabled', 'document_root'])
                ->latest()
                ->limit(30)
                ->get()
                ->map(fn (Domain $d) => [
                    'id' => $d->id,
                    'name' => $d->name,
                    'status' => $d->status,
                    'php' => $d->php_version,
                    'server' => $d->server_type,
                    'ssl' => (bool) $d->ssl_enabled,
                    'document_root' => $d->document_root,
                ])
                ->values()
                ->all(),
            'databases' => Database::query()
                ->where('user_id', $user->id)
                ->latest()
                ->limit(20)
                ->get(['id', 'name', 'type', 'domain_id', 'size_mb'])
                ->map(fn (Database $db) => [
                    'id' => $db->id,
                    'name' => $db->name,
                    'type' => $db->type,
                    'domain_id' => $db->domain_id,
                    'size_mb' => $db->size_mb,
                ])
                ->values()
                ->all(),
        ];

        try {
            $raw = Cache::remember('ai:ctx:server_stats', 25, fn (): array => $this->engine->getSystemStats());
            $server = SystemStatsNormalizer::normalize($raw);
            if ($server['available'] ?? false) {
                $ctx['server'] = $server;
            } else {
                $ctx['server_unavailable'] = true;
                $ctx['server_error'] = $server['error'] ?? 'unknown';
            }
        } catch (\Throwable $e) {
            $ctx['server_unavailable'] = true;
            $ctx['server_error'] = $e->getMessage();
        }

        try {
            $security = Cache::remember('ai:ctx:security_overview', 45, fn (): array => $this->engine->securityOverview());
            if (empty($security['error'])) {
                $ctx['security'] = [
                    'fail2ban_enabled' => (bool) ($security['fail2ban']['enabled'] ?? false),
                    'modsecurity_enabled' => (bool) ($security['modsecurity']['enabled'] ?? false),
                    'clamav_enabled' => (bool) ($security['clamav']['enabled'] ?? false),
                    'firewall_rules_count' => count($security['firewall']['recent_rules'] ?? []),
                    'panel_path' => '/security',
                ];
            }
        } catch (\Throwable) {
        }

        if ($domain) {
            $ctx['selected_domain'] = [
                'id' => $domain->id,
                'name' => $domain->name,
                'status' => $domain->status,
                'php_version' => $domain->php_version,
                'server_type' => $domain->server_type,
                'ssl_enabled' => (bool) $domain->ssl_enabled,
                'document_root' => $domain->document_root,
            ];

            try {
                $logs = $this->engine->getSiteLogs($domain->name, 80);
                if (empty($logs['error']) && ! empty($logs['entries'])) {
                    $lines = [];
                    foreach (array_slice($logs['entries'], 0, 40) as $entry) {
                        if (is_array($entry)) {
                            $lines[] = trim((string) ($entry['content'] ?? ''));
                        }
                    }
                    $ctx['site_logs_tail'] = implode("\n", array_filter($lines));
                }
            } catch (\Throwable) {
            }

            if ($filePath && $fileContent === null) {
                try {
                    $fileContent = $this->engine->readFile($domain->name, $filePath);
                } catch (\Throwable $e) {
                    $ctx['file_read_error'] = $e->getMessage();
                }
            }
        }

        if ($filePath) {
            $ctx['file'] = [
                'path' => $filePath,
                'content' => $this->truncate($fileContent ?? '', 48_000),
            ];
        }

        return $ctx;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function toSystemPrompt(array $context, string $locale = 'tr'): string
    {
        $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $routesDoc = json_encode(PanelZekaKnowledge::routes((bool) ($context['user']['is_admin'] ?? false)), JSON_UNESCAPED_UNICODE);
        $actionDoc = PanelZekaKnowledge::actionSchemaDoc($locale);

        if ($locale === 'en') {
            return <<<PROMPT
You are **PanelZeka** — the intelligent copilot built into the Hostvim hosting panel. Always introduce yourself as PanelZeka when greeting.

Your job:
- Analyze server metrics in context.server (CPU, RAM, disk, load, processes) and give actionable advice.
- Guide users to the correct panel page using paths from panel_routes (e.g. /domains, /ssl, /cron).
- Diagnose site errors using logs and files; propose fixes via hostvim-actions (user must approve before apply).
- Never claim metrics are missing if context.server has numeric values.

{$actionDoc}

Panel navigation (JSON):
{$routesDoc}

Current context (JSON):
{$json}
PROMPT;
        }

        return <<<PROMPT
Sen **PanelZeka**'sın — Hostvim hosting panelinin yapay zeka asistanısın. Kendini her zaman PanelZeka olarak tanıt.

Görevlerin:
- context.server içindeki CPU, RAM, disk, load ve süreç verilerini analiz et; uygulanabilir öneriler sun.
- Kullanıcıyı panel_routes yollarıyla doğru sayfaya yönlendir (ör. /domains, /ssl, /cron).
- Site hatalarını log ve dosyalarla teşhis et; düzeltmeleri hostvim-actions ile öner (kullanıcı onaylamadan uygulanmaz).
- context.server sayısal değerler içeriyorsa "veri yok" deme.

{$actionDoc}

Panel menüleri (JSON):
{$routesDoc}

Güncel bağlam (JSON):
{$json}
PROMPT;
    }

    private function truncate(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max)."\n\n[... içerik kısaltıldı ...]";
    }
}
