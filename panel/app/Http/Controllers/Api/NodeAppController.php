<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesUserDomain;
use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Services\EngineApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NodeAppController extends Controller
{
    use AuthorizesUserDomain;

    public function __construct(
        private EngineApiService $engine,
    ) {}

    public function show(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $resp = $this->engine->getNodeApp($domain->name);
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

        $resp = $this->engine->detectNodeApp($domain->name, $validated['work_dir'] ?? null);
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

        $resp = $this->engine->updateNodeApp($domain->name, $validated);
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

        $resp = $this->engine->autoConfigureNodeApp($domain->name, $validated['app_profile'] ?? null);
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

        $resp = $this->engine->startNodeApp($domain->name);
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

        $resp = $this->engine->stopNodeApp($domain->name);
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

        $resp = $this->engine->restartNodeApp($domain->name);
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

        $resp = $this->engine->installNodeApp($domain->name, (bool) ($validated['use_ci'] ?? false));
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

        $resp = $this->engine->buildNodeApp($domain->name);
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

        $resp = $this->engine->healNodeApp($domain->name);
        if (! empty($resp['error'])) {
            return response()->json([
                'message' => $resp['error'],
                'steps' => $resp['steps'] ?? [],
                'healthy' => (bool) ($resp['healthy'] ?? false),
            ], 422);
        }

        return response()->json($resp);
    }
}
