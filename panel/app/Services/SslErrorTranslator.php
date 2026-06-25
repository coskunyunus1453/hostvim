<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Certbot / engine ham hatalarını kullanıcıya anlaşılır SSL mesajlarına çevirir.
 */
class SslErrorTranslator
{
    public static function translate(string $raw, ?string $hostname = null): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return (string) __('ssl.err_unknown');
        }

        $host = trim((string) $hostname);
        $lower = strtolower($raw);

        if (self::isEngineTimeout($lower)) {
            return (string) __('ssl.err_engine_timeout');
        }

        if (str_contains($lower, 'acme-challenge') && (str_contains($lower, '404') || str_contains($lower, 'not found'))) {
            return (string) __('ssl.err_acme_webroot', ['host' => $host]);
        }

        if (str_contains($lower, 'unauthorized') && str_contains($lower, 'acme-challenge')) {
            return (string) __('ssl.err_acme_unreachable', ['host' => $host]);
        }

        if (str_contains($lower, 'dns problem') || str_contains($lower, 'nxdomain') || str_contains($lower, 'dnssec')) {
            return (string) __('ssl.err_dns', ['host' => $host]);
        }

        if (str_contains($lower, 'too many certificates') || str_contains($lower, 'rate limit')) {
            return (string) __('ssl.err_rate_limit', ['host' => $host]);
        }

        if (str_contains($lower, 'connection refused') || str_contains($lower, 'connection timed out')) {
            return (string) __('ssl.err_http_unreachable', ['host' => $host]);
        }

        if (str_contains($lower, 'php version') || str_contains($lower, 'platform_check')) {
            return (string) __('ssl.err_php_version');
        }

        if (preg_match('/Detail:\s*(.+?)(?:\nHint:|$)/is', $raw, $m)) {
            $detail = trim($m[1]);
            if ($detail !== '' && $detail !== $raw) {
                return self::translate($detail, $hostname);
            }
        }

        if (str_starts_with($lower, 'certbot:')) {
            $trimmed = trim(preg_replace('/^certbot:\s*/i', '', $raw) ?? $raw);

            return (string) __('ssl.err_certbot', ['detail' => Str::limit($trimmed, 240)]);
        }

        return (string) __('ssl.err_certbot', ['detail' => Str::limit($raw, 240)]);
    }

    private static function isEngineTimeout(string $lower): bool
    {
        return str_contains($lower, 'timed out')
            || str_contains($lower, 'curl error 28')
            || str_contains($lower, 'operation timed out');
    }
}
