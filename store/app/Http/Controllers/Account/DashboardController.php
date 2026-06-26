<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\Panel\PanelCustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class DashboardController extends Controller
{
    public function index(Request $request, PanelCustomerService $panel)
    {
        $user = $request->user();
        $orders = $user->orders()->latest()->limit(5)->get();
        $panelSummary = null;
        $panelError = null;

        if ($user->panel_user_id) {
            $cacheKey = 'panel:summary:'.$user->id;
            $panelSummary = Cache::get($cacheKey);

            if ($panelSummary === null) {
                try {
                    $panelSummary = $panel->summary($user);
                } catch (RuntimeException $e) {
                    $panelError = $e->getMessage();
                }
            }
        }

        return view('account.dashboard', compact('user', 'orders', 'panelSummary', 'panelError'));
    }
}
