<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesUserDomain;
use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Services\EngineApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Site bazında PHP shell fonksiyonlarını (exec/shell_exec/system...) aç/kapa.
 * Güvenlik açısından hassastır: yalnızca yöneticiler değiştirebilir.
 */
class PhpFunctionsController extends Controller
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

        $res = $this->engine->getSitePhpShell($domain->name);
        if (! empty($res['error'])) {
            return response()->json(['message' => (string) $res['error']], 503);
        }

        return response()->json([
            'domain' => $domain->name,
            'shell_functions' => (bool) ($res['shell_functions'] ?? false),
            'managed_pool' => (bool) ($res['managed_pool'] ?? false),
            'can_manage' => (bool) $request->user()?->isAdmin(),
        ]);
    }

    public function update(Request $request, Domain $domain): JsonResponse
    {
        // Tehlikeli fonksiyonları açmak sunucu güvenliğini ilgilendirir; sadece admin.
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $res = $this->engine->setSitePhpShell(
            $domain->name,
            (bool) $validated['enabled'],
            (string) ($domain->php_version ?? ''),
            (string) ($domain->server_type ?? ''),
        );
        if (! empty($res['error'])) {
            return response()->json(['message' => (string) $res['error']], 422);
        }

        return response()->json([
            'domain' => $domain->name,
            'shell_functions' => (bool) ($res['shell_functions'] ?? $validated['enabled']),
        ]);
    }
}
