<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Yüklenen SVG'lerden betik çalıştırabilecek içeriği temizler (aynı origin'den sunulduğu için stored XSS riski).
 */
final class SafeSvg
{
    /** @var list<string> */
    private const FORBIDDEN_ELEMENTS = [
        'script', 'foreignobject', 'iframe', 'embed', 'object', 'handler', 'listener',
        'audio', 'video', 'canvas', 'meta', 'link', 'base', 'form', 'input', 'button', 'textarea',
    ];

    /** @var list<string> */
    private const ANIMATION_ELEMENTS = ['set', 'animate', 'animatemotion', 'animatetransform'];

    /**
     * Temizlenmiş SVG döner; geçerli bir SVG değilse null.
     */
    public static function sanitize(string $svg): ?string
    {
        if (trim($svg) === '' || preg_match('/<!ENTITY/i', $svg)) {
            return null;
        }
        $svg = (string) preg_replace('/<!DOCTYPE[^>\[]*>/i', '', $svg);
        if (stripos($svg, '<!DOCTYPE') !== false) {
            return null;
        }

        $dom = new DOMDocument;
        $prev = libxml_use_internal_errors(true);
        $ok = $dom->loadXML($svg, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $dom->documentElement;
        if (! $ok || ! $root instanceof DOMElement || strtolower($root->localName) !== 'svg') {
            return null;
        }

        $xpath = new DOMXPath($dom);

        foreach (iterator_to_array($xpath->query('//processing-instruction()') ?: []) as $pi) {
            $pi->parentNode?->removeChild($pi);
        }

        /** @var list<DOMElement> $elements */
        $elements = iterator_to_array($dom->getElementsByTagName('*'));
        foreach ($elements as $el) {
            if ($el->parentNode === null) {
                continue;
            }
            $name = strtolower($el->localName);
            if (in_array($name, self::FORBIDDEN_ELEMENTS, true)) {
                $el->parentNode->removeChild($el);

                continue;
            }
            if (in_array($name, self::ANIMATION_ELEMENTS, true)) {
                $target = strtolower((string) ($el->getAttribute('attributeName')));
                if ($target === '' || str_starts_with($target, 'on') || str_contains($target, 'href')) {
                    $el->parentNode->removeChild($el);

                    continue;
                }
            }

            foreach (iterator_to_array($el->attributes) as $attr) {
                $attrName = strtolower($attr->localName);
                $value = (string) $attr->value;
                $normalized = strtolower((string) preg_replace('/[\s\x00-\x1F]+/', '', html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

                if (str_starts_with($attrName, 'on')) {
                    $el->removeAttributeNode($attr);

                    continue;
                }
                if ($attrName === 'href' || $attrName === 'src') {
                    $isLocalRef = str_starts_with($normalized, '#');
                    $isRasterData = (bool) preg_match('#^data:image/(png|jpe?g|gif|webp);#', $normalized);
                    if (! $isLocalRef && ! $isRasterData) {
                        $el->removeAttributeNode($attr);
                    }

                    continue;
                }
                if (str_contains($normalized, 'javascript:') || str_contains($normalized, 'vbscript:')
                    || str_contains($normalized, 'expression(') || preg_match('/url\((?!["\']?#)/', $normalized)) {
                    $el->removeAttributeNode($attr);
                }
            }

            if ($name === 'style') {
                $css = strtolower((string) $el->textContent);
                if (str_contains($css, '@import') || str_contains($css, 'javascript:') || str_contains($css, 'expression(')) {
                    $el->parentNode->removeChild($el);
                }
            }
        }

        $out = $dom->saveXML($root);

        return is_string($out) && $out !== '' ? $out : null;
    }
}
