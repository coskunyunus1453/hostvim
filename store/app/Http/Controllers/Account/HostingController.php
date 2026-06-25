<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\Panel\PanelCustomerService;
use Illuminate\Http\Request;
use RuntimeException;

class HostingController extends Controller
{
    public function index(Request $request, PanelCustomerService $panel)
    {
        $user = $request->user();

        if (! $user->panel_user_id) {
            return view('account.hosting', ['linked' => false, 'hosting' => null]);
        }

        try {
            $hosting = $panel->hosting($user);
        } catch (RuntimeException $e) {
            return view('account.hosting', [
                'linked' => true,
                'hosting' => null,
                'error' => $e->getMessage(),
            ]);
        }

        return view('account.hosting', [
            'linked' => true,
            'hosting' => $hosting,
        ]);
    }

    public function panelLogin(Request $request, PanelCustomerService $panel)
    {
        try {
            $sso = $panel->panelSso($request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->away($sso['redirect_url']);
    }
}
