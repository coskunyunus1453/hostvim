<?php

namespace App\Http\Middleware;

use App\Services\CacheService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompressResponseMiddleware
{
    public function __construct(
        protected CacheService $cache,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

    if (! $this->cache->isGzipEnabled()) {
      return $response;
    }

    if ($response->headers->has('Content-Encoding')) {
      return $response;
    }

    $acceptEncoding = $request->header('Accept-Encoding', '');
    if (! str_contains($acceptEncoding, 'gzip')) {
      return $response;
    }

    $contentType = $response->headers->get('Content-Type', '');
    $compressible = str_contains($contentType, 'text/')
      || str_contains($contentType, 'application/json')
      || str_contains($contentType, 'application/javascript')
      || str_contains($contentType, 'application/xml')
      || str_contains($contentType, 'image/svg+xml');

    if (! $compressible) {
      return $response;
    }

    $content = $response->getContent();
    if ($content === false || strlen($content) < 1024) {
      return $response;
    }

    $compressed = gzencode($content, 6);
    if ($compressed === false) {
      return $response;
    }

    $response->setContent($compressed);
    $response->headers->set('Content-Encoding', 'gzip');
    $response->headers->set('Content-Length', (string) strlen($compressed));
    $response->headers->remove('Transfer-Encoding');

    return $response;
  }
}
