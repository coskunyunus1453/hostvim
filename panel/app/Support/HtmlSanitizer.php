<?php

namespace App\Support;

final class HtmlSanitizer
{
    private const ALLOWED_TAGS = '<p><br><strong><em><b><i><ul><ol><li><h1><h2><h3><h4><a><span><div>';

    public static function onboarding(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $html = trim($html);
        if ($html === '') {
            return null;
        }

        $html = preg_replace('/<(script|iframe|object|embed|form|input|button|meta|link|style|svg|math)[^>]*>.*?<\/\1>/is', '', $html) ?? $html;
        $html = preg_replace('/<(script|iframe|object|embed|form|input|button|meta|link|style|svg|math)\b[^>]*\/?>/is', '', $html) ?? $html;
        $html = preg_replace('/\s(on\w+|style|formaction|xlink:href)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;
        $html = preg_replace('/javascript\s*:/i', '', $html) ?? $html;
        $html = strip_tags($html, self::ALLOWED_TAGS);

        return trim($html) === '' ? null : $html;
    }
}
