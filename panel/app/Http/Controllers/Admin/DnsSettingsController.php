<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BindDnsService;
use App\Services\DomainDnsBootstrapService;
use App\Services\PanelDnsSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DnsSettingsController extends Controller
{
    public function __construct(
        private PanelDnsSettingsService $dnsSettings,
        private BindDnsService $bindDns,
        private DomainDnsBootstrapService $dnsBootstrap,
    ) {}

    public function show(): JsonResponse
    {
        return response()->json($this->dnsSettings->forApi());
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ns1' => ['required', 'string', 'max:253', 'regex:/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)+$/'],
            'ns2' => ['required', 'string', 'max:253', 'regex:/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)+$/'],
            'server_ip' => ['required', 'ip'],
            'bind_enabled' => ['sometimes', 'boolean'],
            'bootstrap_defaults' => ['sometimes', 'boolean'],
        ]);

        $this->dnsSettings->update([
            'ns1' => $validated['ns1'],
            'ns2' => $validated['ns2'],
            'server_ip' => $validated['server_ip'],
            'bind_enabled' => $request->boolean('bind_enabled', true),
            'bootstrap_defaults' => $request->boolean('bootstrap_defaults', true),
        ]);

        $repair = $this->dnsBootstrap->repairAllActiveDomains();

        $bind = ['ok' => true, 'skipped' => true];
        if ($this->dnsSettings->bindEnabled()) {
            $bind = $this->bindDns->syncViaSudo();
        }

        return response()->json([
            'message' => __('dns.settings_saved'),
            'settings' => $this->dnsSettings->forApi(),
            'repair' => $repair,
            'bind' => $bind,
        ]);
    }
}
