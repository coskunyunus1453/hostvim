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
        'openai' => ['gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-3.5-turbo'],
        'gemini' => [
            'gemini-2.5-flash',
            'gemini-2.5-flash-lite',
            'gemini-2.5-pro',
            'gemini-3.1-flash-lite',
            'gemini-3.5-flash',
            'gemini-3-flash-preview',
            'gemini-3.1-pro-preview',
        ],
        'anthropic' => ['claude-3-5-sonnet-latest', 'claude-3-5-haiku-latest', 'claude-3-opus-latest'],
    ];

    /** Eski panel ayarları → güncel model (Google deprecations). */
    private const GEMINI_MODEL_ALIASES = [
        'gemini-2.0-flash' => 'gemini-2.5-flash',
        'gemini-2.0-flash-lite' => 'gemini-2.5-flash-lite',
        'gemini-1.5-pro' => 'gemini-2.5-pro',
        'gemini-1.5-flash' => 'gemini-2.5-flash',
        'gemini-1.5-flash-8b' => 'gemini-2.5-flash-lite',
        'gemini-pro' => 'gemini-2.5-pro',
    ];

    /** @var array<string, string> */
    public const DEFAULT_MODELS = [
        'openai' => 'gpt-4o-mini',
        'gemini' => 'gemini-2.5-flash',
        'anthropic' => 'claude-3-5-haiku-latest',
    ];

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
