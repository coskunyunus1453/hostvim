<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\SiteSubdomain;
use App\Support\HostingSiteTarget;
use Illuminate\Validation\ValidationException;

class HostingSiteTargetResolver
{
    public function forDomain(Domain $domain, ?int $subdomainId = null): HostingSiteTarget
    {
        if ($subdomainId === null || $subdomainId === 0) {
            return $this->forPrimaryDomain($domain);
        }

        $sub = $domain->siteSubdomains()->find($subdomainId);
        if ($sub === null) {
            throw ValidationException::withMessages([
                'subdomain_id' => ['Alt alan adı bulunamadı.'],
            ]);
        }

        return $this->forSubdomain($domain, $sub);
    }

    public function forPrimaryDomain(Domain $domain): HostingSiteTarget
    {
        $docRoot = trim((string) $domain->document_root);
        if ($docRoot === '') {
            $root = rtrim((string) config('panelze.hosting_web_root'), '/\\');
            $docRoot = $root.DIRECTORY_SEPARATOR.$domain->name.DIRECTORY_SEPARATOR.'public_html';
        }

        return new HostingSiteTarget(
            domain: $domain,
            subdomain: null,
            hostname: $domain->name,
            documentRoot: $docRoot,
            engineSiteName: $domain->name,
        );
    }

    public function forSubdomain(Domain $domain, SiteSubdomain $sub): HostingSiteTarget
    {
        $docRoot = trim((string) $sub->document_root);
        if ($docRoot === '') {
            $root = rtrim((string) config('panelze.hosting_web_root'), '/\\');
            $docRoot = $root.DIRECTORY_SEPARATOR.$domain->name.DIRECTORY_SEPARATOR.$sub->path_segment.DIRECTORY_SEPARATOR.'public_html';
        }

        return new HostingSiteTarget(
            domain: $domain,
            subdomain: $sub,
            hostname: $sub->hostname,
            documentRoot: $docRoot,
            engineSiteName: $domain->name,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listTargetsForUser(\App\Models\User $user): array
    {
        $out = [];
        $domains = $user->domains()
            ->with(['sslCertificate', 'siteSubdomains'])
            ->orderByDesc('id')
            ->get();

        foreach ($domains as $domain) {
            $out[] = $this->serializeTarget($this->forPrimaryDomain($domain));
            foreach ($domain->siteSubdomains as $sub) {
                $out[] = $this->serializeTarget($this->forSubdomain($domain, $sub));
            }
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeTarget(HostingSiteTarget $target): array
    {
        $cert = null;
        if ($target->isSubdomain()) {
            $cert = \App\Models\SslCertificate::query()
                ->where('domain_id', $target->domain->id)
                ->where('site_subdomain_id', $target->subdomain->id)
                ->first();
        } else {
            $cert = $target->domain->sslCertificate;
        }

        return [
            'key' => $target->targetKey(),
            'kind' => $target->isSubdomain() ? 'subdomain' : 'domain',
            'domain_id' => $target->domain->id,
            'subdomain_id' => $target->subdomain?->id,
            'hostname' => $target->hostname,
            'parent_domain' => $target->domain->name,
            'path_segment' => $target->subdomain?->path_segment,
            'document_root' => $target->documentRoot,
            'php_version' => $target->phpVersion(),
            'server_type' => $target->serverType(),
            'ssl_enabled' => (bool) ($target->subdomain?->ssl_enabled ?? $target->domain->ssl_enabled),
            'ssl_expiry' => $target->subdomain?->ssl_expiry ?? $target->domain->ssl_expiry,
            'ssl_status' => $cert?->status,
            'status' => $target->domain->status,
        ];
    }
}
