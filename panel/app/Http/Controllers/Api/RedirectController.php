<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesUserDomain;
use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Services\EngineApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RedirectController extends Controller
{
    use AuthorizesUserDomain;

    public function __construct(
        private EngineApiService $engine,
    ) {}

    public function index(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $res = $this->engine->getSiteRedirects($domain->name);
        if (! empty($res['error'])) {
            return response()->json(['message' => (string) $res['error']], 503);
        }

        return response()->json($res);
    }

    public function update(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $validated = $request->validate([
            'rules' => ['required', 'array', 'max:50'],
            'rules.*.id' => ['nullable', 'string', 'max:64'],
            'rules.*.source' => ['required', 'string', 'max:512'],
            'rules.*.target' => ['required', 'string', 'max:2048'],
            'rules.*.status' => ['nullable', 'integer', Rule::in([301, 302, 307, 308])],
            'rules.*.enabled' => ['nullable', 'boolean'],
            'rules.*.preserve_query' => ['nullable', 'boolean'],
            'rules.*.match_type' => ['nullable', 'string', Rule::in(['exact', 'prefix', 'wildcard'])],
        ]);

        $rules = collect($validated['rules'])->map(function (array $r) {
            return [
                'id' => $r['id'] ?? null,
                'source' => $r['source'],
                'target' => $r['target'],
                'status' => (int) ($r['status'] ?? 301),
                'enabled' => (bool) ($r['enabled'] ?? true),
                'preserve_query' => array_key_exists('preserve_query', $r) ? (bool) $r['preserve_query'] : true,
                'match_type' => $r['match_type'] ?? 'exact',
            ];
        })->values()->all();

        $res = $this->engine->setSiteRedirects($domain->name, $rules);
        if (! empty($res['error'])) {
            return response()->json(['message' => (string) $res['error']], 422);
        }

        return response()->json([
            'domain' => $domain->name,
            'rules' => $res['rules'] ?? $rules,
        ]);
    }
}
