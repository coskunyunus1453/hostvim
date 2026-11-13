<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AiLlmClient
{
    /**
     * Google Gemini — metin/sohbet (generateContent) için güncel model kimlikleri.
     *
     * @see https://ai.google.dev/gemini-api/docs/models
     *
     * @var array<string, list<string>>
     */
    public const PROVIDER_MODELS = [
        'openai' => [
            'gpt-5.2',
            'gpt-5.1',
            'gpt-5',
            'gpt-5-mini',
            'gpt-5-nano',
            'gpt-4.1',
            'gpt-4.1-mini',
            'gpt-4o',
            'gpt-4o-mini',
        ],
        'gemini' => [
            'gemini-2.5-flash',
            'gemini-2.5-flash-lite',
            'gemini-2.5-pro',
            'gemini-3.1-flash-lite-preview',
            'gemini-3.1-pro-preview',
            'gemini-3-flash-preview',
        ],
        'anthropic' => [
            'claude-sonnet-4-6',
            'claude-opus-4-6',
            'claude-haiku-4-5',
            'claude-sonnet-4-5',
            'claude-3-5-haiku-latest',
        ],
    ];

    /** Eski panel ayarları → güncel model (Google/OpenAI/Anthropic deprecations). */
    private const GEMINI_MODEL_ALIASES = [
        'gemini-2.0-flash' => 'gemini-2.5-flash',
        'gemini-2.0-flash-lite' => 'gemini-2.5-flash-lite',
        'gemini-1.5-pro' => 'gemini-2.5-pro',
        'gemini-1.5-flash' => 'gemini-2.5-flash',
        'gemini-1.5-flash-8b' => 'gemini-2.5-flash-lite',
        'gemini-pro' => 'gemini-2.5-pro',
        'gemini-3.1-flash-lite' => 'gemini-3.1-flash-lite-preview',
        'gemini-3.5-flash' => 'gemini-2.5-flash',
    ];

    /** @var array<string, string> */
    private const OPENAI_MODEL_ALIASES = [
        'gpt-4-turbo' => 'gpt-4.1',
        'gpt-3.5-turbo' => 'gpt-4o-mini',
        'gpt-4' => 'gpt-4.1',
    ];

    /** @var array<string, string> */
    private const ANTHROPIC_MODEL_ALIASES = [
        'claude-3-5-sonnet-latest' => 'claude-sonnet-4-6',
        'claude-3-opus-latest' => 'claude-opus-4-6',
        'claude-3-7-sonnet-latest' => 'claude-sonnet-4-6',
        'claude-sonnet-4-0' => 'claude-sonnet-4-6',
        'claude-opus-4-0' => 'claude-opus-4-6',
    ];

    /** @var array<string, string> */
    public const DEFAULT_MODELS = [
        'openai' => 'gpt-5-mini',
        'gemini' => 'gemini-2.5-flash',
        'anthropic' => 'claude-haiku-4-5',
    ];

    /**
     * Tahmini liste fiyatı (USD / 1M token). Kaynak: sağlayıcı fiyat sayfaları, 2026.
     * Gerçek fatura sağlayıcıya göre değişebilir — panelde bilgilendirme amaçlıdır.
     *
     * @var array<string, array{input: float, output: float, label?: string}>
     */
    public const MODEL_PRICING_USD = [
        // OpenAI
        'gpt-5.2' => ['input' => 1.75, 'output' => 14.00, 'label' => 'GPT-5.2'],
        'gpt-5.1' => ['input' => 1.25, 'output' => 10.00, 'label' => 'GPT-5.1'],
        'gpt-5' => ['input' => 1.25, 'output' => 10.00, 'label' => 'GPT-5'],
        'gpt-5-mini' => ['input' => 0.25, 'output' => 2.00, 'label' => 'GPT-5 Mini'],
        'gpt-5-nano' => ['input' => 0.05, 'output' => 0.40, 'label' => 'GPT-5 Nano'],
        'gpt-4.1' => ['input' => 2.00, 'output' => 8.00, 'label' => 'GPT-4.1'],
        'gpt-4.1-mini' => ['input' => 0.40, 'output' => 1.60, 'label' => 'GPT-4.1 Mini'],
        'gpt-4o' => ['input' => 2.50, 'output' => 10.00, 'label' => 'GPT-4o'],
        'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60, 'label' => 'GPT-4o Mini'],
        // Gemini
        'gemini-2.5-flash' => ['input' => 0.30, 'output' => 2.50, 'label' => 'Gemini 2.5 Flash'],
        'gemini-2.5-flash-lite' => ['input' => 0.10, 'output' => 0.40, 'label' => 'Gemini 2.5 Flash-Lite'],
        'gemini-2.5-pro' => ['input' => 1.25, 'output' => 10.00, 'label' => 'Gemini 2.5 Pro'],
        'gemini-3.1-flash-lite-preview' => ['input' => 0.25, 'output' => 1.50, 'label' => 'Gemini 3.1 Flash-Lite'],
        'gemini-3.1-pro-preview' => ['input' => 2.00, 'output' => 12.00, 'label' => 'Gemini 3.1 Pro'],
        'gemini-3-flash-preview' => ['input' => 0.30, 'output' => 2.50, 'label' => 'Gemini 3 Flash'],
        // Anthropic
        'claude-sonnet-4-6' => ['input' => 3.00, 'output' => 15.00, 'label' => 'Claude Sonnet 4.6'],
        'claude-opus-4-6' => ['input' => 5.00, 'output' => 25.00, 'label' => 'Claude Opus 4.6'],
        'claude-haiku-4-5' => ['input' => 1.00, 'output' => 5.00, 'label' => 'Claude Haiku 4.5'],
        'claude-sonnet-4-5' => ['input' => 3.00, 'output' => 15.00, 'label' => 'Claude Sonnet 4.5'],
        'claude-3-5-haiku-latest' => ['input' => 0.80, 'output' => 4.00, 'label' => 'Claude 3.5 Haiku'],
    ];

    /**
     * @return array{input: float, output: float, label: string}|null
     */
    public static function pricingFor(string $model): ?array
    {
        $model = trim($model);
        if ($model === '') {
            return null;
        }
        $row = self::MODEL_PRICING_USD[$model] ?? null;
        if ($row === null) {
            return null;
        }

        return [
            'input' => (float) $row['input'],
            'output' => (float) $row['output'],
            'label' => (string) ($row['label'] ?? $model),
        ];
    }

    /**
     * @return list<array{model: string, label: string, input_per_1m: float, output_per_1m: float, currency: string}>
     */
    public static function pricingCatalog(string $provider): array
    {
        $provider = strtolower(trim($provider));
        $out = [];
        foreach (self::PROVIDER_MODELS[$provider] ?? [] as $model) {
            $p = self::pricingFor($model);
            $out[] = [
                'model' => $model,
                'label' => $p['label'] ?? $model,
                'input_per_1m' => $p['input'] ?? 0.0,
                'output_per_1m' => $p['output'] ?? 0.0,
                'currency' => 'USD',
            ];
        }

        return $out;
    }

    public static function estimateCostUsd(string $model, int $promptTokens, int $completionTokens): float
    {
        $p = self::pricingFor($model);
        if ($p === null) {
            return 0.0;
        }

        return ($promptTokens / 1_000_000) * $p['input']
            + ($completionTokens / 1_000_000) * $p['output'];
    }
    /**
     * @return array{ok: bool, message: string, latency_ms?: int}
     */
    public function testConnection(string $provider, string $apiKey, ?string $model = null): array
    {
        $model = $this->resolveModel($provider, $model);
        $t0 = microtime(true);
        try {
            $result = $this->chat($provider, $apiKey, $model, [
                ['role' => 'user', 'content' => 'Reply with exactly: OK'],
            ], 16, null);
            $latency = (int) round((microtime(true) - $t0) * 1000);
            $text = strtolower(trim($result['content']));
            $ok = str_contains($text, 'ok');

            return [
                'ok' => $ok,
                'message' => $ok ? 'Bağlantı başarılı.' : 'Yanıt alındı fakat beklenen onay gelmedi: '.Str::limit($result['content'], 120),
                'latency_ms' => $latency,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => $e->getMessage(),
                'latency_ms' => (int) round((microtime(true) - $t0) * 1000),
            ];
        }
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @return array{content: string, prompt_tokens: int, completion_tokens: int, model: string}
     */
    public function chat(
        string $provider,
        string $apiKey,
        string $model,
        array $messages,
        int $maxTokens = 4096,
        ?string $systemPrompt = null,
    ): array {
        $provider = strtolower(trim($provider));
        $model = $this->resolveModel($provider, $model);

        return match ($provider) {
            'openai' => $this->chatOpenAi($apiKey, $model, $messages, $maxTokens, $systemPrompt),
            'gemini' => $this->chatGemini($apiKey, $model, $messages, $maxTokens, $systemPrompt),
            'anthropic' => $this->chatAnthropic($apiKey, $model, $messages, $maxTokens, $systemPrompt),
            default => throw new \InvalidArgumentException('Unsupported AI provider: '.$provider),
        };
    }

    public function resolveModel(string $provider, ?string $model): string
    {
        $provider = strtolower(trim($provider));
        $model = trim((string) $model);
        if ($model === '') {
            return self::DEFAULT_MODELS[$provider] ?? 'gpt-4o-mini';
        }

        if ($provider === 'gemini') {
            return self::GEMINI_MODEL_ALIASES[$model] ?? $model;
        }
        if ($provider === 'openai') {
            return self::OPENAI_MODEL_ALIASES[$model] ?? $model;
        }
        if ($provider === 'anthropic') {
            return self::ANTHROPIC_MODEL_ALIASES[$model] ?? $model;
        }

        return $model;
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @return array{content: string, prompt_tokens: int, completion_tokens: int, model: string}
     */
    private function chatOpenAi(string $apiKey, string $model, array $messages, int $maxTokens, ?string $systemPrompt): array
    {
        $payloadMessages = [];
        if ($systemPrompt) {
            $payloadMessages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        foreach ($messages as $m) {
            if (! in_array($m['role'], ['system', 'user', 'assistant'], true)) {
                continue;
            }
            $payloadMessages[] = ['role' => $m['role'], 'content' => $m['content']];
        }

        $response = Http::timeout(120)
            ->withToken($apiKey)
            ->acceptJson()
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => $payloadMessages,
                'max_tokens' => $maxTokens,
                'temperature' => 0.4,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException($this->formatHttpError('OpenAI', $response->status(), $response->json()));
        }

        $json = $response->json();
        $content = (string) ($json['choices'][0]['message']['content'] ?? '');
        $usage = is_array($json['usage'] ?? null) ? $json['usage'] : [];

        return [
            'content' => $content,
            'prompt_tokens' => (int) ($usage['prompt_tokens'] ?? $this->estimateTokens(implode("\n", array_column($messages, 'content')))),
            'completion_tokens' => (int) ($usage['completion_tokens'] ?? $this->estimateTokens($content)),
            'model' => (string) ($json['model'] ?? $model),
        ];
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @return array{content: string, prompt_tokens: int, completion_tokens: int, model: string}
     */
    private function chatGemini(string $apiKey, string $model, array $messages, int $maxTokens, ?string $systemPrompt): array
    {
        $contents = [];
        foreach ($messages as $m) {
            $role = $m['role'] === 'assistant' ? 'model' : 'user';
            if ($m['role'] === 'system') {
                continue;
            }
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $m['content']]],
            ];
        }

        if ($contents === []) {
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => 'Hello']],
            ];
        }

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            rawurlencode($model),
            rawurlencode($apiKey)
        );

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'maxOutputTokens' => $maxTokens,
                'temperature' => 0.4,
            ],
        ];
        if ($systemPrompt) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $systemPrompt]],
            ];
        }

        $response = Http::timeout(120)
            ->acceptJson()
            ->post($url, $payload);

        if (! $response->successful()) {
            throw new \RuntimeException($this->formatHttpError('Gemini', $response->status(), $response->json()));
        }

        $json = $response->json();
        $parts = $json['candidates'][0]['content']['parts'] ?? [];
        $textParts = [];
        foreach ($parts as $part) {
            if (is_array($part) && isset($part['text'])) {
                $textParts[] = (string) $part['text'];
            }
        }
        $content = trim(implode("\n", $textParts));
        $usage = is_array($json['usageMetadata'] ?? null) ? $json['usageMetadata'] : [];

        return [
            'content' => $content,
            'prompt_tokens' => (int) ($usage['promptTokenCount'] ?? 0),
            'completion_tokens' => (int) ($usage['candidatesTokenCount'] ?? 0),
            'model' => $model,
        ];
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @return array{content: string, prompt_tokens: int, completion_tokens: int, model: string}
     */
    private function chatAnthropic(string $apiKey, string $model, array $messages, int $maxTokens, ?string $systemPrompt): array
    {
        $anthropicMessages = [];
        foreach ($messages as $m) {
            if ($m['role'] === 'system') {
                continue;
            }
            $anthropicMessages[] = [
                'role' => $m['role'] === 'assistant' ? 'assistant' : 'user',
                'content' => $m['content'],
            ];
        }

        $payload = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'messages' => $anthropicMessages,
            'temperature' => 0.4,
        ];
        if ($systemPrompt) {
            $payload['system'] = $systemPrompt;
        }

        $response = Http::timeout(120)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->acceptJson()
            ->post('https://api.anthropic.com/v1/messages', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException($this->formatHttpError('Claude', $response->status(), $response->json()));
        }

        $json = $response->json();
        $blocks = is_array($json['content'] ?? null) ? $json['content'] : [];
        $textParts = [];
        foreach ($blocks as $block) {
            if (is_array($block) && ($block['type'] ?? '') === 'text') {
                $textParts[] = (string) ($block['text'] ?? '');
            }
        }
        $content = trim(implode("\n", $textParts));
        $usage = is_array($json['usage'] ?? null) ? $json['usage'] : [];

        return [
            'content' => $content,
            'prompt_tokens' => (int) ($usage['input_tokens'] ?? 0),
            'completion_tokens' => (int) ($usage['output_tokens'] ?? 0),
            'model' => (string) ($json['model'] ?? $model),
        ];
    }

    private function formatHttpError(string $vendor, int $status, mixed $json): string
    {
        if (is_array($json)) {
            $msg = $json['error']['message'] ?? $json['error'] ?? $json['message'] ?? null;
            if (is_string($msg) && $msg !== '') {
                return sprintf('%s API hatası (%d): %s', $vendor, $status, $msg);
            }
        }

        return sprintf('%s API hatası (%d)', $vendor, $status);
    }

    private function estimateTokens(string $text): int
    {
        $len = mb_strlen($text);

        return max(1, (int) ceil($len / 4));
    }
}
