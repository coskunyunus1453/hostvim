<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesUserDomain;
use App\Http\Controllers\Concerns\ResolvesHostingSiteTarget;
use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Services\EngineApiService;
use App\Support\HostingSiteTarget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NodeAppController extends Controller
{
    use AuthorizesUserDomain;
    use ResolvesHostingSiteTarget;

    public function __construct(
        private EngineApiService $engine,
    ) {}

    public function show(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $target = $this->resolveHostingTarget($request, $domain);
        $resp = $this->engine->getNodeApp($target->engineSiteName, $this->pathSegment($target));
        if (! empty($resp['error'])) {
            return response()->json(['message' => $resp['error']], 503);
        }

        return response()->json($resp);
    }

    public function detect(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $validated = $request->validate([
            'work_dir' => ['nullable', 'string', 'max:255'],
        ]);

        $target = $this->resolveHostingTarget($request, $domain);
        $resp = $this->engine->detectNodeApp(
            $target->engineSiteName,
            $validated['work_dir'] ?? null,
            $this->pathSegment($target),
        );
        if (! empty($resp['error'])) {
            return response()->json(['message' => $resp['error']], 503);
        }

        return response()->json($resp);
    }

    public function update(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'profile' => ['nullable', 'string', 'max:32'],
            'app_profile' => ['nullable', 'string', 'max:32'],
            'work_dir' => ['nullable', 'string', 'max:255'],
            'start_script' => ['nullable', 'string', 'max:64'],
            'listen_port' => ['nullable', 'integer', 'min:1024', 'max:65535'],
            'auto_start' => ['nullable', 'boolean'],
            'env_file' => ['nullable', 'string', 'max:255'],
        ]);

        $target = $this->resolveHostingTarget($request, $domain);
        $resp = $this->engine->updateNodeApp($target->engineSiteName, $validated, $this->pathSegment($target));
        if (! empty($resp['error'])) {
            return response()->json(['message' => $resp['error']], 422);
        }

        return response()->json($resp);
    }

    public function autoConfigure(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $validated = $request->validate([
            'app_profile' => ['nullable', 'string', 'max:32'],
        ]);

        $target = $this->resolveHostingTarget($request, $domain);
        $resp = $this->engine->autoConfigureNodeApp(
            $target->engineSiteName,
            $validated['app_profile'] ?? null,
            $this->pathSegment($target),
        );
        if (! empty($resp['error'])) {
            return response()->json(['message' => $resp['error'], 'output' => $resp['output'] ?? ''], 422);
        }

        return response()->json($resp);
    }

    public function start(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $target = $this->resolveHostingTarget($request, $domain);
        $resp = $this->engine->startNodeApp($target->engineSiteName, $this->pathSegment($target));
        if (! empty($resp['error'])) {
            return response()->json(['message' => $resp['error'], 'output' => $resp['output'] ?? ''], 422);
        }

        return response()->json($resp);
    }

    public function stop(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $target = $this->resolveHostingTarget($request, $domain);
        $resp = $this->engine->stopNodeApp($target->engineSiteName, $this->pathSegment($target));
        if (! empty($resp['error'])) {
            return response()->json(['message' => $resp['error'], 'output' => $resp['output'] ?? ''], 422);
        }

        return response()->json($resp);
    }

    public function restart(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $target = $this->resolveHostingTarget($request, $domain);
        $resp = $this->engine->restartNodeApp($target->engineSiteName, $this->pathSegment($target));
        if (! empty($resp['error'])) {
            return response()->json(['message' => $resp['error'], 'output' => $resp['output'] ?? ''], 422);
        }

        return response()->json($resp);
    }

    public function install(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $validated = $request->validate([
            'use_ci' => ['nullable', 'boolean'],
        ]);

        $target = $this->resolveHostingTarget($request, $domain);
        $resp = $this->engine->installNodeApp(
            $target->engineSiteName,
            (bool) ($validated['use_ci'] ?? false),
            $this->pathSegment($target),
        );
        if (! empty($resp['error'])) {
            return response()->json(['message' => $resp['error'], 'output' => $resp['output'] ?? ''], 422);
        }

        return response()->json($resp);
    }

    public function build(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $target = $this->resolveHostingTarget($request, $domain);
        $resp = $this->engine->buildNodeApp($target->engineSiteName, $this->pathSegment($target));
        if (! empty($resp['error'])) {
            return response()->json(['message' => $resp['error'], 'output' => $resp['output'] ?? ''], 422);
        }

        return response()->json($resp);
    }

    public function heal(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $target = $this->resolveHostingTarget($request, $domain);
        $resp = $this->engine->healNodeApp($target->engineSiteName, $this->pathSegment($target));
        if (! empty($resp['error'])) {
            return response()->json([
                'message' => $resp['error'],
                'steps' => $resp['steps'] ?? [],
                'healthy' => (bool) ($resp['healthy'] ?? false),
            ], 422);
        }

        return response()->json($resp);
    }

    private function pathSegment(HostingSiteTarget $target): ?string
    {
        $seg = trim((string) ($target->subdomain?->path_segment ?? ''));

        return $seg !== '' ? $seg : null;
    }
}
