<?php

namespace App\Http\Controllers;

use App\Services\SettingsService;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function index(SettingsService $settings): Response
    {
        $sitemapUrl = route('sitemap');
        $extra = trim($settings->get('seo_robots_txt', ''));

        $content = "User-agent: *\n";
        $content .= "Allow: /\n";
        $content .= "Disallow: /admin\n";
        $content .= "Disallow: /sepet\n";
        $content .= "Disallow: /odeme\n";
        $content .= "Disallow: /hesabim\n";
        $content .= "Disallow: /giris\n";
        $content .= "Disallow: /kayit\n\n";

        if (filter_var($settings->get('seo_sitemap_enabled', '1'), FILTER_VALIDATE_BOOLEAN)) {
            $content .= "Sitemap: {$sitemapUrl}\n";
        }

        if ($extra !== '') {
            $content .= "\n{$extra}\n";
        }

        return response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
