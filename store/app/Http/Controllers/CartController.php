<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use InvalidArgumentException;

class CartController extends Controller
{
    public function index(CartService $cart)
    {
        try {
            $items = $cart->validatedItems();
        } catch (InvalidArgumentException $e) {
            return redirect()->route('products.index')->with('error', $e->getMessage());
        }

        return view('cart.index', [
            'items' => $items,
            'subtotal' => $cart->subtotal(),
            'discount' => $cart->couponDiscount(),
            'total' => $cart->grossTotal(),
            'tax' => $cart->taxBreakdown(),
        ]);
    }

    public function remove(string $key, CartService $cart)
    {
        $cart->remove($key);

        return back()->with('success', 'Ürün sepetten kaldırıldı.');
    }

    public function clear(CartService $cart)
    {
        $cart->clear();

        return redirect()->route('home')->with('success', 'Sepet temizlendi.');
    }

    public function count(CartService $cart)
    {
        return response()->json(['count' => $cart->count()])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}
