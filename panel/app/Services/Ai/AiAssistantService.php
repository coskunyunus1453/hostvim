<?php

namespace App\Services\Ai;

use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\AiProviderConfig;
use App\Models\Domain;
use App\Models\User;
use App\Services\EngineApiService;
use Illuminate\Support\Str;

class AiAssistantService
{
    public function __construct(
        private AiLlmClient $llm,
        private AiAssistantContextBuilder $contextBuilder,
        private EngineApiService $engine,
        private PanelZekaActionExecutor $actionExecutor,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function providerCatalog(): array
    {
        $out = [];
        foreach (array_keys(AiLlmClient::PROVIDER_MODELS) as $provider) {
            $out[] = [
                'provider' => $provider,
                'label' => $this->providerLabel($provider),
                'models' => AiLlmClient::PROVIDER_MODELS[$provider],
                'default_model' => AiLlmClient::DEFAULT_MODELS[$provider],
            ];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function settingsForUser(User $user): array
    {
        $rows = AiProviderConfig::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('provider');

        $providers = [];
        foreach (array_keys(AiLlmClient::PROVIDER_MODELS) as $provider) {
            $row = $rows->get($provider);
            $providers[] = [
                'provider' => $provider,
                'label' => $this->providerLabel($provider),
                'models' => AiLlmClient::PROVIDER_MODELS[$provider],
                'default_model' => AiLlmClient::DEFAULT_MODELS[$provider],
                'enabled' => (bool) ($row?->enabled),
                'is_default' => (bool) ($row?->is_default),
                'model' => $this->llm->resolveModel($provider, $row?->model ?: AiLlmClient::DEFAULT_MODELS[$provider]),
                'api_key_set' => $row !== null && filled($row->api_key),
                'api_key_hint' => $this->maskKey($row?->api_key),
                'last_test_at' => $row?->last_test_at,
                'last_test_ok' => $row?->last_test_ok,
                'last_test_message' => $row?->last_test_message,
            ];
        }

        return [
            'providers' => $providers,
            'catalog' => $this->providerCatalog(),
            'has_active_provider' => collect($providers)->contains(fn ($p) => $p['enabled'] && $p['api_key_set']),
        ];
    }

    /**
     * @param  array<int, array{provider: string, api_key?: string|null, model?: string|null, enabled?: bool, is_default?: bool}>  $items
     */
    public function saveSettings(User $user, array $items): void
    {
        $defaultProvider = null;
        foreach ($items as $item) {
            if (! empty($item['is_default'])) {
                $defaultProvider = strtolower((string) $item['provider']);
            }
        }

        foreach ($items as $item) {
            $provider = strtolower(trim((string) ($item['provider'] ?? '')));
            if (! array_key_exists($provider, AiLlmClient::PROVIDER_MODELS)) {
                continue;
            }

            $row = AiProviderConfig::query()->firstOrNew([
                'user_id' => $user->id,
                'provider' => $provider,
            ]);

            if (array_key_exists('api_key', $item)) {
                $key = trim((string) ($item['api_key'] ?? ''));
                if ($key !== '') {
                    $row->api_key = $key;
                }
            }

            if (isset($item['model'])) {
                $row->model = trim((string) $item['model']) ?: AiLlmClient::DEFAULT_MODELS[$provider];
            } elseif (! $row->exists) {
                $row->model = AiLlmClient::DEFAULT_MODELS[$provider];
            }

            if (array_key_exists('enabled', $item)) {
                $row->enabled = (bool) $item['enabled'];
            }

            $row->is_default = $defaultProvider !== null && $defaultProvider === $provider;
            $row->save();
        }

        if ($defaultProvider) {
            AiProviderConfig::query()
                ->where('user_id', $user->id)
                ->where('provider', '!=', $defaultProvider)
                ->update(['is_default' => false]);
        }
    }

    /**
     * @return array{ok: bool, message: string, latency_ms?: int}
     */
    public function testProvider(User $user, string $provider): array
    {
        $config = $this->requireConfig($user, $provider);
        $result = $this->llm->testConnection($provider, (string) $config->api_key, $config->model);

        $config->last_test_at = now();
        $config->last_test_ok = $result['ok'];
        $config->last_test_message = Str::limit($result['message'], 480);
        $config->save();

        return $result;
    }

    /**
     * @return array{session: AiChatSession, user_message: AiChatMessage, assistant_message: AiChatMessage, actions: array<string, mixed>}
     */
    public function chat(
        User $user,
        string $message,
        ?int $sessionId = null,
        ?int $domainId = null,
        string $contextMode = 'server',
        ?string $filePath = null,
        ?string $providerOverride = null,
    ): array {
        $message = trim($message);
        if ($message === '') {
            throw new \InvalidArgumentException('Mesaj boş olamaz.');
        }

        $config = $this->resolveActiveConfig($user, $providerOverride);
        $domain = null;
        if ($domainId) {
            $domain = Domain::query()->where('user_id', $user->id)->findOrFail($domainId);
        }

        $session = $sessionId
            ? AiChatSession::query()->where('user_id', $user->id)->findOrFail($sessionId)
            : AiChatSession::create([
                'user_id' => $user->id,
                'domain_id' => $domain?->id,
                'title' => Str::limit($message, 60),
                'context_mode' => $contextMode,
            ]);

        if ($domain && ! $session->domain_id) {
            $session->domain_id = $domain->id;
            $session->save();
        }

        $context = $this->contextBuilder->build($user, $contextMode, $domain, $filePath);
        $locale = $user->locale ?: app()->getLocale();
        $systemPrompt = $this->contextBuilder->toSystemPrompt($context, str_starts_with((string) $locale, 'en') ? 'en' : 'tr');

        $history = $session->messages()
            ->orderBy('id')
            ->limit(24)
            ->get(['role', 'content'])
            ->map(fn (AiChatMessage $m) => ['role' => $m->role, 'content' => $m->content])
            ->all();
        $history[] = ['role' => 'user', 'content' => $message];

        $userMsg = AiChatMessage::create([
            'session_id' => $session->id,
            'role' => 'user',
            'content' => $message,
        ]);

        $result = $this->llm->chat(
            (string) $config->provider,
            (string) $config->api_key,
            (string) $config->model,
            $history,
            4096,
            $systemPrompt,
        );

        $actions = $this->parseActions($result['content']);

        $assistantMsg = AiChatMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => $result['content'],
            'provider' => $config->provider,
            'model' => $result['model'],
            'prompt_tokens' => $result['prompt_tokens'],
            'completion_tokens' => $result['completion_tokens'],
            'meta' => $actions !== [] ? ['actions' => $actions] : null,
        ]);

        $session->touch();

        return [
            'session' => $session->fresh(['domain:id,name']),
            'user_message' => $userMsg,
            'assistant_message' => $assistantMsg,
            'actions' => $actions,
        ];
    }

    /**
     * @return array{applied: bool, path: string, message: string}
     */
    public function applyFix(User $user, int $domainId, string $path, string $content): array
    {
        $result = $this->actionExecutor->execute($user, [
            'type' => 'file_write',
            'params' => [
                'domain_id' => $domainId,
                'path' => $path,
                'content' => $content,
            ],
        ]);

        if (! ($result['ok'] ?? false)) {
            throw new \RuntimeException((string) ($result['message'] ?? 'Dosya kaydedilemedi.'));
        }

        return [
            'applied' => true,
            'path' => $path,
            'message' => (string) ($result['message'] ?? 'Dosya güncellendi.'),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $actions
     * @return list<array<string, mixed>>
     */
    public function executeApprovedActions(User $user, array $actions): array
    {
        return $this->actionExecutor->executeBatch($user, $actions);
    }

    /**
     * @return array{ok: bool, message: string, data?: array<string, mixed>}
     */
    public function readFileForUser(User $user, int $domainId, string $path): array
    {
        return $this->actionExecutor->execute($user, [
            'type' => 'read_file',
            'params' => ['domain_id' => $domainId, 'path' => $path],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function usageStats(User $user, int $days = 30): array
    {
        $days = max(7, min(90, $days));
        $since = now()->subDays($days)->startOfDay();

        $messages = AiChatMessage::query()
            ->where('role', 'assistant')
            ->where('created_at', '>=', $since)
            ->whereHas('session', fn ($q) => $q->where('user_id', $user->id))
            ->get(['provider', 'prompt_tokens', 'completion_tokens', 'created_at']);

        $byDay = [];
        $byProvider = [];
        $totalPrompt = 0;
        $totalCompletion = 0;

        foreach ($messages as $m) {
            $day = $m->created_at->format('Y-m-d');
            $byDay[$day] = ($byDay[$day] ?? 0) + 1;
            $provider = $m->provider ?: 'unknown';
            if (! isset($byProvider[$provider])) {
                $byProvider[$provider] = ['requests' => 0, 'prompt_tokens' => 0, 'completion_tokens' => 0];
            }
            $byProvider[$provider]['requests']++;
            $byProvider[$provider]['prompt_tokens'] += (int) $m->prompt_tokens;
            $byProvider[$provider]['completion_tokens'] += (int) $m->completion_tokens;
            $totalPrompt += (int) $m->prompt_tokens;
            $totalCompletion += (int) $m->completion_tokens;
        }

        ksort($byDay);

        $daily = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $daily[] = ['date' => $d, 'requests' => $byDay[$d] ?? 0];
        }

        return [
            'days' => $days,
            'total_requests' => $messages->count(),
            'total_tokens' => $totalPrompt + $totalCompletion,
            'prompt_tokens' => $totalPrompt,
            'completion_tokens' => $totalCompletion,
            'daily' => $daily,
            'by_provider' => collect($byProvider)->map(fn ($v, $k) => array_merge(['provider' => $k], $v))->values()->all(),
        ];
    }

    private function requireConfig(User $user, string $provider): AiProviderConfig
    {
        $provider = strtolower(trim($provider));
        $config = AiProviderConfig::query()
            ->where('user_id', $user->id)
            ->where('provider', $provider)
            ->first();

        if ($config === null || ! filled($config->api_key)) {
            throw new \RuntimeException('Bu sağlayıcı için API anahtarı tanımlı değil.');
        }

        return $config;
    }

    private function resolveActiveConfig(User $user, ?string $providerOverride = null): AiProviderConfig
    {
        if ($providerOverride) {
            $cfg = $this->requireConfig($user, $providerOverride);
            if (! $cfg->enabled) {
                throw new \RuntimeException('Seçilen sağlayıcı etkin değil.');
            }

            return $cfg;
        }

        $config = AiProviderConfig::query()
            ->where('user_id', $user->id)
            ->where('enabled', true)
            ->where('is_default', true)
            ->whereNotNull('api_key')
            ->first();

        if ($config === null) {
            $config = AiProviderConfig::query()
                ->where('user_id', $user->id)
                ->where('enabled', true)
                ->whereNotNull('api_key')
                ->first();
        }

        if ($config === null) {
            throw new \RuntimeException('Aktif AI sağlayıcısı yok. Ayarlar sekmesinden API anahtarı ekleyin.');
        }

        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseActions(string $content): array
    {
        if (! preg_match('/```hostvim-actions\s*([\s\S]*?)```/i', $content, $m)) {
            return [];
        }
        $json = trim($m[1]);
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function providerLabel(string $provider): string
    {
        return match ($provider) {
            'openai' => 'ChatGPT (OpenAI)',
            'gemini' => 'Google Gemini',
            'anthropic' => 'Claude (Anthropic)',
            default => ucfirst($provider),
        };
    }

    private function maskKey(?string $key): ?string
    {
        if (! filled($key)) {
            return null;
        }
        $key = (string) $key;
        if (mb_strlen($key) <= 8) {
            return '••••';
        }

        return mb_substr($key, 0, 4).'••••'.mb_substr($key, -4);
    }
}
