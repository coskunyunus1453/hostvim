<?php

namespace App\Http\Middleware;

use App\Services\Panel\PanelCustomerService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncPanelUserAfterResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        if (! $user) {
            return $response;
        }

        if ($user->panel_user_id) {
            if (! $request->session()->get('panel_link_synced_'.$user->id)) {
                $request->session()->put('panel_link_synced_'.$user->id, true);
            }

            return $response;
        }

        if ($request->session()->get('panel_link_pending_'.$user->id)) {
            return $response;
        }

        $request->session()->put('panel_link_pending_'.$user->id, true);

        dispatch(function () use ($user): void {
            app(PanelCustomerService::class)->syncPanelUserId($user->fresh() ?? $user);
        })->afterResponse();

        return $response;
    }
}
