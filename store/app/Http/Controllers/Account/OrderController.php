<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()->orders()->with('items')->latest()->paginate(15);

        return view('account.orders', compact('orders'));
    }

    public function show(Request $request, int $orderId)
    {
        $order = $request->user()->orders()->with('items', 'paymentMethod')->findOrFail($orderId);

        return view('account.order-show', compact('order'));
    }
}
