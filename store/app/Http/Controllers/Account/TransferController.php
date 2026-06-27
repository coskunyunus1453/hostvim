<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\DomainName;
use App\Models\Order;
use App\Models\OwnershipTransferRequest;
use App\Services\OwnershipTransferService;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function __construct(private OwnershipTransferService $transfers) {}

    public function requestDomain(Request $request, int $id)
    {
        $domain = DomainName::query()->findOrFail($id);

        $validated = $request->validate([
            'target_email' => ['required', 'email', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->transfers->requestDomainTransfer(
            $request->user(),
            $domain,
            $validated['target_email'],
            $validated['note'] ?? null,
        );

        return back()->with('success', 'Devir talebiniz alındı. Ekibimiz onayladığında bilgilendirileceksiniz.');
    }

    public function requestHosting(Request $request, int $orderId)
    {
        $order = Order::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($orderId);

        $validated = $request->validate([
            'target_email' => ['required', 'email', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $serviceDomain = (string) ($order->items()->where('item_type', 'hosting')->value('service_domain')
            ?: $order->items()->where('item_type', 'hosting')->value('domain_name')
            ?: '');

        $this->transfers->requestHostingTransfer(
            $request->user(),
            $order,
            $serviceDomain,
            $validated['target_email'],
            $validated['note'] ?? null,
        );

        return back()->with('success', 'Hosting devir talebiniz alındı. Ekibimiz onayladığında bilgilendirileceksiniz.');
    }

    public function cancel(Request $request, OwnershipTransferRequest $transfer)
    {
        abort_unless((int) $transfer->user_id === (int) $request->user()->id, 403);

        $this->transfers->cancel($transfer);

        return back()->with('success', 'Devir talebi iptal edildi.');
    }
}
