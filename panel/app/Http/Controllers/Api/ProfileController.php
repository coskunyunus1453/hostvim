<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($request->user()->id),
            ],
            'locale' => 'sometimes|nullable|string|in:en,tr,de,fr,es,pt,zh,ja,ar,ru',
        ]);

        $request->user()->update($validated);

        return response()->json([
            'user' => $request->user()->fresh()->load(['roles', 'hostingPackage']),
        ]);
    }

    public function password(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        if (! Hash::check($validated['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('auth.current_password_invalid')],
            ]);
        }

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'force_password_change' => false,
        ]);

        return response()->json(['message' => __('auth.password_updated')]);
    }

    public function completeOnboarding(Request $request): JsonResponse
    {
        $request->user()->update(['onboarding_completed_at' => now()]);

        return response()->json([
            'user' => $request->user()->fresh()->load(['roles', 'hostingPackage']),
        ]);
    }
}
