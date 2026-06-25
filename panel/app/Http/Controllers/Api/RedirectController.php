<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesUserDomain;
use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Services\EngineApiService;
use App\Services\RedirectRuleValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RedirectController extends Controller
{
    use AuthorizesUserDomain;

    public function __construct(
        private EngineApiService $engine,
        private RedirectRuleValidator $validator,
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

        if (empty($res['server_type'])) {
            $res['server_type'] = $domain->server_type;
        }

        return response()->json($res);
    }

    public function update(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        if (strtolower((string) $domain->server_type) !== 'nginx') {
            return response()->json(['message' => __('redirects.nginx_only')], 422);
        }

        $validated = $request->validate([
            'rules' => ['required', 'array', 'max:'.RedirectRuleValidator::MAX_RULES],
            'rules.*.id' => ['nullable', 'string', 'max:64'],
            'rules.*.source' => ['required', 'string', 'max:512'],
            'rules.*.target' => ['required', 'string', 'max:2048'],
            'rules.*.status' => ['nullable', 'integer', Rule::in([301, 302, 307, 308])],
            'rules.*.enabled' => ['nullable', 'boolean'],
            'rules.*.preserve_query' => ['nullable', 'boolean'],
            'rules.*.match_type' => ['nullable', 'string', Rule::in(['exact', 'prefix', 'wildcard'])],
        ]);

        $rules = $this->validator->validateAndNormalize($validated['rules']);

        $res = $this->engine->setSiteRedirects($domain->name, $rules);
        if (! empty($res['error'])) {
            return response()->json(['message' => (string) $res['error']], 422);
        }

        return response()->json([
            'message' => __('redirects.saved'),
            'domain' => $domain->name,
            'rules' => $res['rules'] ?? $rules,
        ]);
    }
}
