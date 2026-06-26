<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorAuditEvent;
use App\Models\VendorLicense;
use App\Models\VendorNode;
use App\Models\VendorSubscription;
use App\Models\VendorSupportTicket;
use App\Models\VendorTenant;
use App\Models\Domain;
use Illuminate\Http\JsonResponse;

class OpsController extends Controller
{
    public function customer360(VendorTenant $tenant): JsonResponse
    {
        $licenses = VendorLicense::query()->where('tenant_id', (int) $tenant->id)->get(['id', 'status', 'license_key', 'expires_at', 'plan_id']);
        $licenseIds = $licenses->pluck('id')->all();
        $nodes = VendorNode::query()->whereIn('license_id', $licenseIds)->get(['id', 'license_id', 'instance_id', 'hostname', 'status', 'last_seen_at']);
        $subs = VendorSubscription::query()->where('tenant_id', (int) $tenant->id)->latest('id')->limit(10)->get();
        $tickets = VendorSupportTicket::query()->where('tenant_id', (int) $tenant->id)->latest('last_activity_at')->limit(10)->get();
        $domains = collect();
        $modules = collect();
        if ($tenant->panel_user_id) {
            $domains = Domain::query()
                ->where('user_id', (int) $tenant->panel_user_id)
                ->latest('id')
                ->limit(50)
                ->get(['id', 'name', 'status', 'php_version', 'ssl_enabled', 'server_type', 'created_at']);
            $modules = $tenant->panelUser
                ? $tenant->panelUser->pluginModules()->select('plugin_modules.id', 'plugin_modules.slug', 'plugin_modules.name')->get()
                : collect();
        }

        return response()->json([
            'tenant' => $tenant,
            'overview' => [
                'licenses_total' => $licenses->count(),
                'licenses_active' => $licenses->where('status', 'active')->count(),
                'nodes_total' => $nodes->count(),
                'nodes_online' => $nodes->where('status', 'online')->count(),
                'subscriptions_total' => $subs->count(),
                'tickets_open' => $tickets->whereIn('status', ['open', 'in_progress', 'waiting_customer'])->count(),
            ],
            'licenses' => $licenses,
            'nodes' => $nodes,
            'subscriptions' => $subs,
            'tickets' => $tickets,
            'domains' => $domains,
            'modules' => $modules,
        ]);
    }

    public function licenseTimeline(VendorLicense $license): JsonResponse
    {
        $events = VendorAuditEvent::query()
            ->where('license_id', (int) $license->id)
            ->latest('id')
            ->limit(100)
            ->get();

        $nodes = VendorNode::query()
            ->where('license_id', (int) $license->id)
            ->latest('last_seen_at')
            ->get(['id', 'instance_id', 'hostname', 'status', 'last_seen_at', 'created_at']);

        return response()->json([
            'license' => $license->only(['id', 'license_key', 'status', 'starts_at', 'expires_at']),
            'events' => $events,
            'nodes' => $nodes,
        ]);
    }
}

