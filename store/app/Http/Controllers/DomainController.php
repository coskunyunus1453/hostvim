<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\Domain\DomainAvailabilityService;
use App\Support\CustomerFacingText;
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
            $data = $whois->lookup($validated['domain']);
            if (is_array($data) && isset($data['registrar'])) {
                $data['registrar'] = self::publicRegistrarLabel((string) $data['registrar']);
            }
            if (is_array($data) && ! empty($data['name_servers']) && is_array($data['name_servers'])) {
                $data['name_servers'] = self::publicNameservers($data['name_servers']);
            }

            return response()->json($data);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'reason' => 'error', 'message' => CustomerFacingText::sanitize($e->getMessage())], 422);
        }
    }

    private static function publicRegistrarLabel(string $registrar): string
    {
        $lower = strtolower($registrar);
        foreach (['spaceship', 'porkbun', 'cloudflare', 'metunic'] as $vendor) {
            if (str_contains($lower, $vendor)) {
                return CustomerFacingText::brandName();
            }
        }

        return $registrar;
    }

    /**
     * @param  list<string>  $servers
     * @return list<string>
     */
    private static function publicNameservers(array $servers): array
    {
        $vendors = ['spaceship', 'porkbun', 'cloudflare', 'metunic'];
        $hasVendor = false;
        foreach ($servers as $ns) {
            $lower = strtolower((string) $ns);
            foreach ($vendors as $vendor) {
                if (str_contains($lower, $vendor)) {
                    $hasVendor = true;
                    break 2;
                }
            }
        }

        if (! $hasVendor) {
            return $servers;
        }

        $branded = CustomerFacingText::defaultNameservers();

        return $branded !== [] ? $branded : $servers;
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
