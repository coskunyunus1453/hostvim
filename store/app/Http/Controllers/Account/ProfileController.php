<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\Panel\PanelCustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('account.profile', ['user' => $request->user()->fresh()]);
    }

    public function update(Request $request, PanelCustomerService $panel)
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'company' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:120'],
            'district' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'size:2'],
            'tax_office' => ['nullable', 'string', 'max:120'],
            'tax_number' => ['nullable', 'string', 'max:32'],
            'billing_company' => ['nullable', 'string', 'max:255'],
            'billing_address' => ['nullable', 'string', 'max:500'],
            'billing_city' => ['nullable', 'string', 'max:120'],
            'billing_district' => ['nullable', 'string', 'max:120'],
            'billing_postal_code' => ['nullable', 'string', 'max:20'],
            'billing_country' => ['nullable', 'string', 'size:2'],
            'use_profile_as_billing' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('use_profile_as_billing')) {
            $validated['billing_company'] = $validated['company'] ?? $user->company;
            $validated['billing_address'] = $validated['address'] ?? $user->address;
            $validated['billing_city'] = $validated['city'] ?? $user->city;
            $validated['billing_district'] = $validated['district'] ?? $user->district;
            $validated['billing_postal_code'] = $validated['postal_code'] ?? $user->postal_code;
            $validated['billing_country'] = $validated['country'] ?? $user->country;
        }

        $user->update($validated);

        if ($user->panel_user_id) {
            try {
                $panel->updatePanelProfile($user, ['name' => $user->name]);
            } catch (RuntimeException) {
                // Panel profil senkronu opsiyonel
            }
        }

        return back()->with('success', 'Profil bilgileriniz güncellendi.');
    }

    public function updatePassword(Request $request, PanelCustomerService $panel)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], (string) $user->password)) {
            return back()->withErrors(['current_password' => 'Mevcut şifre hatalı.']);
        }

        $user->update(['password' => $validated['password']]);

        if ($user->panel_user_id) {
            try {
                $panel->updatePanelPassword(
                    $user,
                    $validated['current_password'],
                    $validated['password'],
                    $validated['password_confirmation'] ?? $validated['password'],
                );
            } catch (RuntimeException $e) {
                return back()->with('error', 'Mağaza şifresi güncellendi; panel senkronu: '.$e->getMessage());
            }
        }

        return back()->with('success', 'Şifreniz güncellendi.');
    }
}
