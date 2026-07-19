<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OwnershipTransferRequest;
use App\Services\Panel\PanelCustomerService;
use Illuminate\Http\Request;
use RuntimeException;

class HostingController extends Controller
{
    public function index(Request $request, PanelCustomerService $panel)
    {
        $user = $request->user();

        $hostingOrders = $this->hostingOrders($user);
        $pendingTransfers = OwnershipTransferRequest::query()
            ->where('user_id', $user->id)
            ->where('type', OwnershipTransferRequest::TYPE_HOSTING)
            ->where('status', OwnershipTransferRequest::STATUS_PENDING)
            ->get()
            ->keyBy('order_id');

        if (! $user->panel_user_id) {
            return view('account.hosting', [
                'linked' => false,
                'hosting' => null,
                'hostingOrders' => $hostingOrders,
                'pendingTransfers' => $pendingTransfers,
            ]);
        }

        try {
            $hosting = $panel->hosting($user);
        } catch (RuntimeException $e) {
            return view('account.hosting', [
                'linked' => true,
                'hosting' => null,
                'error' => $e->getMessage(),
                'hostingOrders' => $hostingOrders,
                'pendingTransfers' => $pendingTransfers,
            ]);
        }

        return view('account.hosting', [
            'linked' => true,
            'hosting' => $hosting,
            'hostingOrders' => $hostingOrders,
            'pendingTransfers' => $pendingTransfers,
        ]);
    }

    /**
     * Kullanıcının devredilebilir hosting siparişleri (hosting kalemi içerenler).
     */
    private function hostingOrders(\App\Models\User $user)
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->where('payment_status', 'paid')
            ->whereHas('items', fn ($q) => $q->where('item_type', 'hosting'))
            ->with(['items' => fn ($q) => $q->where('item_type', 'hosting')])
            ->latest()
            ->get()
            ->map(function (Order $order) {
                $item = $order->items->first();
                $order->setAttribute('service_domain_label', $item?->service_domain ?: $item?->domain_name ?: $order->order_number);
                $order->setAttribute('hosting_product_label', $item?->product_name ?: 'Hosting');

                return $order;
            });
    }

    public function panelLogin(Request $request, PanelCustomerService $panel)
    {
        $user = $request->user();
        if (! $user->panel_user_id) {
            $panel->syncPanelUserId($user);
            $user->refresh();
        }

        try {
            $sso = $panel->panelSso($user);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->away($sso['redirect_url']);
    }
}
