<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EngineApiService;
use App\Services\PanelKafesApplyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebServerSettingsController extends Controller
{
    public function __construct(
        private EngineApiService $engine,
    ) {}

    public function show(): JsonResponse
    {
        return response()->json([
            'settings' => $this->engine->getWebServerSettings(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nginx_manage_vhosts' => 'sometimes|boolean',
            'nginx_reload_after_vhost' => 'sometimes|boolean',
            'apache_manage_vhosts' => 'sometimes|boolean',
            'apache_reload_after_vhost' => 'sometimes|boolean',

            'openlitespeed_manage_vhosts' => 'sometimes|boolean',
            'openlitespeed_conf_root' => 'sometimes|nullable|string|max:255',
            'openlitespeed_reload_after_vhost' => 'sometimes|boolean',
            'openlitespeed_ctrl_path' => 'sometimes|nullable|string|max:255',

            'php_fpm_manage_pools' => 'sometimes|boolean',
            'php_fpm_reload_after_pool' => 'sometimes|boolean',
            'php_fpm_socket' => 'sometimes|nullable|string|max:255',
            'php_fpm_listen_dir' => 'sometimes|nullable|string|max:255',
            'php_fpm_pool_dir_template' => 'sometimes|nullable|string|max:255',
            'php_fpm_pool_user' => 'sometimes|nullable|string|max:64',
            'php_fpm_pool_group' => 'sometimes|nullable|string|max:64',

            'site_cage_enabled' => 'sometimes|boolean',
            'site_cage_default_cpu_percent' => 'sometimes|integer|min:10|max:400',
            'site_cage_default_memory_mb' => 'sometimes|integer|min:128|max:65536',
            'site_cage_default_pm_max_children' => 'sometimes|integer|min:1|max:200',
            'site_cage_default_memory_limit' => 'sometimes|nullable|string|max:16',

            'reload' => 'sometimes|boolean',
        ]);

        $result = $this->engine->updateWebServerSettings($validated);

        return response()->json([
            'message' => $result['message'] ?? 'ok',
            'settings' => $result['settings'] ?? $this->engine->getWebServerSettings(),
            'reload' => $result['reload'] ?? null,
        ]);
    }

    public function apacheModules(): JsonResponse
    {
        $result = $this->engine->getApacheModules();

        return response()->json([
            'modules' => $result['modules'] ?? [],
        ]);
    }

    public function services(): JsonResponse
    {
        return response()->json([
            'services' => $this->engine->getWebServerServices(),
        ]);
    }

    public function setApacheModule(Request $request, string $module): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $result = $this->engine->setApacheModule($module, (bool) $validated['enabled']);

        return response()->json([
            'module' => $result['module'] ?? $module,
            'enabled' => (bool) ($result['enabled'] ?? false),
        ]);
    }

    public function getNginxConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scope' => 'sometimes|string|in:main,panel',
        ]);
        $scope = (string) ($validated['scope'] ?? 'main');
        $result = $this->engine->getNginxConfig($scope);

        return response()->json([
            'scope' => $result['scope'] ?? $scope,
            'content' => (string) ($result['content'] ?? ''),
        ]);
    }

    public function updateNginxConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scope' => 'required|string|in:main,panel',
            'content' => 'required|string|max:300000',
            'test_reload' => 'sometimes|boolean',
        ]);
        $result = $this->engine->updateNginxConfig(
            (string) $validated['scope'],
            (string) $validated['content'],
            (bool) ($validated['test_reload'] ?? true)
        );

        return response()->json([
            'message' => $result['message'] ?? 'ok',
            'scope' => $result['scope'] ?? $validated['scope'],
        ]);
    }

    public function applyPanelKafesAll(PanelKafesApplyService $apply): JsonResponse
    {
        $result = $apply->applyAllActive();

        return response()->json([
            'message' => sprintf('PanelKafes: %d başarılı, %d hatalı', $result['ok'], $result['failed']),
            'ok' => $result['ok'],
            'failed' => $result['failed'],
            'results' => $result['results'],
        ]);
    }

    public function applyPanelKafesSite(Request $request, PanelKafesApplyService $apply): JsonResponse
    {
        $validated = $request->validate([
            'domain' => 'required|string|max:253',
        ]);
        $domain = strtolower(trim((string) $validated['domain']));
        $result = $apply->applySite($domain);

        return response()->json([
            'message' => $result['message'] ?? 'ok',
            'cage_user' => $result['cage_user'] ?? null,
            'status' => $result['status'] ?? null,
            'error' => $result['error'] ?? null,
        ]);
    }
}

