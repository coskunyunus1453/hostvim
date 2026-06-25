<?php

namespace App\Http\Controllers;

use App\Services\CampaignService;
use App\Services\CartService;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function apply(Request $request, CartService $cart, CampaignService $campaigns)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
        ]);

        $items = $cart->validatedItems();

        if (empty($items)) {
            return back()->with('error', 'Sepetiniz boş.');
        }

        try {
            $campaign = $campaigns->validateCouponForCart($validated['code'], $items);
            $campaigns->applyCoupon($campaign->code);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return back()->with('success', 'Kupon uygulandı: ' . $campaign->title);
    }

    public function remove(CampaignService $campaigns)
    {
        $campaigns->removeCoupon();

        return back()->with('success', 'Kupon kaldırıldı.');
    }
}
