<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\PanelSetting;
use App\Models\VendorAuditEvent;
use App\Services\VendorAuditService;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SecurityController extends Controller
{
    public function __construct(
        private VendorAuditService $audit,
    ) {}

    public function auditFeed(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'severity' => ['nullable', Rule::in(['info', 'warning', 'critical'])],
            'event' => ['nullable', 'string', 'max:120'],
        ]);
        $q = VendorAuditEvent::query()->latest('id');
        if (! empty($validated['severity'])) {
            $q->where('severity', $validated['severity']);
        }
        if (! empty($validated['event'])) {
            $q->where('event', 'like', $validated['event'].'%');
        }

        return response()->json([
            'items' => $q->paginate(50),
        ]);
    }

    public function auditExport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);
        $limit = (int) ($validated['limit'] ?? 500);
        $items = VendorAuditEvent::query()->latest('id')->limit($limit)->get();

        return response()->json([
            'exported_at' => now()->toIso8601String(),
            'count' => $items->count(),
            'items' => $items,
        ]);
    }

    public function siemConfig(): JsonResponse
    {
        $raw = (string) (PanelSetting::query()->where('key', 'vendor.siem.config')->value('value') ?? '');
        $cfg = $raw !== '' ? (json_decode($raw, true) ?: []) : [];

        return response()->json([
            'item' => [
                'enabled' => (bool) ($cfg['enabled'] ?? false),
                'endpoint' => (string) ($cfg['endpoint'] ?? ''),
                'auth_type' => (string) ($cfg['auth_type'] ?? 'none'),
                'has_secret' => ! empty($cfg['secret']),
                'timeout_seconds' => (int) ($cfg['timeout_seconds'] ?? 5),
            ],
        ]);
    }

    public function saveSiemConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'endpoint' => ['nullable', 'url', 'max:500'],
            'auth_type' => ['nullable', Rule::in(['none', 'bearer'])],
            'secret' => ['nullable', 'string', 'max:500'],
            'timeout_seconds' => ['nullable', 'integer', 'min:2', 'max:30'],
        ]);

        $existing = (string) (PanelSetting::query()->where('key', 'vendor.siem.config')->value('value') ?? '');
        $cfg = $existing !== '' ? (json_decode($existing, true) ?: []) : [];
        $secretPlain = (string) ($validated['secret'] ?? '');
        if ($secretPlain !== '') {
            $cfg['secret'] = encrypt($secretPlain);
        }
        $cfg['enabled'] = (bool) $validated['enabled'];
        $cfg['endpoint'] = (string) ($validated['endpoint'] ?? '');
        $cfg['auth_type'] = (string) ($validated['auth_type'] ?? 'none');
        $cfg['timeout_seconds'] = (int) ($validated['timeout_seconds'] ?? 5);

        PanelSetting::query()->updateOrCreate(
            ['key' => 'vendor.siem.config'],
            ['value' => json_encode($cfg, JSON_UNESCAPED_SLASHES)]
        );

        $this->audit->record('vendor.security.siem.config_saved', 'warning', null, null, (int) $request->user()->id, [
            'enabled' => $cfg['enabled'],
            'endpoint' => $cfg['endpoint'],
            'auth_type' => $cfg['auth_type'],
        ], $request);

        return response()->json(['saved' => true]);
    }

    public function testSiem(Request $request): JsonResponse
    {
        $raw = (string) (PanelSetting::query()->where('key', 'vendor.siem.config')->value('value') ?? '');
        $cfg = $raw !== '' ? (json_decode($raw, true) ?: []) : [];
        $endpoint = (string) ($cfg['endpoint'] ?? '');
        if ($endpoint === '') {
            return response()->json(['message' => 'SIEM endpoint not configured'], 422);
        }

        $timeout = (int) ($cfg['timeout_seconds'] ?? 5);
        $http = Http::timeout(max(2, min(30, $timeout)))->acceptJson();
        if (($cfg['auth_type'] ?? 'none') === 'bearer' && ! empty($cfg['secret'])) {
            $http = $http->withToken((string) decrypt((string) $cfg['secret']));
        }

        $payload = [
            'source' => 'panelze-vendor',
            'event' => 'siem.test',
            'severity' => 'info',
            'timestamp' => now()->toIso8601String(),
            'meta' => ['message' => 'Vendor SIEM connectivity test'],
        ];
        $res = $http->post($endpoint, $payload);

        $this->audit->record('vendor.security.siem.test', $res->successful() ? 'info' : 'warning', null, null, (int) $request->user()->id, [
            'endpoint' => $endpoint,
            'status' => $res->status(),
        ], $request);

        return response()->json([
            'ok' => $res->successful(),
            'status' => $res->status(),
            'body' => mb_substr($res->body(), 0, 1000),
        ], $res->successful() ? 200 : 502);
    }
}

