<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\EInvoice\EInvoiceSettings;
use App\Services\SeoService;
use App\Services\SettingsService;

class PageController extends Controller
{
    public function show(string $slug, SeoService $seo)
    {
        $page = Page::where('slug', $slug)->where('is_published', true)->firstOrFail();

        $page->content = $this->injectCompanyInfo((string) $page->content);

        $breadcrumbs = [
            ['label' => 'Ana Sayfa', 'url' => route('home')],
            ['label' => $page->title, 'url' => null],
        ];

        return view('pages.show', [
            'page' => $page,
            'seo' => $seo->forPage($page),
            'breadcrumbs' => $breadcrumbs,
            'schemas' => [$seo->breadcrumbSchema($breadcrumbs)],
        ]);
    }

    /**
     * Yasal metinlerdeki [[firma_*]] yer tutucularini admin'deki firma/e-fatura
     * ayarlarindan doldurur. Boş alanlar, doldurulması gerektiğini belli eden bir
     * uyarı ile gösterilir (tek yerden yönetim).
     */
    private function injectCompanyInfo(string $content): string
    {
        if (! str_contains($content, '[[')) {
            return $content;
        }

        $company = EInvoiceSettings::company();
        $siteName = (string) app(SettingsService::class)->get('site_name', config('app.name', 'HostVim'));
        $blank = '<span style="color:#b91c1c">[doldurulacak]</span>';

        $map = [
            '[[site_adi]]' => $siteName,
            '[[firma_unvan]]' => $company['title'] ?: $siteName,
            '[[firma_adres]]' => $company['address'] ?: $blank,
            '[[firma_vergi_dairesi]]' => $company['tax_office'] ?: $blank,
            '[[firma_vergi_no]]' => $company['tax_number'] ?: $blank,
            '[[firma_telefon]]' => $company['phone'] ?: $blank,
            '[[firma_eposta]]' => $company['email'] ?: $blank,
        ];

        return strtr($content, $map);
    }
}
