<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

class SafeHtml
{
    protected HtmlSanitizerInterface $sanitizer;

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowElement('span', ['class'])
            ->allowElement('div', ['class'])
            ->allowElement('p', ['class'])
            ->allowElement('a', ['href', 'title', 'target', 'rel'])
            ->allowElement('img', ['src', 'alt', 'title', 'width', 'height'])
            ->allowElement('h1', ['class'])
            ->allowElement('h2', ['class'])
            ->allowElement('h3', ['class'])
            ->allowElement('h4', ['class'])
            ->allowElement('ul', ['class'])
            ->allowElement('ol', ['class'])
            ->allowElement('li', ['class'])
            ->allowElement('blockquote', ['class'])
            ->allowElement('pre', ['class'])
            ->allowElement('code', ['class'])
            ->allowElement('table', ['class'])
            ->allowElement('thead', ['class'])
            ->allowElement('tbody', ['class'])
            ->allowElement('tr', ['class'])
            ->allowElement('th', ['class'])
            ->allowElement('td', ['class'])
            ->allowElement('br')
            ->allowElement('strong')
            ->allowElement('em')
            ->allowElement('u')
            ->allowElement('hr')
            ->forceAttribute('a', 'rel', 'noopener noreferrer');

        $this->sanitizer = new HtmlSanitizer($config);
    }

    public function clean(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        return $this->sanitizer->sanitize($html);
    }
}
