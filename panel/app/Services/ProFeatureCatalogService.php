<?php

namespace App\Services;

/**
 * Hub + landing modül meta + panel varsayılan eşlemesi.
 */
class ProFeatureCatalogService
{
    /**
     * @return array<string, array{label: string, ui_paths: list<string>, api_route_prefixes: list<string>}>
     */
    public function registry(): array
    {
        $defaults = config('panelze_pro_features', []);
        if (! is_array($defaults)) {
            return [];
        }
        $out = [];
        foreach ($defaults as $key => $meta) {
            if (! is_string($key) || ! is_array($meta)) {
                continue;
            }
            $out[$key] = [
                'label' => (string) ($meta['label'] ?? $key),
                'ui_paths' => $this->stringList($meta['ui_paths'] ?? []),
                'api_route_prefixes' => $this->stringList($meta['api_route_prefixes'] ?? []),
            ];
        }

        return $out;
    }

    public function moduleKeyForUiPath(string $path): ?string
    {
        $path = '/'.trim($path, '/');
        if ($path === '/') {
            return null;
        }
        foreach ($this->mergedModules() as $key => $meta) {
            foreach ($meta['ui_paths'] as $uiPath) {
                $uiPath = '/'.trim($uiPath, '/');
                if ($path === $uiPath || str_starts_with($path, $uiPath.'/')) {
                    return $key;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, array{label: string, ui_paths: list<string>, api_route_prefixes: list<string>, enabled: bool, requires_pro: bool}>
     */
    public function modulesForUi(PanelLicenseService $license): array
    {
        $merged = $this->mergedModules();
        $out = [];
        foreach ($merged as $key => $meta) {
            $hubMeta = $this->hubModuleMeta($key);
            $hubUi = $this->stringList($hubMeta['ui_paths'] ?? []);
            $hubApi = $this->stringList($hubMeta['api_route_prefixes'] ?? []);
            $out[$key] = [
                'label' => (string) ($hubMeta['label'] ?? $meta['label']),
                'ui_paths' => $hubUi !== [] ? $hubUi : $meta['ui_paths'],
                'api_route_prefixes' => $hubApi !== [] ? $hubApi : $meta['api_route_prefixes'],
                'enabled' => $license->hasFeature($key),
                'requires_pro' => true,
            ];
        }

        return $out;
    }

    /**
     * @return array<string, array{label: string, ui_paths: list<string>, api_route_prefixes: list<string>}>
     */
    private function mergedModules(): array
    {
        $registry = $this->registry();
        $hub = $this->hubFeatureKeys();
        foreach ($hub as $key) {
            if (! isset($registry[$key])) {
                $registry[$key] = [
                    'label' => $key,
                    'ui_paths' => [],
                    'api_route_prefixes' => [],
                ];
            }
        }

        return $registry;
    }

    /**
     * @return list<string>
     */
    private function hubFeatureKeys(): array
    {
        $payload = app(PanelLicenseService::class)->hubPayload();
        $features = $payload['features'] ?? [];
        if (! is_array($features)) {
            return [];
        }

        return array_keys($features);
    }

    /**
     * @return array<string, mixed>
     */
    private function hubModuleMeta(string $key): array
    {
        $payload = app(PanelLicenseService::class)->hubPayload();
        $feat = $payload['features'][$key] ?? null;

        return is_array($feat) ? $feat : [];
    }

    /**
     * @param  mixed  $value
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($v) => is_string($v) ? trim($v) : '',
            $value
        )));
    }
}
