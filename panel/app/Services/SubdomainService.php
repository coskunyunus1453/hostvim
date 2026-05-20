<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\SiteSubdomain;
use Illuminate\Validation\ValidationException;

class SubdomainService
{
    public function __construct(
        private EngineApiService $engine,
        private HostnameReservationService $hostnames,
        private HostingQuotaService $quota,
    ) {}

    /**
     * @param  array{prefix?: string|null, hostname?: string|null, path_segment?: string|null, php_version?: string|null}  $input
     */
    public function add(Domain $site, array $input): SiteSubdomain
    {
        $this->quota->ensureCanCreateSubdomain($site->user);

        $hostname = $this->resolveHostname($site->name, $input);
        $this->hostnames->assertEngineSafeFqdn($hostname, 'hostname');

        $pathSegmentInput = $input['path_segment'] ?? null;
        if (($pathSegmentInput === null || trim((string) $pathSegmentInput) === '')
            && isset($input['prefix'])
            && str_contains((string) $input['prefix'], '.')) {
            $pathSegmentInput = str_replace('.', '-', strtolower(trim((string) $input['prefix'])));
        }

        $pathSegment = $this->hostnames->resolveSubdomainPathSegment(
            $site->name,
            $hostname,
            $pathSegmentInput,
        );

        if ($this->hostnames->isGloballyTaken($hostname)) {
            throw ValidationException::withMessages(['hostname' => [__('sites.hostname_already_taken')]]);
        }

        if ($site->siteSubdomains()->where('path_segment', $pathSegment)->exists()) {
            throw ValidationException::withMessages(['path_segment' => [__('sites.path_segment_in_use')]]);
        }

        $payload = [
            'hostname' => $hostname,
            'path_segment' => $pathSegment,
            'php_version' => $input['php_version'] ?? $site->php_version ?? '8.2',
        ];

        $resp = $this->engine->siteAddSubdomain($site->name, $payload);
        if (! empty($resp['error'])) {
            throw ValidationException::withMessages(['hostname' => [(string) $resp['error']]]);
        }

        try {
            return SiteSubdomain::create([
                'domain_id' => $site->id,
                'hostname' => $hostname,
                'path_segment' => $pathSegment,
                'document_root' => $resp['document_root'] ?? null,
            ]);
        } catch (\Throwable $e) {
            report($e);
            $this->engine->siteRemoveSubdomain($site->name, $pathSegment);

            throw ValidationException::withMessages([
                'hostname' => [__('sites.subdomain_db_rollback')],
            ]);
        }
    }

    public function remove(Domain $site, string $pathSegment): void
    {
        $pathSegment = strtolower(trim($pathSegment));
        $sub = $site->siteSubdomains()->where('path_segment', $pathSegment)->first();
        if ($sub === null) {
            throw ValidationException::withMessages([
                'path_segment' => [__('sites.subdomain_not_found')],
            ]);
        }

        $resp = $this->engine->siteRemoveSubdomain($site->name, $sub->path_segment);
        if (! empty($resp['error']) && ! $this->ignorableEngineNotFound($resp['error'])) {
            throw ValidationException::withMessages([
                'path_segment' => [(string) $resp['error']],
            ]);
        }

        $sub->delete();
    }

    /**
     * @param  array{prefix?: string|null, hostname?: string|null}  $input
     */
    public function resolveHostname(string $parentFqdn, array $input): string
    {
        $parent = strtolower(trim($parentFqdn));
        $hostname = strtolower(trim((string) ($input['hostname'] ?? '')));
        $prefix = strtolower(trim((string) ($input['prefix'] ?? '')));

        if ($hostname !== '') {
            return $hostname;
        }

        if ($prefix === '') {
            throw ValidationException::withMessages([
                'prefix' => [__('domains.subdomain_prefix_required')],
            ]);
        }

        if (str_contains($prefix, '..') || str_starts_with($prefix, '.') || str_ends_with($prefix, '.')) {
            throw ValidationException::withMessages([
                'prefix' => [__('domains.subdomain_prefix_invalid')],
            ]);
        }

        if (preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*$/', $prefix) !== 1) {
            throw ValidationException::withMessages([
                'prefix' => [__('domains.subdomain_prefix_invalid')],
            ]);
        }

        $suffix = '.'.$parent;
        if (str_ends_with($prefix, $suffix)) {
            return $prefix;
        }

        return $prefix.$suffix;
    }

    private function ignorableEngineNotFound(string $error): bool
    {
        $e = strtolower($error);

        return str_contains($e, 'not found') || str_contains($e, 'no such');
    }
}
