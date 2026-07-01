<?php

namespace App\Services\Domain;

use App\Support\DomainPremiumLexicon;
use Illuminate\Validation\ValidationException;

/**
 * Hibrit domain değerleme: premium sözlük + kural motoru + Gemini AI.
 */
class DomainValueService
{
    /** @var array<string, float> TLD çarpanı (.com = 1.0 referans) */
    private const TLD_MULTIPLIER = [
        'com' => 1.0, 'com.tr' => 0.12, 'net' => 0.45, 'org' => 0.35,
        'io' => 0.55, 'co' => 0.50, 'tr' => 0.10, 'dev' => 0.40,
        'app' => 0.38, 'ai' => 0.70, 'info' => 0.08, 'biz' => 0.10,
        'xyz' => 0.03, 'online' => 0.05, 'store' => 0.42, 'shop' => 0.45,
    ];

    public function __construct(
        protected WhoisService $whois,
        protected DomainAiValuationService $ai,
        protected \App\Services\SettingsService $settings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function estimate(string $raw): array
    {
        $domain = $this->normalize($raw);
        [$sld, $tld] = $this->split($domain);
        $tldKey = ltrim($tld, '.');
        $len = strlen($sld);

        $whois = $this->whois->lookup($domain);
        $registered = ($whois['ok'] ?? false) ? (bool) ($whois['registered'] ?? true) : null;
        $ageYears = $this->domainAgeYears($whois);

        $lexicon = DomainPremiumLexicon::match($sld);
        $garbageScore = $this->garbageScore($sld);
        $rules = $this->rulesEstimate($sld, $tldKey, $len, $lexicon, $registered, $ageYears, $garbageScore);

        $aiResult = null;
        $aiStatus = 'disabled';
        $aiError = null;

        if ($this->ai->isEnabled()) {
            $aiResponse = $this->ai->appraiseWithStatus($domain, $sld, $tldKey, [
                'registered' => $registered,
                'age_years' => $ageYears,
                'lexicon_match' => $lexicon ? $lexicon['label'].' ('.$lexicon['tier'].')' : 'yok',
                'rules_estimate_usd' => (int) round($rules['estimate'] / $this->usdTryRate()),
                'garbage_score' => $garbageScore,
            ]);
            $aiResult = $aiResponse['result'];
            $aiStatus = $aiResponse['status'];
            $aiError = $aiResponse['error'];
        }

        return $this->mergeResults($domain, $sld, $tld, $tldKey, $rules, $aiResult, $lexicon, $registered, $ageYears, $garbageScore, $aiStatus, $aiError);
    }

    /**
     * @param  array{tier: string, base_usd: int, label: string}|null  $lexicon
     * @return array<string, mixed>
     */
    private function rulesEstimate(
        string $sld,
        string $tldKey,
        int $len,
        ?array $lexicon,
        ?bool $registered,
        ?int $ageYears,
        int $garbageScore,
    ): array {
        $usdTry = $this->usdTryRate();
        $tldMult = self::TLD_MULTIPLIER[$tldKey] ?? 0.06;

        // --- Premium jenerik kelime (haber.com, yemek.com vb.) ---
        if ($lexicon !== null) {
            $baseUsd = $lexicon['base_usd'];
            $lengthMult = $this->lengthMultiplier($len);
            $ageMult = $this->ageMultiplier($ageYears);
            $estimateUsd = (int) round($baseUsd * $tldMult * $lengthMult * $ageMult);

            $score = match ($lexicon['tier']) {
                'ultra' => min(99, 92 + (int) min(7, $ageYears ?? 0)),
                'high' => min(90, 78 + (int) min(10, ($ageYears ?? 0) / 2)),
                default => 70,
            };

            return $this->buildRulesPayload(
                $estimateUsd, $usdTry, $score,
                $lexicon['tier'] === 'ultra' ? 'ultra' : 'premium',
                $this->buildCriteria($sld, $tldKey, $len, $lexicon, $registered, $ageYears, 98, 95, 98),
                $lexicon['label'].' — piyasa referanslı jenerik değerleme.',
                'lexicon',
            );
        }

        // --- Tek kelime jenerik olabilir (sözlük dışı) ---
        if (DomainPremiumLexicon::isLikelyGenericWord($sld) && $tldKey === 'com' && $len <= 8) {
            $baseUsd = match (true) {
                $len <= 4 => 400_000,
                $len <= 6 => 200_000,
                default => 80_000,
            };
            $estimateUsd = (int) round($baseUsd * $this->ageMultiplier($ageYears));

            return $this->buildRulesPayload(
                $estimateUsd, $usdTry, 82, 'premium',
                $this->buildCriteria($sld, $tldKey, $len, null, $registered, $ageYears, 95, 85, 75),
                'Tek kelimelik .com — yüksek jenerik potansiyel.',
                'generic_word',
            );
        }

        // --- Standart kural motoru (düşük değerli / marka domainleri) ---
        return $this->standardRules($sld, $tldKey, $len, $registered, $ageYears, $usdTry, $tldMult, $garbageScore);
    }

    /**
     * @return array<string, mixed>
     */
    private function standardRules(
        string $sld,
        string $tldKey,
        int $len,
        ?bool $registered,
        ?int $ageYears,
        float $usdTry,
        float $tldMult,
        int $garbageScore,
    ): array {
        // Çöp / rastgele domain — kayıt ücreti civarı
        if ($garbageScore >= 55) {
            $estimateUsd = match (true) {
                $garbageScore >= 80 => 8,
                $garbageScore >= 65 => 15,
                default => 25,
            };
            if ($registered === false) {
                $estimateUsd = max(5, (int) round($estimateUsd * 0.7));
            }

            return $this->buildRulesPayload(
                $estimateUsd, $usdTry, max(3, 12 - (int) ($garbageScore / 10)), 'dusuk',
                $this->buildCriteria($sld, $tldKey, $len, null, $registered, $ageYears, 40, max(5, 20 - $len), 5, $garbageScore),
                'Düşük kaliteli / rastgele alan adı — kayıt maliyeti seviyesinde değer.',
                'garbage',
                false,
            );
        }

        $tldScore = min(70, (int) round((self::TLD_MULTIPLIER[$tldKey] ?? 0.06) * 100));
        $lengthScore = $this->lengthScore($len);
        $charScore = $this->characterScore($sld);
        $brandScore = $this->brandabilityScore($sld);
        $ageScore = $this->ageScore($registered, $ageYears);

        $totalScore = (int) round(
            $tldScore * 0.12 + $lengthScore * 0.28 + $charScore * 0.25
            + $brandScore * 0.20 + $ageScore * 0.15
        );
        $totalScore = max(5, min(70, $totalScore - (int) ($garbageScore * 0.35)));

        $baseUsd = match (true) {
            $len <= 3 && $tldKey === 'com' && $garbageScore < 20 => 50_000,
            $len <= 5 && $garbageScore < 25 => 8_000,
            $len <= 8 && $garbageScore < 30 => 2_000,
            $len <= 12 => 400,
            default => 80,
        };

        if ($registered === false) {
            $baseUsd = (int) round($baseUsd * 0.55);
        }

        $multiplier = pow(max(8, $totalScore) / 50, 1.6);
        $estimateUsd = max(5, (int) round($baseUsd * $tldMult * $multiplier));

        $tier = match (true) {
            $totalScore >= 60 => 'iyi',
            $totalScore >= 38 => 'orta',
            default => 'dusuk',
        };

        return $this->buildRulesPayload(
            $estimateUsd, $usdTry, $totalScore, $tier,
            $this->buildCriteria($sld, $tldKey, $len, null, $registered, $ageYears, $tldScore, $lengthScore, $charScore, $garbageScore),
            'Kriter tabanlı değerleme.',
            'rules',
        );
    }

    /**
     * @param  array<string, mixed>  $rules
     * @param  array<string, mixed>|null  $ai
     * @param  array{tier: string, base_usd: int, label: string}|null  $lexicon
     * @return array<string, mixed>
     */
    private function mergeResults(
        string $domain,
        string $sld,
        string $tld,
        string $tldKey,
        array $rules,
        ?array $ai,
        ?array $lexicon,
        ?bool $registered,
        ?int $ageYears,
        int $garbageScore,
        string $aiStatus,
        ?string $aiError,
    ): array {
        $rulesEstimate = $rules['estimate'];
        $rulesLow = $rules['low'];
        $rulesHigh = $rules['high'];
        $rulesScore = $rules['score'];

        $estimate = $rulesEstimate;
        $low = $rulesLow;
        $high = $rulesHigh;
        $score = $rulesScore;
        $tier = $rules['tier'];
        $criteria = $rules['criteria'];
        $reasoning = $rules['reasoning'];
        $source = $rules['source'];
        $aiPowered = false;
        $aiEstimate = null;

        if ($ai !== null) {
            $aiEstimate = $ai['estimate'];
            // Her zaman: %65 AI + %35 kural motoru
            $estimate = (int) round($rulesEstimate * 0.35 + $aiEstimate * 0.65);
            $low = (int) round($rulesLow * 0.35 + $ai['low'] * 0.65);
            $high = (int) round($rulesHigh * 0.35 + $ai['high'] * 0.65);
            $score = (int) round($rulesScore * 0.35 + $ai['score'] * 0.65);
            $tier = $this->tierFromScore($score, $lexicon, $ai['is_generic_keyword'] ?? false);
            $reasoning = trim(($ai['reasoning'] ?: '').' (Karma: %65 AI + %35 kriter)');
            $source = 'ai_hybrid';
            $aiPowered = true;

            $criteria[] = [
                'key' => 'ai',
                'label' => 'Yapay zeka (Gemini)',
                'score' => $ai['score'],
                'weight' => 65,
                'detail' => 'AI tahmini: '.number_format($aiEstimate, 0, ',', '.').' ₺ · Kriter: '.number_format($rulesEstimate, 0, ',', '.').' ₺',
            ];
        } elseif ($lexicon !== null) {
            $source = 'lexicon';
        }

        $estimate = max(50, $estimate);
        $tierLabel = match ($tier) {
            'ultra' => 'Ultra Premium',
            'premium' => 'Premium',
            'iyi' => 'İyi',
            'orta' => 'Orta',
            default => 'Düşük',
        };

        return [
            'domain' => $domain,
            'sld' => $sld,
            'tld' => $tld,
            'registered' => $registered,
            'age_years' => $ageYears,
            'score' => $score,
            'tier' => $tier,
            'tier_label' => $tierLabel,
            'estimate' => $estimate,
            'low' => max(30, $low),
            'high' => max($estimate, $high),
            'currency' => 'TRY',
            'criteria' => $criteria,
            'summary' => $this->summary($sld, $tldKey, $tier, $estimate, $registered, $lexicon, $garbageScore),
            'reasoning' => $reasoning,
            'source' => $source,
            'ai_powered' => $aiPowered,
            'ai_status' => $aiStatus,
            'ai_error' => $aiPowered ? null : $aiError,
            'rules_estimate' => $rulesEstimate,
            'ai_estimate' => $aiEstimate,
            'blend' => $aiPowered ? ['ai' => 65, 'rules' => 35] : null,
            'is_generic' => $lexicon !== null || ($ai !== null && ($ai['is_generic_keyword'] ?? false)),
            'generic_label' => $lexicon['label'] ?? ($ai !== null && ($ai['is_generic_keyword'] ?? false) ? 'Jenerik anahtar kelime' : null),
        ];
    }

    private function tierFromScore(int $score, ?array $lexicon, bool $aiGeneric): string
    {
        if ($lexicon !== null && $lexicon['tier'] === 'ultra' && $score >= 80) {
            return 'ultra';
        }
        if ($score >= 88 || ($lexicon !== null && $score >= 75)) {
            return $lexicon && $lexicon['tier'] === 'ultra' ? 'ultra' : 'premium';
        }
        if ($score >= 65) {
            return 'iyi';
        }
        if ($score >= 38) {
            return 'orta';
        }

        return 'dusuk';
    }

    /**
     * 0 = kaliteli, 100 = çöp/rastgele.
     */
    private function garbageScore(string $sld): int
    {
        $score = 0;
        $len = strlen($sld);

        if (preg_match('/\d/', $sld)) {
            $score += 22;
        }
        if (str_contains($sld, '-')) {
            $score += 18;
        }
        if ($len > 10) {
            $score += 15;
        }
        if ($len > 14) {
            $score += 20;
        }
        if ($len > 18) {
            $score += 15;
        }
        if (preg_match('/(.)\1{2,}/', $sld)) {
            $score += 18;
        }
        if (preg_match('/(asd|qwe|zxc|qwer|asdf|qwert|wqew|sdsd|asd)/i', $sld)) {
            $score += 28;
        }
        if (! preg_match('/[aeiou]/', $sld)) {
            $score += 25;
        }
        if (preg_match('/[b-df-hj-np-tv-z]{6,}/i', $sld)) {
            $score += 22;
        }

        $vowels = preg_match_all('/[aeiou]/', $sld);
        if ($len > 0 && ($vowels / $len) < 0.15) {
            $score += 15;
        }

        // Rakam oranı yüksek
        $digits = preg_match_all('/\d/', $sld);
        if ($len > 0 && ($digits / $len) > 0.2) {
            $score += 20;
        }

        // Sözlükte yok + uzun + karışık
        if ($len >= 12 && DomainPremiumLexicon::match($sld) === null && ! DomainPremiumLexicon::isLikelyGenericWord($sld)) {
            $score += 12;
        }

        return min(100, $score);
    }

    /**
     * @param  array{tier: string, base_usd: int, label: string}|null  $lexicon
     * @return list<array<string, mixed>>
     */
    private function buildCriteria(
        string $sld,
        string $tldKey,
        int $len,
        ?array $lexicon,
        ?bool $registered,
        ?int $ageYears,
        int $tldScore,
        int $lengthScore,
        int $keywordScore,
        int $garbageScore = 0,
    ): array {
        $criteria = [
            ['key' => 'tld', 'label' => 'Uzantı (TLD)', 'score' => $tldScore, 'weight' => 12, 'detail' => '.'.strtoupper($tldKey).' — '.($tldKey === 'com' ? 'global .com' : 'TLD çarpanı uygulandı')],
            ['key' => 'length', 'label' => 'Uzunluk', 'score' => $lengthScore, 'weight' => 28, 'detail' => $len.' karakter'.($len > 12 ? ' — çok uzun, değer düşer' : ($len <= 6 ? ' — kısa ve değerli' : ''))],
        ];

        if ($garbageScore >= 40) {
            $criteria[] = [
                'key' => 'quality',
                'label' => 'Kalite / okunabilirlik',
                'score' => max(3, 100 - $garbageScore),
                'weight' => 35,
                'detail' => 'Rastgele veya düşük kaliteli yapı (skor: '.$garbageScore.'/100 çöp)',
            ];
        } elseif ($lexicon !== null) {
            $criteria[] = [
                'key' => 'generic',
                'label' => 'Jenerik kelime',
                'score' => 98,
                'weight' => 40,
                'detail' => $lexicon['label'].' ("'.$sld.'") — yüksek arama ve ticari niyet potansiyeli.',
            ];
        } else {
            $criteria[] = [
                'key' => 'keyword',
                'label' => 'Anahtar kelime',
                'score' => $keywordScore,
                'weight' => 20,
                'detail' => DomainPremiumLexicon::isLikelyGenericWord($sld) ? 'Tek kelime, jenerik yapı' : 'Sınırlı anahtar kelime eşleşmesi',
            ];
        }

        $criteria[] = [
            'key' => 'age',
            'label' => 'Alan adı yaşı',
            'score' => $this->ageScore($registered, $ageYears),
            'weight' => 10,
            'detail' => $this->ageDetail($registered, $ageYears),
        ];

        return $criteria;
    }

    /**
     * @param  list<array<string, mixed>>  $criteria
     * @return array<string, mixed>
     */
    private function buildRulesPayload(
        int $estimateUsd,
        float $usdTry,
        int $score,
        string $tier,
        array $criteria,
        string $reasoning,
        string $source,
        bool $enforceMinTry = true,
    ): array {
        $estimate = (int) round($estimateUsd * $usdTry);
        $low = (int) round($estimate * 0.55);
        $high = (int) round($estimate * 1.65);

        return [
            'estimate' => $enforceMinTry ? max(50, $estimate) : max(30, $estimate),
            'low' => max(20, $low),
            'high' => max($estimate, $high),
            'score' => $score,
            'tier' => $tier,
            'criteria' => $criteria,
            'reasoning' => $reasoning,
            'source' => $source,
        ];
    }

    private function lengthMultiplier(int $len): float
    {
        return match (true) {
            $len <= 3 => 1.8,
            $len <= 5 => 1.3,
            $len <= 7 => 1.0,
            $len <= 10 => 0.75,
            default => 0.5,
        };
    }

    private function ageMultiplier(?int $ageYears): float
    {
        if ($ageYears === null || $ageYears === 0) {
            return 1.0;
        }

        return min(1.6, 1.0 + ($ageYears * 0.02));
    }

    private function usdTryRate(): float
    {
        return max(1.0, (float) ($this->settings->get('domain_usd_try_rate') ?: 35));
    }

    private function higherTier(string $a, string $b): string
    {
        $order = ['dusuk' => 0, 'orta' => 1, 'iyi' => 2, 'premium' => 3, 'ultra' => 4];

        return ($order[$b] ?? 0) >= ($order[$a] ?? 0) ? $b : $a;
    }

    private function normalize(string $raw): string
    {
        $domain = strtolower(trim($raw));
        $domain = preg_replace('/^https?:\/\//', '', $domain) ?? $domain;
        $domain = explode('/', $domain)[0] ?? $domain;
        $domain = ltrim($domain, '.');

        if (! preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,24}$/', $domain)) {
            throw ValidationException::withMessages(['domain' => 'Geçerli bir alan adı girin (ör. markam.com).']);
        }

        return $domain;
    }

    /** @return array{0: string, 1: string} */
    private function split(string $domain): array
    {
        if (preg_match('/\.(com|net|org|gen|web|info|biz|name|tv|cc|bel)\.tr$/', $domain)) {
            $tld = substr($domain, strrpos($domain, '.', -5));

            return [substr($domain, 0, -strlen($tld)), $tld];
        }

        $parts = explode('.', $domain, 2);

        return [$parts[0] ?? $domain, '.'.($parts[1] ?? 'com')];
    }

    private function lengthScore(int $len): int
    {
        return match (true) {
            $len <= 2 => 98, $len <= 3 => 92, $len <= 5 => 82,
            $len <= 8 => 65, $len <= 12 => 45, $len <= 15 => 30,
            default => 18,
        };
    }

    private function characterScore(string $sld): int
    {
        $score = 55;
        if (preg_match('/^[a-z]+$/', $sld)) {
            $score += 35;
        }
        if (preg_match('/\d/', $sld)) {
            $score -= 28;
        }
        if (str_contains($sld, '-')) {
            $score -= 32;
        }

        return max(8, min(100, $score));
    }

    private function brandabilityScore(string $sld): int
    {
        $score = 40;
        $len = strlen($sld);
        $vowels = preg_match_all('/[aeiou]/', $sld);
        $ratio = $len > 0 ? $vowels / $len : 0;
        if ($ratio >= 0.25 && $ratio <= 0.55) {
            $score += 25;
        }
        if ($len >= 4 && $len <= 10 && preg_match('/^[a-z]+$/', $sld)) {
            $score += 20;
        }

        return max(10, min(100, $score));
    }

    /** @param  array<string, mixed>  $whois */
    private function domainAgeYears(array $whois): ?int
    {
        if (empty($whois['created_at'])) {
            return null;
        }
        try {
            $created = new \DateTimeImmutable((string) $whois['created_at']);

            return max(0, (int) $created->diff(new \DateTimeImmutable)->y);
        } catch (\Throwable) {
            return null;
        }
    }

    private function ageScore(?bool $registered, ?int $ageYears): int
    {
        if ($registered === false) {
            return 28;
        }
        if ($registered === null || $ageYears === null) {
            return 45;
        }

        return min(100, 38 + (int) ($ageYears * 5.5));
    }

    private function ageDetail(?bool $registered, ?int $ageYears): string
    {
        if ($registered === false) {
            return 'Kayıtlı değil.';
        }
        if ($ageYears === null) {
            return 'Kayıt yaşı doğrulanamadı.';
        }
        if ($ageYears === 0) {
            return 'Yeni kayıt.';
        }

        return $ageYears.' yıllık kayıt — eski domainler premium segmentte daha değerli.';
    }

    /**
     * @param  array{tier: string, base_usd: int, label: string}|null  $lexicon
     */
    private function summary(string $sld, string $tldKey, string $tier, int $estimate, ?bool $registered, ?array $lexicon, int $garbageScore = 0): string
    {
        $name = $sld.'.'.$tldKey;
        $fmt = number_format($estimate, 0, ',', '.');

        if ($garbageScore >= 55) {
            return "{$name} düşük kaliteli / rastgele bir alan adı. Tahmini değer kayıt maliyeti seviyesinde: ₺{$fmt}.";
        }
        if ($tier === 'ultra' || ($lexicon !== null && $lexicon['tier'] === 'ultra')) {
            return "{$name} ultra premium jenerik alan adı. Yüksek arama hacmi ve ticari değer nedeniyle tahmini piyasa değeri ₺{$fmt} civarındadır.";
        }
        if ($tier === 'premium' || $lexicon !== null) {
            return "{$name} premium jenerik kelime. Tahmini değer yaklaşık ₺{$fmt}.";
        }
        if ($registered === false) {
            return "{$name} müsait. Geliştirme potansiyeline göre tahmini değer ₺{$fmt}.";
        }

        return "{$name} için tahmini piyasa değeri yaklaşık ₺{$fmt}.";
    }
}
