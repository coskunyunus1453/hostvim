<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\Domain\DomainAvailabilityService;
use App\Services\Domain\DomainSettings;
use App\Services\Domain\WhoisService;
use App\Services\SeoService;
use App\Support\DomainFaq;
use App\Support\DomainTldContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    public function index(DomainAvailabilityService $domains, DomainSettings $settings, SeoService $seo)
    {
        $tlds = $settings->registerEnabled() ? $domains->listTlds() : [];

        // Aktif uzantilarla eslesen SEO icerik bloklarini, fiyat tablosu sirasinda hazirla.
        $allContent = DomainTldContent::all();
        $tldContents = [];
        foreach ($tlds as $tld) {
            $key = $tld['tld'];
            if (isset($allContent[$key])) {
                $tldContents[$key] = $allContent[$key];
            }
        }

        return view('domain.index', [
            'tlds' => $tlds,
            'currency' => $settings->currency(),
            'tldContents' => $tldContents,
            'faqs' => DomainFaq::all(),
            'seo' => $seo->forDomain(),
            'breadcrumbs' => [
                ['label' => 'Ana Sayfa', 'url' => route('home')],
                ['label' => 'Domain', 'url' => null],
            ],
        ]);
    }

    public function check(Request $request, DomainAvailabilityService $domains, DomainSettings $settings): JsonResponse
    {
        if (! $settings->registerEnabled()) {
            return response()->json(['message' => 'Domain satışı şu an kapalı.'], 503);
        }

        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:253'],
        ]);

        try {
            return response()->json($domains->check($validated['domain']));
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function search(Request $request, DomainAvailabilityService $domains, DomainSettings $settings): JsonResponse
    {
        if (! $settings->registerEnabled()) {
            return response()->json(['message' => 'Domain satışı şu an kapalı.'], 503);
        }

        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:253'],
        ]);

        try {
            return response()->json($domains->search($validated['domain']));
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function whois(Request $request, WhoisService $whois): JsonResponse
    {
        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:253'],
        ]);

        try {
            return response()->json($whois->lookup($validated['domain']));
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'reason' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function addToCart(Request $request, CartService $cart): JsonResponse
    {
        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:253'],
            'years' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        try {
            $cart->addDomain($validated['domain'], (int) ($validated['years'] ?? 1));
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'redirect' => route('cart.index'),
        ]);
    }
}
