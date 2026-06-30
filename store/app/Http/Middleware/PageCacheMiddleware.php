<?php

namespace App\Http\Middleware;

use App\Services\CacheService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class PageCacheMiddleware
{
    /** @var list<string> */
    protected array $excludedRouteNames = [
        'cart.index', 'cart.count', 'cart.remove', 'cart.clear',
        'checkout.index', 'checkout.process',
        'payment.success', 'payment.fail',
        'payment.paytr.callback', 'payment.iyzico.callback',
        'contact.index', 'contact.store', 'products.cart.add',
        'login', 'register', 'logout',
        'password.request', 'password.email', 'password.reset', 'password.update',
    ];

    /** @var list<string> */
    protected array $excludedPrefixes = [
        'admin', 'livewire', 'filament',
        'giris', 'kayit', 'cikis', 'hesabim', 'sifremi-unuttum', 'sifre-sifirla',
    ];

    public function __construct(
        protected CacheService $cache,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldUsePageCache($request)) {
            $response = $next($request);

            return $this->applyBrowserHeaders($response);
        }

        $key = $this->cache->pageCacheKey($request);

        if ($cached = Cache::get($key)) {
            if ($this->isGzipPayload($cached)) {
                Cache::forget($key);
            } else {
                return $this->cachedResponse($cached);
            }
        }

        $response = $next($request);

        if ($this->shouldStoreResponse($response)) {
            $this->cache->storePageHtml(
                $key,
                $this->extractHtmlForCache($response),
                null,
                $request->path(),
            );
        }

        return $this->applyBrowserHeaders($response);
    }

    protected function extractHtmlForCache(Response $response): string
    {
        $content = $response->getContent();

        if ($content === false) {
            return '';
        }

        if ($response->headers->get('Content-Encoding') === 'gzip' || $this->isGzipPayload($content)) {
            $decoded = @gzdecode($content);

            return $decoded !== false ? $decoded : $content;
        }

        return $content;
    }

    protected function isGzipPayload(string $content): bool
    {
        return strlen($content) >= 2 && $content[0] === "\x1f" && $content[1] === "\x8b";
    }

    protected function shouldUsePageCache(Request $request): bool
    {
        if (! $this->cache->isPageCacheEnabled()) {
            return false;
        }

        if (auth()->check()) {
            return false;
        }

        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($request->ajax() || $request->wantsJson()) {
            return false;
        }

        $routeName = $request->route()?->getName();
        if ($routeName && in_array($routeName, $this->excludedRouteNames, true)) {
            return false;
        }
        if ($routeName && (str_starts_with($routeName, 'account.') || str_starts_with($routeName, 'payment.'))) {
            return false;
        }

        foreach ($this->excludedPrefixes as $prefix) {
            if ($request->is($prefix) || $request->is($prefix . '/*')) {
                return false;
            }
        }

        if (session()->has('success') || session()->has('error') || session()->has('errors') || session()->has('status')) {
            return false;
        }

        return true;
    }

    protected function shouldStoreResponse(Response $response): bool
    {
        if ($response->getStatusCode() !== 200) {
            return false;
        }

        $contentType = $response->headers->get('Content-Type', '');

        return str_contains($contentType, 'text/html') || $contentType === '';
    }

    protected function cachedResponse(string $html): Response
    {
        $response = response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Page-Cache' => 'HIT',
        ]);

        return $this->applyBrowserHeaders($response);
    }

    public function applyBrowserHeaders(Response $response): Response
    {
        if (! $this->cache->isBrowserCacheEnabled()) {
            return $response;
        }

        $path = request()->path();
        $isAsset = str_starts_with($path, 'build/') || preg_match('/\.(css|js|woff2?|ttf|svg|png|jpe?g|webp|ico|gif)$/i', $path);
        $isPrivate = in_array($path, ['giris', 'kayit', 'cikis'], true)
            || str_starts_with($path, 'admin')
            || str_starts_with($path, 'hesabim')
            || str_starts_with($path, 'odeme')
            || str_starts_with($path, 'sifremi-unuttum')
            || str_starts_with($path, 'sifre-sifirla');

        if ($isPrivate) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');

            return $response;
        }

        if ($isAsset) {
            $maxAge = $this->cache->browserAssetsTtl();
            $response->headers->set('Cache-Control', "public, max-age={$maxAge}, immutable");
        } else {
            $maxAge = $this->cache->browserHtmlTtl();
            if ($maxAge > 0) {
                $response->headers->set('Cache-Control', "public, max-age={$maxAge}, must-revalidate");
            } else {
                $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
            }
        }

        $response->headers->set('Vary', 'Accept-Encoding');

        return $response;
    }
}
