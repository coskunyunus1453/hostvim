<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SeoAnalyzerService;
use App\Services\SpeedTestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CuriousController extends Controller
{
    public function __construct(
        private SpeedTestService $speedTest,
        private SeoAnalyzerService $seoAnalyzer,
    ) {}

    public function ping(): JsonResponse
    {
        return response()->json($this->speedTest->pingPayload());
    }

    public function prepareDownload(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        try {
            $payload = $this->speedTest->prepareDownload($userId);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

        return response()->json([
            'message' => __('curious.speed_prepared'),
            'result' => $payload,
        ]);
    }

    public function download(Request $request, string $token): StreamedResponse|JsonResponse
    {
        $userId = (int) $request->user()->id;
        $file = $this->speedTest->consumeDownload($token, $userId);
        if ($file === null) {
            return response()->json(['message' => __('curious.speed_token_invalid')], 404);
        }

        $path = $file['path'];
        $bytes = (int) $file['bytes'];

        return response()->stream(function () use ($path): void {
            $fh = fopen($path, 'rb');
            if ($fh !== false) {
                while (! feof($fh)) {
                    $buf = fread($fh, 64 * 1024);
                    if ($buf === false) {
                        break;
                    }
                    echo $buf;
                }
                fclose($fh);
            }
            $this->speedTest->deleteFileAfterStream($path);
        }, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => (string) $bytes,
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $max = $this->speedTest->uploadMaxBytes();
        $request->validate([
            'payload' => 'required|file|max:'.(int) ceil($max / 1024),
        ]);

        $userId = (int) $request->user()->id;
        $file = $request->file('payload');
        if ($file === null) {
            return response()->json(['message' => __('curious.speed_upload_missing')], 422);
        }

        $temp = $file->getRealPath();
        if ($temp === false || ! is_file($temp)) {
            return response()->json(['message' => __('curious.speed_upload_missing')], 422);
        }

        try {
            $meta = $this->speedTest->handleUpload($userId, $temp);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $bytes = (int) $meta['bytes'];

        return response()->json([
            'message' => __('curious.speed_upload_done'),
            'result' => [
                'bytes' => $bytes,
                // Mbps tarayıcı → panel aktarım süresinden hesaplanmalı (dosya zaten yüklendikten sonra ölçüm yanıltıcıdır).
            ],
        ]);
    }

    public function cleanup(Request $request): JsonResponse
    {
        $this->speedTest->purgeUserDir((int) $request->user()->id);

        return response()->json(['message' => __('curious.speed_cleaned')]);
    }

    public function analyzeSeo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => 'required|string|max:2048',
        ]);

        $result = $this->seoAnalyzer->analyze((string) $validated['url']);
        if (! ($result['ok'] ?? false)) {
            return response()->json([
                'message' => (string) ($result['error'] ?? __('curious.seo_failed')),
            ], 422);
        }

        return response()->json([
            'message' => __('curious.seo_done'),
            'report' => $result['report'],
        ]);
    }
}
