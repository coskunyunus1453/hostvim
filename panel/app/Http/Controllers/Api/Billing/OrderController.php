<?php

namespace App\Http\Controllers\Api\Billing;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Billing\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()->orders()
            ->with('items.hostingPackage')
            ->latest()
            ->paginate(20);

        return response()->json($orders);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        return response()->json($order->load('items.hostingPackage', 'invoices'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:10'],
            'items.*.item_type' => ['nullable', 'string', 'in:hosting,domain_register'],
            'items.*.package_id' => ['nullable', 'integer', 'exists:hosting_packages,id'],
            'items.*.billing_cycle' => ['nullable', 'string', 'in:monthly,yearly'],
            'items.*.domain' => ['nullable', 'string', 'max:253'],
            'items.*.domain_years' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        try {
            $result = $this->orders->place($request->user(), $validated['items']);
        } catch (ValidationException $e) {
            throw $e;
        }

        return response()->json([
            'order' => $result['order']->load('items.hostingPackage'),
            'invoice' => $result['invoice']->load('items'),
        ], 201);
    }
}
