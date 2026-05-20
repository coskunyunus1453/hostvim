<?php

namespace App\Services\Ai;

use App\Models\Domain;
use App\Models\User;
use App\Services\EngineApiService;
use Illuminate\Support\Facades\Cache;

class AiAssistantContextBuilder
{
    public function __construct(
        private EngineApiService $engine,
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
        $ctx = [
            'mode' => $contextMode,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->isAdmin(),
            ],
            'domains' => $user->domains()
                ->select(['id', 'name', 'status', 'php_version', 'server_type', 'ssl_enabled'])
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
                ])
                ->values()
                ->all(),
        ];

        try {
            $stats = Cache::remember('ai:ctx:server_stats', 30, function (): array {
                return $this->engine->getSystemStats();
            });
            if (empty($stats['error'])) {
                $ctx['server'] = [
                    'cpu_usage' => $stats['cpu']['usage'] ?? null,
                    'memory_usage_percent' => $stats['memory']['usage_percent'] ?? null,
                    'disk_usage_percent' => $stats['disk']['usage_percent'] ?? null,
                    'load' => $stats['load'] ?? null,
                    'uptime' => $stats['uptime'] ?? null,
                ];
            }
        } catch (\Throwable) {
            // Engine offline — context still usable
        }

        try {
            $security = Cache::remember('ai:ctx:security_overview', 45, function (): array {
                return $this->engine->securityOverview();
            });
            if (empty($security['error'])) {
                $ctx['security'] = [
                    'fail2ban' => $security['fail2ban']['enabled'] ?? false,
                    'modsecurity' => $security['modsecurity']['enabled'] ?? false,
                    'clamav' => $security['clamav']['enabled'] ?? false,
                    'firewall_rules' => count($security['firewall']['recent_rules'] ?? []),
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
        if ($locale === 'en') {
            return <<<PROMPT
You are Hostvim AI Assistant — an expert hosting panel copilot for server and website administration.

Capabilities:
- Analyze server health, security posture, domains, logs and site files.
- Explain errors clearly and propose concrete fixes.
- When a file fix is needed, include a JSON block fenced as ```hostvim-actions with schema:
{"fixes":[{"domain_id":1,"path":"relative/path/from/site/root","content":"full new file content","summary":"short reason"}],"tips":["optional tips"]}
- Only suggest fixes the user can apply from the panel. Paths are relative to the site document root unless absolute under /var/www or /home.
- Never invent domain IDs — use IDs from context.domains or selected_domain.
- Be concise, actionable, friendly. Use markdown for readability.

Current panel context (JSON):
{$json}
PROMPT;
        }

        return <<<PROMPT
Sen Hostvim AI Asistanısın — sunucu ve web sitesi yönetimi konusunda uzman bir hosting panel yardımcısısın.

Yeteneklerin:
- Sunucu sağlığı, güvenlik durumu, domainler, loglar ve site dosyalarını analiz et.
- Hataları açıkça açıkla ve somut düzeltmeler öner.
- Dosya düzeltmesi gerekiyorsa ```hostvim-actions ile şu JSON şemasını ekle:
{"fixes":[{"domain_id":1,"path":"site-köküne-göre-yol","content":"dosyanın yeni tam içeriği","summary":"kısa neden"}],"tips":["isteğe bağlı ipuçları"]}
- Yalnızca panelden uygulanabilir düzeltmeler öner. Yollar site document root'una göre relative olmalı.
- Domain ID uydurma — context.domains veya selected_domain içindeki ID'leri kullan.
- Öz, uygulanabilir ve dostane ol. Markdown kullan.

Güncel panel bağlamı (JSON):
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
