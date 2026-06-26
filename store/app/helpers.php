<?php

use App\Support\SafeHtml;

if (! function_exists('safe_html')) {
    function safe_html(?string $html): string
    {
        return app(SafeHtml::class)->clean($html);
    }
}
