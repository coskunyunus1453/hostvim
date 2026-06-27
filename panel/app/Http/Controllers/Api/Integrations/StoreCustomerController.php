<?php

namespace App\Http\Controllers\Api\Integrations;

use App\Http\Controllers\Controller;
use App\Models\DomainRegistration;
use App\Models\User;
use App\Services\Integrations\StoreCustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreCustomerController extends Controller
{
    public function __construct(private StoreCustomerService $customers) {}

    public function linkByEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        return response()->json($this->customers->linkByEmail($validated['email']));
    }

    public function summary(Request $request): JsonResponse
    {
        return response()->json($this->customers->summary($this->resolveUser($request)));
    }

    public function domains(Request $request): JsonResponse
    {
        return response()->json($this->customers->domainsPortfolio($this->resolveUser($request)));
    }

    public function hosting(Request $request): JsonResponse
    {
        return response()->json($this->customers->hostingOverview($this->resolveUser($request)));
    }

    public function invoices(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));

        return response()->json($this->customers->invoices($this->resolveUser($request), $page));
    }

    public function invoiceShow(Request $request, int $invoiceId): JsonResponse
    {
        return response()->json($this->customers->invoiceDetail($this->resolveUser($request), $invoiceId));
    }

    public function invoicePay(Request $request, int $invoiceId): JsonResponse
    {
        return response()->json($this->customers->initiateInvoicePayment($this->resolveUser($request), $invoiceId));
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);

        return response()->json(['user' => $this->customers->profilePayload($user)]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'panel_user_id' => ['required', 'integer', 'min:1'],
            'name' => ['sometimes', 'string', 'max:255'],
            'locale' => ['sometimes', 'nullable', 'string', 'in:en,tr'],
        ]);

        $user = $this->resolveUser($request);

        return response()->json($this->customers->updateProfile($user, $validated));
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'panel_user_id' => ['required', 'integer', 'min:1'],
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ]);

        $user = $this->resolveUser($request);
        $this->customers->updatePassword($user, $validated['current_password'], $validated['password']);

        return response()->json(['message' => __('auth.password_updated')]);
    }

    public function requestTransfer(Request $request): JsonResponse
    {
        $providerIds = collect(config('registrars.providers', []))->pluck('id')->all();

        $validated = $request->validate([
            'panel_user_id' => ['required', 'integer', 'min:1'],
            'domain' => ['required', 'string', 'max:253', 'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,24}$/i'],
            'source_registrar' => ['required', 'string', Rule::in($providerIds)],
            'auth_code' => ['nullable', 'string', 'max:64'],
            'direction' => ['sometimes', Rule::in(['in', 'out'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $this->resolveUser($request);
        $transfer = $this->customers->requestTransfer($user, $validated);

        return response()->json([
            'message' => 'Transfer talebi alındı. Ekibimiz kısa sürede işleme alacaktır.',
            'transfer' => $transfer,
        ], 201);
    }

    public function updateRegistration(Request $request, int $registrationId): JsonResponse
    {
        $validated = $request->validate([
            'panel_user_id' => ['required', 'integer', 'min:1'],
            'auto_renew' => ['sometimes', 'boolean'],
            'locked' => ['sometimes', 'boolean'],
        ]);

        $user = $this->resolveUser($request);
        $registration = DomainRegistration::query()->findOrFail($registrationId);
        $updated = $this->customers->updateRegistration($user, $registration, $validated);

        return response()->json([
            'message' => 'Alan adı ayarları güncellendi.',
            'registration' => $updated,
        ]);
    }

    public function transferOwnership(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'panel_user_id' => ['required', 'integer', 'min:1'],
            'target_email' => ['required', 'email', 'max:255'],
            'type' => ['required', Rule::in(['domain', 'hosting'])],
            'domain' => ['required', 'string', 'max:253', 'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,24}$/i'],
        ]);

        $source = $this->resolveUser($request);
        $result = $this->customers->transferOwnership($source, $validated);

        return response()->json($result);
    }

    public function panelSso(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);

        return response()->json($this->customers->mintPanelSso($user));
    }

    private function resolveUser(Request $request): User
    {
        $panelUserId = (int) ($request->input('panel_user_id') ?? $request->query('panel_user_id'));
        if ($panelUserId < 1) {
            throw ValidationException::withMessages(['panel_user_id' => 'panel_user_id gerekli.']);
        }

        $user = User::query()->find($panelUserId);
        if ($user === null || $user->status !== 'active') {
            abort(404, 'Panel hesabı bulunamadı.');
        }
        if ($user->isAdmin() || $user->isVendorOperator()) {
            abort(403, 'Bu hesap mağaza müşteri API\'si ile kullanılamaz.');
        }

        return $user;
    }
}
