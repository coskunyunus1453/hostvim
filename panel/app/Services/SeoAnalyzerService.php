<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SeoAnalyzerService
{
    private const MAX_BODY = 1_500_000;

    /**
     * @return array{ok: bool, error?: string, report?: array<string, mixed>}
     */
    public function analyze(string $rawUrl): array
    {
        $url = $this->normalizeUrl($rawUrl);
        if ($url === null) {
            return ['ok' => false, 'error' => __('curious.seo_invalid_url')];
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $this->isBlockedHost($host)) {
            return ['ok' => false, 'error' => __('curious.seo_blocked_host')];
        }

        $started = microtime(true);
        try {
            $response = Http::withOptions([
                'allow_redirects' => ['max' => 5, 'strict' => false],
            ])
                ->withHeaders([
                    'User-Agent' => 'Panelze-SEO-Analyzer/1.0 (+https://panelze.com)',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->timeout(max(5, (int) config('panelze.curious.seo_timeout', 20)))
                ->get($url);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => __('curious.seo_fetch_failed', ['detail' => $e->getMessage()])];
        }

        $durationMs = (int) round((microtime(true) - $started) * 1000);
        $status = $response->status();
        $html = (string) $response->body();
        if (strlen($html) > self::MAX_BODY) {
            $html = substr($html, 0, self::MAX_BODY);
        }

        if ($status < 200 || $status >= 400) {
            return ['ok' => false, 'error' => __('curious.seo_http_error', ['code' => $status])];
        }

        $report = $this->buildReport($url, $html, $response->header('Content-Type'), $durationMs, $status);

        return ['ok' => true, 'report' => $report];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReport(string $url, string $html, mixed $contentType, int $durationMs, int $status): array
    {
        $dom = $this->loadDom($html);
        $title = $this->textOf($dom, '//title');
        $metaDesc = $this->metaContent($dom, 'description');
        $metaRobots = $this->metaContent($dom, 'robots');
        $canonical = $this->linkRel($dom, 'canonical');
        $viewport = $this->metaContent($dom, 'viewport');
        $ogTitle = $this->metaProperty($dom, 'og:title');
        $ogDesc = $this->metaProperty($dom, 'og:description');
        $ogImage = $this->metaProperty($dom, 'og:image');
        $lang = '';
        if ($dom !== null) {
            $htmlEl = $dom->getElementsByTagName('html')->item(0);
            if ($htmlEl instanceof \DOMElement) {
                $lang = trim((string) $htmlEl->getAttribute('lang'));
            }
        }

        $h1s = $this->nodeTexts($dom, '//h1');
        $h2count = $this->nodeCount($dom, '//h2');
        $images = $this->nodeCount($dom, '//img');
        $imagesNoAlt = $this->nodeCount($dom, '//img[not(@alt) or @alt=""]');
        $links = $this->collectLinks($dom, $url);
        $hasJsonLd = stripos($html, 'application/ld+json') !== false;
        $hasHttps = str_starts_with(strtolower($url), 'https://');
        $wordCount = $this->approxWordCount($dom);
        $sizeKb = round(strlen($html) / 1024, 1);

        $checks = [];
        $checks[] = $this->check(
            'title_present',
            $title !== '',
            __('curious.seo_check_title_present'),
            $title !== '' ? Str::limit($title, 80) : __('curious.seo_missing')
        );
        $titleLen = mb_strlen($title);
        $checks[] = $this->check(
            'title_length',
            $titleLen >= 30 && $titleLen <= 60,
            __('curious.seo_check_title_length'),
            (string) $titleLen.' '.__('curious.seo_chars')
        );
        $checks[] = $this->check(
            'meta_description',
            $metaDesc !== '',
            __('curious.seo_check_meta_desc'),
            $metaDesc !== '' ? Str::limit($metaDesc, 100) : __('curious.seo_missing')
        );
        $descLen = mb_strlen($metaDesc);
        if ($metaDesc !== '') {
            $checks[] = $this->check(
                'meta_desc_length',
                $descLen >= 70 && $descLen <= 160,
                __('curious.seo_check_meta_desc_length'),
                (string) $descLen.' '.__('curious.seo_chars')
            );
        }
        $checks[] = $this->check(
            'h1_single',
            count($h1s) === 1,
            __('curious.seo_check_h1'),
            count($h1s) === 0 ? __('curious.seo_missing') : (count($h1s).' H1')
        );
        $checks[] = $this->check(
            'viewport',
            $viewport !== '',
            __('curious.seo_check_viewport'),
            $viewport !== '' ? 'OK' : __('curious.seo_missing')
        );
        $checks[] = $this->check(
            'https',
            $hasHttps,
            __('curious.seo_check_https'),
            $hasHttps ? 'HTTPS' : 'HTTP'
        );
        $checks[] = $this->check(
            'canonical',
            $canonical !== '',
            __('curious.seo_check_canonical'),
            $canonical !== '' ? Str::limit($canonical, 80) : __('curious.seo_optional_missing')
        );
        $checks[] = $this->check(
            'og_tags',
            $ogTitle !== '' && $ogDesc !== '',
            __('curious.seo_check_og'),
            trim(($ogTitle !== '' ? 'og:title ' : '').($ogDesc !== '' ? 'og:description ' : '').($ogImage !== '' ? 'og:image' : ''))
        );
        $checks[] = $this->check(
            'img_alt',
            $images === 0 || $imagesNoAlt === 0,
            __('curious.seo_check_img_alt'),
            $images > 0 ? "{$imagesNoAlt}/{$images} ".__('curious.seo_without_alt') : '—'
        );
        $checks[] = $this->check(
            'json_ld',
            $hasJsonLd,
            __('curious.seo_check_schema'),
            $hasJsonLd ? __('curious.seo_found') : __('curious.seo_optional_missing')
        );
        $checks[] = $this->check(
            'lang',
            $lang !== '',
            __('curious.seo_check_lang'),
            $lang !== '' ? $lang : __('curious.seo_missing')
        );
        $checks[] = $this->check(
            'response_time',
            $durationMs < 2500,
            __('curious.seo_check_speed'),
            $durationMs.' ms'
        );

        $passed = count(array_filter($checks, fn ($c) => ($c['status'] ?? '') === 'pass'));
        $score = (int) round(($passed / max(1, count($checks))) * 100);

        $categories = [
            [
                'id' => 'meta',
                'title' => __('curious.seo_cat_meta'),
                'items' => array_values(array_filter($checks, fn ($c) => in_array($c['id'], ['title_present', 'title_length', 'meta_description', 'meta_desc_length', 'h1_single'], true))),
            ],
            [
                'id' => 'technical',
                'title' => __('curious.seo_cat_technical'),
                'items' => array_values(array_filter($checks, fn ($c) => in_array($c['id'], ['viewport', 'https', 'canonical', 'lang', 'response_time'], true))),
            ],
            [
                'id' => 'social',
                'title' => __('curious.seo_cat_social'),
                'items' => array_values(array_filter($checks, fn ($c) => in_array($c['id'], ['og_tags', 'json_ld'], true))),
            ],
            [
                'id' => 'content',
                'title' => __('curious.seo_cat_content'),
                'items' => array_values(array_filter($checks, fn ($c) => in_array($c['id'], ['img_alt'], true))),
            ],
        ];

        return [
            'url' => $url,
            'fetched_at' => now()->toIso8601String(),
            'http_status' => $status,
            'response_ms' => $durationMs,
            'content_type' => is_string($contentType) ? $contentType : '',
            'score' => $score,
            'summary' => $this->scoreSummary($score),
            'meta' => [
                'title' => $title,
                'description' => $metaDesc,
                'robots' => $metaRobots,
                'canonical' => $canonical,
                'h1' => $h1s,
                'h2_count' => $h2count,
                'lang' => $lang,
                'word_count' => $wordCount,
                'html_size_kb' => $sizeKb,
            ],
            'links' => $links,
            'open_graph' => [
                'title' => $ogTitle,
                'description' => $ogDesc,
                'image' => $ogImage,
            ],
            'categories' => $categories,
            'checks' => $checks,
        ];
    }

    private function scoreSummary(int $score): string
    {
        if ($score >= 85) {
            return __('curious.seo_summary_excellent');
        }
        if ($score >= 65) {
            return __('curious.seo_summary_good');
        }
        if ($score >= 45) {
            return __('curious.seo_summary_fair');
        }

        return __('curious.seo_summary_poor');
    }

    /**
     * @return array{id: string, status: string, label: string, detail: string}
     */
    private function check(string $id, bool $ok, string $label, string $detail): array
    {
        return [
            'id' => $id,
            'status' => $ok ? 'pass' : 'warn',
            'label' => $label,
            'detail' => $detail,
        ];
    }

    private function normalizeUrl(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        if (! preg_match('#^https?://#i', $raw)) {
            $raw = 'https://'.$raw;
        }
        $parts = parse_url($raw);
        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }
        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        return $scheme.'://'.$parts['host']
            .(isset($parts['port']) ? ':'.$parts['port'] : '')
            .($parts['path'] ?? '/')
            .(isset($parts['query']) ? '?'.$parts['query'] : '');
    }

    private function isBlockedHost(string $host): bool
    {
        $host = strtolower(trim($host, '[]'));
        if ($host === 'localhost' || $host === '0.0.0.0') {
            return true;
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return ! filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );
        }

        return false;
    }

    private function loadDom(string $html): ?\DOMDocument
    {
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $ok = $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        return $ok ? $dom : null;
    }

    private function textOf(?\DOMDocument $dom, string $xpath): string
    {
        if ($dom === null) {
            return '';
        }
        $xp = new \DOMXPath($dom);
        $nodes = $xp->query($xpath);

        return ($nodes !== false && $nodes->length > 0)
            ? trim((string) $nodes->item(0)?->textContent)
            : '';
    }

    private function metaContent(?\DOMDocument $dom, string $name): string
    {
        if ($dom === null) {
            return '';
        }
        $xp = new \DOMXPath($dom);
        $nodes = $xp->query("//meta[translate(@name,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='".strtolower($name)."']");

        return ($nodes !== false && $nodes->length > 0)
            ? trim((string) $nodes->item(0)?->getAttribute('content'))
            : '';
    }

    private function metaProperty(?\DOMDocument $dom, string $property): string
    {
        if ($dom === null) {
            return '';
        }
        $xp = new \DOMXPath($dom);
        $nodes = $xp->query("//meta[translate(@property,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='".strtolower($property)."']");

        return ($nodes !== false && $nodes->length > 0)
            ? trim((string) $nodes->item(0)?->getAttribute('content'))
            : '';
    }

    private function linkRel(?\DOMDocument $dom, string $rel): string
    {
        if ($dom === null) {
            return '';
        }
        $xp = new \DOMXPath($dom);
        $nodes = $xp->query("//link[contains(translate(@rel,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'".strtolower($rel)."')]");

        return ($nodes !== false && $nodes->length > 0)
            ? trim((string) $nodes->item(0)?->getAttribute('href'))
            : '';
    }

    /** @return list<string> */
    private function nodeTexts(?\DOMDocument $dom, string $query): array
    {
        if ($dom === null) {
            return [];
        }
        $xp = new \DOMXPath($dom);
        $nodes = $xp->query($query);
        if ($nodes === false) {
            return [];
        }
        $out = [];
        foreach ($nodes as $node) {
            $t = trim((string) $node->textContent);
            if ($t !== '') {
                $out[] = $t;
            }
        }

        return $out;
    }

    private function nodeCount(?\DOMDocument $dom, string $query): int
    {
        if ($dom === null) {
            return 0;
        }
        $xp = new \DOMXPath($dom);
        $nodes = $xp->query($query);

        return $nodes !== false ? $nodes->length : 0;
    }

    private function approxWordCount(?\DOMDocument $dom): int
    {
        if ($dom === null) {
            return 0;
        }
        $body = $dom->getElementsByTagName('body')->item(0);
        $text = trim(preg_replace('/\s+/u', ' ', (string) ($body?->textContent ?? '')));

        return $text === '' ? 0 : count(preg_split('/\s+/u', $text) ?: []);
    }

    /**
     * @return array{internal: int, external: int, nofollow: int}
     */
    private function collectLinks(?\DOMDocument $dom, string $baseUrl): array
    {
        $internal = 0;
        $external = 0;
        $nofollow = 0;
        $baseHost = strtolower((string) parse_url($baseUrl, PHP_URL_HOST));
        if ($dom === null) {
            return compact('internal', 'external', 'nofollow');
        }
        $xp = new \DOMXPath($dom);
        $nodes = $xp->query('//a[@href]');
        if ($nodes === false) {
            return compact('internal', 'external', 'nofollow');
        }
        foreach ($nodes as $a) {
            if (! $a instanceof \DOMElement) {
                continue;
            }
            $href = trim((string) $a->getAttribute('href'));
            if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                continue;
            }
            $rel = strtolower((string) $a->getAttribute('rel'));
            if (str_contains($rel, 'nofollow')) {
                $nofollow++;
            }
            $host = strtolower((string) parse_url($href, PHP_URL_HOST));
            if ($host === '' || $host === $baseHost) {
                $internal++;
            } else {
                $external++;
            }
        }

        return compact('internal', 'external', 'nofollow');
    }
}
