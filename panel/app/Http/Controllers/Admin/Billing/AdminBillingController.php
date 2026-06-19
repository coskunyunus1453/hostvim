<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Subscription;
use App\Services\Billing\InvoiceService;
use App\Services\Provisioning\ProvisioningService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBillingController extends Controller
{
    public function __construct(
        private InvoiceService $invoices,
        private ProvisioningService $provisioning,
    ) {}

    public function stats(): JsonResponse
    {
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();

        return response()->json([
            'invoices' => [
                'unpaid' => Invoice::where('status', Invoice::STATUS_UNPAID)->count(),
                'overdue' => Invoice::where('status', Invoice::STATUS_OVERDUE)->count(),
                'paid_this_month' => Invoice::where('status', Invoice::STATUS_PAID)
                    ->where('paid_at', '>=', $monthStart)->count(),
            ],
            'revenue_this_month' => (float) Invoice::where('status', Invoice::STATUS_PAID)
                ->where('paid_at', '>=', $monthStart)->sum('total'),
            'outstanding' => (float) Invoice::whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_OVERDUE])->sum('total'),
            'orders_pending' => Order::where('status', Order::STATUS_PENDING)->count(),
            'services' => [
                'active' => Subscription::where('service_status', Subscription::SERVICE_ACTIVE)->count(),
                'suspended' => Subscription::where('service_status', Subscription::SERVICE_SUSPENDED)->count(),
            ],
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        $query = Order::query()->with('user:id,name,email', 'items.hostingPackage:id,name')->latest();
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return response()->json($query->paginate(25));
    }

    public function invoices(Request $request): JsonResponse
    {
        $query = Invoice::query()->with('user:id,name,email')->latest();
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($search = trim($request->string('q')->toString())) {
            $query->where('number', 'like', '%'.$search.'%');
        }

        return response()->json($query->paginate(25));
    }

    public function showInvoice(Invoice $invoice): JsonResponse
    {
        return response()->json($invoice->load('items', 'user:id,name,email', 'order', 'subscription'));
    }

    public function storeInvoice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'due_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'notify' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.description' => ['required', 'string', 'max:200'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $invoice = $this->invoices->createManual(
            (int) $validated['user_id'],
            $validated['items'],
            isset($validated['due_at']) ? Carbon::parse($validated['due_at']) : null,
            $validated['notes'] ?? null,
            (bool) ($validated['notify'] ?? true),
        );

        return response()->json($invoice->load('items', 'user:id,name,email'), 201);
    }

    public function markPaid(Request $request, Invoice $invoice): JsonResponse
    {
        $validated = $request->validate([
            'method' => ['nullable', 'string', 'max:40'],
            'reference' => ['nullable', 'string', 'max:120'],
        ]);

        $invoice = $this->invoices->markPaid(
            $invoice,
            $validated['method'] ?? 'manual',
            $validated['reference'] ?? null,
        );

        return response()->json($invoice->load('items', 'user:id,name,email'));
    }

    public function cancelInvoice(Invoice $invoice): JsonResponse
    {
        return response()->json($this->invoices->cancel($invoice));
    }

    public function cancelOrder(Order $order): JsonResponse
    {
        $order->update(['status' => Order::STATUS_CANCELLED]);

        return response()->json($order);
    }

    // ---- Hizmet (service) yönetimi ----

    public function services(Request $request): JsonResponse
    {
        $query = Subscription::query()
            ->where('payment_provider', 'manual')
            ->with('user:id,name,email', 'hostingPackage:id,name', 'domain:id,name')
            ->latest();
        if ($status = $request->string('service_status')->toString()) {
            $query->where('service_status', $status);
        }

        return response()->json($query->paginate(25));
    }

    public function suspendService(Subscription $subscription): JsonResponse
    {
        $this->provisioning->suspend($subscription, reason: 'admin');

        return response()->json($subscription->fresh());
    }

    public function unsuspendService(Subscription $subscription): JsonResponse
    {
        $this->provisioning->unsuspend($subscription);

        return response()->json($subscription->fresh());
    }

    public function terminateService(Request $request, Subscription $subscription): JsonResponse
    {
        $deleteSite = $request->boolean('delete_site');
        $this->provisioning->terminate($subscription, deleteSite: $deleteSite);

        return response()->json($subscription->fresh());
    }
}
