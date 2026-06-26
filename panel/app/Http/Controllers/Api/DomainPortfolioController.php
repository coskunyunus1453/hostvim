<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DomainRegistration;
use App\Models\DomainTransfer;
use App\Services\Billing\BillingSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DomainPortfolioController extends Controller
{
    public function __construct(private BillingSettings $billingSettings) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $registrations = DomainRegistration::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $hostingDomains = $user->domains()->orderBy('name')->get(['id', 'name', 'status', 'server_type']);

        $transfers = DomainTransfer::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn (DomainTransfer $t) => $this->transferPayload($t));

        $ns = [
            'primary' => (string) $this->billingSettings->get('dns_ns1', ''),
            'secondary' => (string) $this->billingSettings->get('dns_ns2', ''),
        ];

        return response()->json([
            'registrations' => $registrations,
            'hosting_domains' => $hostingDomains,
            'transfers' => $transfers,
            'nameservers' => array_filter($ns),
        ]);
    }

    public function registrars(): JsonResponse
    {
        return response()->json([
            'providers' => config('registrars.providers', []),
        ]);
    }

    public function requestTransfer(Request $request): JsonResponse
    {
        $providerIds = collect(config('registrars.providers', []))->pluck('id')->all();

        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:253', 'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,24}$/i'],
            'source_registrar' => ['required', 'string', Rule::in($providerIds)],
            'auth_code' => ['nullable', 'string', 'max:64'],
            'direction' => ['sometimes', Rule::in([DomainTransfer::DIRECTION_IN, DomainTransfer::DIRECTION_OUT])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $domain = strtolower(trim($validated['domain']));
        $direction = $validated['direction'] ?? DomainTransfer::DIRECTION_IN;

        $registration = DomainRegistration::query()
            ->where('user_id', $request->user()->id)
            ->where('domain', $domain)
            ->first();

        if ($direction === DomainTransfer::DIRECTION_OUT && $registration === null) {
            return response()->json(['message' => 'Bu alan adı portföyünüzde bulunamadı.'], 422);
        }

        $pending = DomainTransfer::query()
            ->where('user_id', $request->user()->id)
            ->where('domain', $domain)
            ->whereIn('status', [DomainTransfer::STATUS_PENDING, DomainTransfer::STATUS_PROCESSING])
            ->exists();

        if ($pending) {
            return response()->json(['message' => 'Bu alan adı için zaten bekleyen bir transfer talebi var.'], 422);
        }

        $transfer = DomainTransfer::create([
            'user_id' => $request->user()->id,
            'domain_registration_id' => $registration?->id,
            'domain' => $domain,
            'direction' => $direction,
            'source_registrar' => $validated['source_registrar'],
            'auth_code' => $validated['auth_code'] ?? null,
            'status' => DomainTransfer::STATUS_PENDING,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Transfer talebi alındı. Ekibimiz kısa sürede işleme alacaktır.',
            'transfer' => $this->transferPayload($transfer),
        ], 201);
    }

    public function updateRegistration(Request $request, DomainRegistration $registration): JsonResponse
    {
        abort_unless((int) $registration->user_id === (int) $request->user()->id, 403);

        $validated = $request->validate([
            'auto_renew' => ['sometimes', 'boolean'],
            'locked' => ['sometimes', 'boolean'],
        ]);

        $registration->update($validated);

        return response()->json([
            'message' => 'Alan adı ayarları güncellendi.',
            'registration' => $registration->fresh(),
        ]);
    }

    /** @return array<string, mixed> */
    private function transferPayload(DomainTransfer $t): array
    {
        return [
            'id' => $t->id,
            'domain' => $t->domain,
            'direction' => $t->direction,
            'source_registrar' => $t->source_registrar,
            'status' => $t->status,
            'notes' => $t->notes,
            'created_at' => $t->created_at?->toIso8601String(),
            'completed_at' => $t->completed_at?->toIso8601String(),
            'has_auth_code' => $t->auth_code !== null && $t->auth_code !== '',
        ];
    }
}
