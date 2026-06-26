<?php

namespace App\Http\Controllers\Api\Billing;

use App\Http\Controllers\Controller;
use App\Services\Billing\BillingSettings;
use App\Services\Domain\DomainAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DomainRegisterController extends Controller
{
    public function __construct(
        private DomainAvailabilityService $availability,
        private BillingSettings $settings,
    ) {}

    public function tlds(): JsonResponse
    {
        if (! (bool) $this->settings->get('domain_register_enabled', true)) {
            return response()->json(['enabled' => false, 'tlds' => []]);
        }

        return response()->json([
            'enabled' => true,
            'currency' => $this->settings->currency(),
            'tlds' => $this->availability->listTlds(),
        ]);
    }

    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate(['domain' => ['required', 'string', 'max:253']]);

        return response()->json($this->availability->check($validated['domain']));
    }
}
