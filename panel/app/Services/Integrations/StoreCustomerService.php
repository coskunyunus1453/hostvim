<?php

namespace App\Services\Integrations;

use App\Models\DomainRegistration;
use App\Models\DomainTransfer;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\BillingSettings;
use App\Services\Billing\InvoicePaymentService;
use App\Services\EngineApiService;
use App\Services\HostingQuotaService;
use App\Services\SafeAuditLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class StoreCustomerService
{
    public function __construct(
        private BillingSettings $billingSettings,
        private HostingQuotaService $quota,
        private EngineApiService $engine,
        private InvoicePaymentService $invoicePayments,
    ) {}

    /**
     * @return array{linked: bool, panel_user_id?: int, name?: string}
     */
    public function linkByEmail(string $email): array
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return ['linked' => false];
        }

        $user = User::query()->where('email', $email)->first();
        if ($user === null || $user->status !== 'active' || $user->isAdmin() || $user->isVendorOperator()) {
            return ['linked' => false];
        }

        return [
            'linked' => true,
            'panel_user_id' => (int) $user->id,
            'name' => $user->name,
        ];
    }

    /** @return array<string, mixed> */
    public function summary(User $user): array
    {
        $hosting = $this->hostingOverview($user);
        $unpaidInvoices = $user->invoices()->whereIn('status', ['unpaid', 'overdue'])->count();
        $registrations = DomainRegistration::query()->where('user_id', $user->id)->count();

        return [
            'user' => $this->profilePayload($user),
            'stats' => [
                'hosting_domains' => count($hosting['domains']),
                'registered_domains' => $registrations,
                'unpaid_invoices' => $unpaidInvoices,
            ],
            'hosting' => $hosting['summary'],
        ];
    }

    /** @return array<string, mixed> */
    public function domainsPortfolio(User $user): array
    {
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

        return [
            'registrations' => $registrations,
            'hosting_domains' => $hostingDomains,
            'transfers' => $transfers,
            'nameservers' => array_filter($ns),
            'registrars' => config('registrars.providers', []),
        ];
    }

    /** @return array<string, mixed> */
    public function hostingOverview(User $user): array
    {
        $pkg = $user->hostingPackage;
        $diskUsed = $user->isAdmin() ? 0 : $this->quota->sumAccountDiskBytes($user);
        $diskLimitBytes = $this->quota->diskQuotaBytes($pkg);

        $domains = [];
        foreach ($user->domains()->orderBy('name')->cursor() as $domain) {
            $disk = $this->engine->getSiteDiskUsage((string) $domain->name);
            $bytes = (int) ($disk['bytes'] ?? 0);
            $trafficBytes = $this->engine->getSiteTrafficSampleBytesTotal((string) $domain->name, 8000);

            $domains[] = [
                'id' => $domain->id,
                'name' => $domain->name,
                'status' => $domain->status,
                'server_type' => $domain->server_type,
                'disk_mb' => round($bytes / 1048576, 1),
                'bandwidth_mb' => round($trafficBytes / 1048576, 1),
                'engine_error' => $disk['error'] ?? null,
            ];
        }

        $subscriptions = $user->subscriptions()
            ->with(['hostingPackage:id,name,slug', 'domain:id,name'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($sub) => [
                'id' => $sub->id,
                'status' => $sub->status,
                'billing_cycle' => $sub->billing_cycle,
                'package' => $sub->hostingPackage?->only(['id', 'name', 'slug']),
                'domain' => $sub->domain?->only(['id', 'name']),
                'current_period_end' => ($sub->next_due_at ?? $sub->ends_at)?->toIso8601String(),
            ]);

        return [
            'package' => $pkg ? [
                'id' => $pkg->id,
                'name' => $pkg->name,
                'slug' => $pkg->slug,
                'disk_space_mb' => (int) $pkg->disk_space_mb > 0 ? (int) $pkg->disk_space_mb : null,
                'bandwidth_mb' => (int) $pkg->bandwidth_mb > 0 ? (int) $pkg->bandwidth_mb : null,
                'max_domains' => (int) $pkg->max_domains >= 0 ? (int) $pkg->max_domains : null,
            ] : null,
            'summary' => [
                'disk_used_mb' => round($diskUsed / 1048576, 1),
                'disk_limit_mb' => $diskLimitBytes !== null ? round($diskLimitBytes / 1048576) : null,
                'domain_count' => count($domains),
            ],
            'domains' => $domains,
            'subscriptions' => $subscriptions,
        ];
    }

    /** @return array<string, mixed> */
    public function invoices(User $user, int $page = 1): array
    {
        $paginator = $user->invoices()
            ->with('items')
            ->latest()
            ->paginate(15, ['*'], 'page', max(1, $page));

        return [
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function invoiceDetail(User $user, int $invoiceId): array
    {
        $invoice = Invoice::query()
            ->whereKey($invoiceId)
            ->where('user_id', $user->id)
            ->with(['items', 'subscription.hostingPackage', 'subscription.domain'])
            ->firstOrFail();

        return ['invoice' => $invoice];
    }

    /** @return array<string, mixed> */
    public function initiateInvoicePayment(User $user, int $invoiceId): array
    {
        $invoice = Invoice::query()
            ->whereKey($invoiceId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        try {
            return $this->invoicePayments->initiate($invoice, $user);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['invoice' => $e->getMessage()]);
        }
    }

    /** @return array<string, mixed> */
    public function profilePayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'locale' => $user->locale,
            'status' => $user->status,
            'force_password_change' => (bool) $user->force_password_change,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateProfile(User $user, array $data): array
    {
        $fill = [];
        if (isset($data['name']) && trim((string) $data['name']) !== '') {
            $fill['name'] = trim((string) $data['name']);
        }
        if (isset($data['locale']) && in_array($data['locale'], ['en', 'tr'], true)) {
            $fill['locale'] = $data['locale'];
        }
        if ($fill !== []) {
            $user->forceFill($fill)->save();
        }

        return ['user' => $this->profilePayload($user->fresh())];
    }

    public function updatePassword(User $user, string $current, string $password): void
    {
        if (! Hash::check($current, (string) $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('auth.current_password_invalid')],
            ]);
        }

        $validator = validator(['password' => $password], [
            'password' => ['required', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $user->forceFill([
            'password' => Hash::make($password),
            'force_password_change' => false,
        ])->save();
    }

    public function requestTransfer(User $user, array $validated): DomainTransfer
    {
        $domain = strtolower(trim((string) ($validated['domain'] ?? '')));
        $direction = $validated['direction'] ?? DomainTransfer::DIRECTION_IN;

        $registration = DomainRegistration::query()
            ->where('user_id', $user->id)
            ->where('domain', $domain)
            ->first();

        if ($direction === DomainTransfer::DIRECTION_OUT && $registration === null) {
            throw ValidationException::withMessages(['domain' => 'Bu alan adı portföyünüzde bulunamadı.']);
        }

        $pending = DomainTransfer::query()
            ->where('user_id', $user->id)
            ->where('domain', $domain)
            ->whereIn('status', [DomainTransfer::STATUS_PENDING, DomainTransfer::STATUS_PROCESSING])
            ->exists();

        if ($pending) {
            throw ValidationException::withMessages(['domain' => 'Bu alan adı için zaten bekleyen bir transfer talebi var.']);
        }

        return DomainTransfer::create([
            'user_id' => $user->id,
            'domain_registration_id' => $registration?->id,
            'domain' => $domain,
            'direction' => $direction,
            'source_registrar' => $validated['source_registrar'],
            'auth_code' => $validated['auth_code'] ?? null,
            'status' => DomainTransfer::STATUS_PENDING,
            'notes' => $validated['notes'] ?? null,
        ]);
    }

    public function updateRegistration(User $user, DomainRegistration $registration, array $validated): DomainRegistration
    {
        abort_unless((int) $registration->user_id === (int) $user->id, 403);

        $registration->update(array_intersect_key($validated, array_flip(['auto_renew', 'locked'])));

        return $registration->fresh();
    }

    /** @return array{redirect_url: string, expires_in: int} */
    public function mintPanelSso(User $user): array
    {
        if ($user->isAdmin() || $user->isVendorOperator()) {
            throw ValidationException::withMessages(['user' => 'Bu hesap türü için panel SSO kullanılamaz.']);
        }
        if ($user->two_factor_enabled && $user->two_factor_secret) {
            throw ValidationException::withMessages([
                'user' => '2FA etkin hesaplar için panelden doğrudan giriş yapın.',
            ]);
        }

        $jti = (string) Str::uuid();
        Cache::put('whmcs_sso:'.$jti, ['user_id' => $user->id, 'admin' => false], now()->addMinutes(2));

        $base = rtrim((string) config('panelze.store_integration.panel_login_url', ''), '/');
        if ($base === '') {
            $base = rtrim((string) env('PANELZE_PANEL_URL', ''), '/');
        }
        if ($base === '') {
            $base = rtrim((string) config('app.url', ''), '/');
        }

        SafeAuditLogger::info('panelze.store.sso_mint', [
            'user_id' => $user->id,
            'email_hash' => hash('sha256', strtolower(trim((string) $user->email))),
        ]);

        return [
            'redirect_url' => $base.'/sso/whmcs?t='.$jti,
            'expires_in' => 120,
        ];
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
