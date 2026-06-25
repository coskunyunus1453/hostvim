<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class RedirectRuleValidator
{
    public const MAX_RULES = 50;

    /**
     * @param  list<array<string, mixed>>  $rules
     * @return list<array<string, mixed>>
     */
    public function validateAndNormalize(array $rules): array
    {
        if (count($rules) > self::MAX_RULES) {
            throw ValidationException::withMessages([
                'rules' => [__('redirects.too_many', ['max' => self::MAX_RULES])],
            ]);
        }

        $out = [];
        foreach ($rules as $i => $r) {
            $out[] = $this->normalizeRule($r, $i + 1);
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $r
     * @return array<string, mixed>
     */
    private function normalizeRule(array $r, int $index): array
    {
        $source = trim((string) ($r['source'] ?? ''));
        if ($source === '') {
            $source = '/';
        }
        if (! str_starts_with($source, '/')) {
            throw ValidationException::withMessages([
                "rules.{$index}.source" => [__('redirects.source_must_start_slash')],
            ]);
        }
        if (preg_match('/[\r\n\t]/', $source)) {
            throw ValidationException::withMessages([
                "rules.{$index}.source" => [__('redirects.invalid_characters')],
            ]);
        }

        $target = trim((string) ($r['target'] ?? ''));
        if ($target === '') {
            throw ValidationException::withMessages([
                "rules.{$index}.target" => [__('redirects.target_required')],
            ]);
        }
        if (preg_match('/[\r\n\t]/', $target) || str_starts_with($target, '//')) {
            throw ValidationException::withMessages([
                "rules.{$index}.target" => [__('redirects.invalid_target')],
            ]);
        }
        if (preg_match('#^https?://#i', $target)) {
            $host = parse_url($target, PHP_URL_HOST);
            if (! is_string($host) || $host === '' || str_contains($host, '..')) {
                throw ValidationException::withMessages([
                    "rules.{$index}.target" => [__('redirects.invalid_target_url')],
                ]);
            }
        } elseif (! str_starts_with($target, '/')) {
            throw ValidationException::withMessages([
                "rules.{$index}.target" => [__('redirects.relative_target_slash')],
            ]);
        }

        $status = (int) ($r['status'] ?? 301);
        if (! in_array($status, [301, 302, 307, 308], true)) {
            throw ValidationException::withMessages([
                "rules.{$index}.status" => [__('redirects.invalid_status')],
            ]);
        }

        $matchType = strtolower(trim((string) ($r['match_type'] ?? 'exact')));
        if ($matchType === '' && str_contains($source, '*')) {
            $matchType = 'wildcard';
        }
        if (! in_array($matchType, ['exact', 'prefix', 'wildcard'], true)) {
            throw ValidationException::withMessages([
                "rules.{$index}.match_type" => [__('redirects.invalid_match_type')],
            ]);
        }
        if ($matchType === 'wildcard' && substr_count($source, '*') !== 1) {
            throw ValidationException::withMessages([
                "rules.{$index}.source" => [__('redirects.wildcard_one_star')],
            ]);
        }

        $id = trim((string) ($r['id'] ?? ''));
        if ($id === '') {
            $id = 'r-'.bin2hex(random_bytes(4));
        }
        if (! preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $id)) {
            throw ValidationException::withMessages([
                "rules.{$index}.id" => [__('redirects.invalid_rule_id')],
            ]);
        }

        return [
            'id' => $id,
            'source' => $source,
            'target' => $target,
            'status' => $status,
            'enabled' => (bool) ($r['enabled'] ?? true),
            'preserve_query' => array_key_exists('preserve_query', $r) ? (bool) $r['preserve_query'] : true,
            'match_type' => $matchType,
        ];
    }
}
