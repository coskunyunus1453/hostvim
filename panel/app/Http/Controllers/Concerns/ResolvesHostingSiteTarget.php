<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Domain;
use App\Services\HostingSiteTargetResolver;
use App\Support\HostingSiteTarget;
use Illuminate\Http\Request;

trait ResolvesHostingSiteTarget
{
    protected function resolveHostingTarget(Request $request, Domain $domain): HostingSiteTarget
    {
        $subdomainId = $request->input('subdomain_id');
        if ($subdomainId === null || $subdomainId === '') {
            $subdomainId = $request->query('subdomain_id');
        }

        $id = is_numeric($subdomainId) ? (int) $subdomainId : null;

        return app(HostingSiteTargetResolver::class)->forDomain($domain, $id);
    }
}
