<?php

namespace App\Http\Controllers;

use App\Services\ContactFormService;
use App\Services\SeoService;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request, SeoService $seo, SettingsService $settings, ContactFormService $contactForm)
    {
        $breadcrumbs = [
            ['label' => 'Ana Sayfa', 'url' => route('home')],
            ['label' => 'İletişim', 'url' => null],
        ];

        $konu = (string) $request->query('konu', '');
        $domain = trim((string) $request->query('domain', ''));
        $subjectOptions = $contactForm->subjectOptions();

        $subjectLabels = [
            'vds' => 'VDS Sunucu — Teklif Talebi',
            'dedicated' => 'Dedicated Sunucu — Teklif Talebi',
            'domain-transfer' => 'Domain Transfer Talebi',
        ];
        $prefillSubject = $subjectLabels[$konu] ?? ($konu !== '' ? ucfirst(str_replace('-', ' ', $konu)) : '');
        $prefillTopic = array_key_exists($konu, $subjectOptions) ? $konu : match ($konu) {
            'vds' => 'vds',
            'dedicated' => 'dedicated',
            'domain-transfer' => 'domain',
            default => old('topic', ''),
        };
        $prefillMessage = $domain !== '' ? "Transfer etmek istediğim alan adı: {$domain}\n\n" : '';

        return view('contact.index', [
            'seo' => $seo->build([
                'title' => 'İletişim',
                'description' => 'Bizimle iletişime geçin. Hosting ve sunucu çözümleri için destek alın.',
                'canonical' => route('contact.index'),
            ]),
            'breadcrumbs' => $breadcrumbs,
            'schemas' => [$seo->breadcrumbSchema($breadcrumbs)],
            'prefillSubject' => $prefillSubject,
            'prefillMessage' => $prefillMessage,
            'prefillTopic' => $prefillTopic,
            'subjectOptions' => $subjectOptions,
            'pageTitle' => $settings->get('contact_page_title', 'Bize Ulaşın'),
            'pageSubtitle' => $settings->get('contact_page_subtitle', 'Sorularınız, teklif talepleriniz ve teknik destek için ekibimiz yanınızda.'),
        ]);
    }

    public function store(Request $request, ContactFormService $contactForm)
    {
        if ($request->filled('website')) {
            return back()
                ->withInput()
                ->withErrors(['form' => 'Gönderim reddedildi. Lütfen tekrar deneyin.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'topic' => 'nullable|string|max:50',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
        ], [
            'name.required' => 'Ad soyad alanı zorunludur.',
            'email.required' => 'E-posta alanı zorunludur.',
            'email.email' => 'Geçerli bir e-posta adresi girin.',
            'message.required' => 'Mesaj alanı zorunludur.',
        ]);

        $topic = $validated['topic'] ?? '';
        $subjectOptions = $contactForm->subjectOptions();
        if ($topic !== '' && isset($subjectOptions[$topic]) && empty($validated['subject'])) {
            $validated['subject'] = $subjectOptions[$topic];
        }

        unset($validated['topic']);

        $contactForm->store($validated);

        return redirect()
            ->route('contact.index')
            ->with('success', 'Mesajınız başarıyla gönderildi. En kısa sürede size dönüş yapacağız.')
            ->with('contact_sent', true);
    }
}
