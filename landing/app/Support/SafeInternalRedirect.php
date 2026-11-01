<?php

namespace App\Support;

final class SafeInternalRedirect
{
    /**
     * Normalize a same-origin path + optional query for safe redirects (open-redirect koruması).
     */
    public static function path(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $path = urldecode(trim($value));

        if (strlen($path) > 2048 || preg_match('/[\x00-\x1F\x7F]/', $path)) {
            return null;
        }

        // Tarayıcılar "\" karakterini "/" gibi yorumlar: "/\evil.com" → "//evil.com"
        if (str_contains($path, '\\') || str_contains($path, '://') || str_contains($path, '//')) {
            return null;
        }

        if (! str_starts_with($path, '/')) {
            return null;
        }

        return $path;
    }
}
