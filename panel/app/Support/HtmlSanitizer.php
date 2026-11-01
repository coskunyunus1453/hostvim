<?php

namespace App\Support;

final class HtmlSanitizer
{
    private const ALLOWED_TAGS = '<p><br><strong><em><b><i><ul><ol><li><h1><h2><h3><h4><a><span><div>';

    private const ALLOWED_ELEMENTS = ['p', 'br', 'strong', 'em', 'b', 'i', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'a', 'span', 'div'];

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
        $html = strip_tags($html, self::ALLOWED_TAGS);
        $html = self::filterAttributes($html);

        return trim($html) === '' ? null : $html;
    }

    /**
     * Öznitelik beyaz listesi: yalnızca <a href> (http/https/mailto/göreli). Entity ile gizlenmiş
     * javascript: gibi şemalar DOM üzerinden çözülmüş değerle kontrol edilir.
     */
    private static function filterAttributes(string $html): string
    {
        $doc = new \DOMDocument;
        $prev = libxml_use_internal_errors(true);
        $loaded = $doc->loadHTML(
            '<?xml encoding="UTF-8"><div>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if (! $loaded) {
            return '';
        }

        $root = null;
        foreach ($doc->childNodes as $node) {
            if ($node instanceof \DOMElement) {
                $root = $node;
                break;
            }
        }
        if ($root === null) {
            return '';
        }

        self::walk($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return $out;
    }

    private static function walk(\DOMElement $el): void
    {
        foreach (iterator_to_array($el->childNodes) as $child) {
            if (! $child instanceof \DOMElement) {
                if (! $child instanceof \DOMText) {
                    $el->removeChild($child);
                }

                continue;
            }
            $tag = strtolower($child->tagName);
            if (! in_array($tag, self::ALLOWED_ELEMENTS, true)) {
                $el->removeChild($child);

                continue;
            }
            foreach (iterator_to_array($child->attributes) as $attr) {
                $keep = $tag === 'a' && strtolower($attr->name) === 'href' && self::isSafeHref($attr->value);
                if (! $keep) {
                    $child->removeAttribute($attr->name);
                }
            }
            self::walk($child);
        }
    }

    private static function isSafeHref(string $href): bool
    {
        $v = strtolower(preg_replace('/[\x00-\x20\x7f]+/', '', $href) ?? '');
        if ($v === '' || str_starts_with($v, '#') || str_starts_with($v, '/')) {
            return true;
        }
        if (preg_match('#^(https?:|mailto:)#', $v) === 1) {
            return true;
        }

        return preg_match('#^[a-z0-9+.\-]*:#', $v) !== 1;
    }
}
