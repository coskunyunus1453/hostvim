<?php

namespace App\Services\Domain;

use App\Services\SettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gemini AI ile domain piyasa değeri tahmini.
 */
class DomainAiValuationService
{
    private const CACHE_TTL = 43200; // 12 saat

    /** @var list<string> */
    private const MODELS = [
        'gemini-2.5-flash',
        'gemini-2.0-flash',
    ];

    public function __construct(
        protected SettingsService $settings,
    ) {}

    public function isEnabled(): bool
    {
        return $this->apiKey() !== '';
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{result: array<string, mixed>|null, status: string, error: string|null}
     */
    public function appraiseWithStatus(string $domain, string $sld, string $tldKey, array $context = []): array
    {
        if (! $this->isEnabled()) {
            return ['result' => null, 'status' => 'disabled', 'error' => 'API anahtarı tanımlı değil'];
        }

        if (Cache::get('domain_value_ai:quota_blocked')) {
            return ['result' => null, 'status' => 'failed', 'error' => 'Gemini API kotası dolu — yalnızca kriter motoru kullanıldı'];
        }

        $cacheKey = 'domain_value_ai:v2:'.md5(strtolower($domain));

        $cached = Cache::get($cacheKey);
        if (is_array($cached) && isset($cached['result'])) {
            return $cached;
        }

        $result = $this->callGemini($domain, $sld, $tldKey, $context);

        $payload = $result['result'] !== null
            ? ['result' => $result['result'], 'status' => 'ok', 'error' => null]
            : ['result' => null, 'status' => 'failed', 'error' => $result['error'] ?? 'Bilinmeyen hata'];

        if ($payload['result'] !== null) {
            Cache::put($cacheKey, $payload, self::CACHE_TTL);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{result: array<string, mixed>|null, error: string|null}
     */
    private function callGemini(string $domain, string $sld, string $tldKey, array $context): array
    {
        $apiKey = $this->apiKey();
        $usdTry = (float) ($this->settings->get('domain_usd_try_rate') ?: 35);
        $registered = $context['registered'] === true ? 'evet' : ($context['registered'] === false ? 'hayır (müsait)' : 'bilinmiyor');
        $ageYears = $context['age_years'] ?? 'bilinmiyor';
        $lexicon = $context['lexicon_match'] ?? 'yok';
        $rulesUsd = (int) ($context['rules_estimate_usd'] ?? 0);
        $garbage = (int) ($context['garbage_score'] ?? 0);

        $prompt = <<<PROMPT
Sen profesyonel domain değerleme uzmanısın. Gerçekçi ikincil piyasa (satış) değeri tahmin et.

Alan adı: {$domain}
SLD: {$sld} | Uzantı: .{$tldKey}
Kayıtlı: {$registered} | Yaş: {$ageYears} yıl
Premium sözlük: {$lexicon}
Kural motoru tahmini: {$rulesUsd} USD
Çöp/kalitesiz skoru (0=iyi, 100=çöp): {$garbage}

KURALLAR:
1) Tek kelime jenerik .com (haber, yemek, news, food, shop, money): 500.000–5.000.000+ USD olabilir.
2) Rastgele, anlamsız, uzun, rakamlı domainler (ör. asdasdasdwqewe222): neredeyse değersiz — kayıt ücreti civarı: 10–100 USD. Müsait çöp domainler 20 USD altına inebilir.
3) Rakam, tire, 12+ karakter, klavye dizilimi (asd, qwe): değeri çok düşür.
4) Marka/telaffuz edilebilir kısa isimler: orta segment.

Sadece JSON döndür:
{"estimate_usd":0,"low_usd":0,"high_usd":0,"score":0,"tier":"low","is_generic_keyword":false,"search_demand":"low","reasoning_tr":""}
PROMPT;

        $lastError = 'Model yanıt vermedi';

        foreach (self::MODELS as $model) {
            try {
                $response = Http::timeout(8)
                    ->post(
                        'https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent?key='.$apiKey,
                        [
                            'contents' => [['parts' => [['text' => $prompt]]]],
                            'generationConfig' => [
                                'temperature' => 0.2,
                                'responseMimeType' => 'application/json',
                            ],
                        ]
                    );
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::warning('domain_value.ai_request_failed', ['domain' => $domain, 'model' => $model, 'error' => $lastError]);

                continue;
            }

            if (! $response->successful()) {
                $lastError = 'HTTP '.$response->status().': '.mb_substr($response->body(), 0, 200);
                Log::warning('domain_value.ai_http_error', ['domain' => $domain, 'model' => $model, 'status' => $response->status()]);
                if ($response->status() === 429) {
                    Cache::put('domain_value_ai:quota_blocked', true, 3600);
                    break;
                }

                continue;
            }

            $text = $response->json('candidates.0.content.parts.0.text');
            if (! is_string($text) || $text === '') {
                $lastError = 'Boş yanıt ('.$model.')';

                continue;
            }

            $parsed = json_decode($text, true);
            if (! is_array($parsed) || ! isset($parsed['estimate_usd'])) {
                $lastError = 'Geçersiz JSON ('.$model.')';

                continue;
            }

            $estimateUsd = max(1, (int) $parsed['estimate_usd']);
            $lowUsd = max(1, (int) ($parsed['low_usd'] ?? max(1, (int) round($estimateUsd * 0.5))));
            $highUsd = max($estimateUsd, (int) ($parsed['high_usd'] ?? (int) round($estimateUsd * 1.8)));

            return [
                'result' => [
                    'source' => 'ai',
                    'model' => $model,
                    'estimate_usd' => $estimateUsd,
                    'low_usd' => $lowUsd,
                    'high_usd' => $highUsd,
                    'estimate' => (int) round($estimateUsd * $usdTry),
                    'low' => (int) round($lowUsd * $usdTry),
                    'high' => (int) round($highUsd * $usdTry),
                    'score' => max(1, min(99, (int) ($parsed['score'] ?? 50))),
                    'tier' => $this->mapTier((string) ($parsed['tier'] ?? 'average')),
                    'is_generic_keyword' => (bool) ($parsed['is_generic_keyword'] ?? false),
                    'search_demand' => (string) ($parsed['search_demand'] ?? 'medium'),
                    'reasoning' => (string) ($parsed['reasoning_tr'] ?? ''),
                    'currency' => 'TRY',
                ],
                'error' => null,
            ];
        }

        return ['result' => null, 'error' => $lastError];
    }

    private function apiKey(): string
    {
        $fromSettings = trim((string) $this->settings->get('domain_value_gemini_api_key', ''));
        if ($fromSettings !== '') {
            return $fromSettings;
        }

        return trim((string) config('services.gemini.api_key', ''));
    }

    private function mapTier(string $tier): string
    {
        return match ($tier) {
            'ultra_premium' => 'ultra',
            'premium' => 'premium',
            'good' => 'iyi',
            'average' => 'orta',
            default => 'dusuk',
        };
    }
}
