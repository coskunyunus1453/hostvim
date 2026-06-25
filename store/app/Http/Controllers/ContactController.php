<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Services\SeoService;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(SeoService $seo)
    {
        $breadcrumbs = [
            ['label' => 'Ana Sayfa', 'url' => route('home')],
            ['label' => 'İletişim', 'url' => null],
        ];

        return view('contact.index', [
            'seo' => $seo->build([
                'title' => 'İletişim',
                'description' => 'Bizimle iletişime geçin. Hosting ve sunucu çözümleri için destek alın.',
                'canonical' => route('contact.index'),
            ]),
            'breadcrumbs' => $breadcrumbs,
            'schemas' => [$seo->breadcrumbSchema($breadcrumbs)],
        ]);
    }

    public function store(Request $request)
    {
        if ($request->filled('website')) {
            abort(422);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        ContactMessage::create($validated);

        return back()->with('success', 'Mesajınız alındı. En kısa sürede dönüş yapacağız.');
    }
}
