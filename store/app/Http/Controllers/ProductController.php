<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\CartService;
use App\Services\SeoService;
use App\Support\BillingCycle;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ProductController extends Controller
{
    public function index(SeoService $seo)
    {
        $categories = ProductCategory::where('is_active', true)
            ->with(['activeProducts'])
            ->orderBy('sort_order')
            ->get();

        $breadcrumbs = [
            ['label' => 'Ana Sayfa', 'url' => route('home')],
            ['label' => 'Ürünler', 'url' => null],
        ];

        return view('products.index', [
            'categories' => $categories,
            'seo' => $seo->forProductsIndex(),
            'breadcrumbs' => $breadcrumbs,
            'schemas' => [$seo->breadcrumbSchema($breadcrumbs)],
        ]);
    }

    public function category(string $slug, SeoService $seo)
    {
        $category = ProductCategory::where('slug', $slug)->where('is_active', true)
            ->with(['activeProducts'])
            ->firstOrFail();

        $breadcrumbs = [
            ['label' => 'Ana Sayfa', 'url' => route('home')],
            ['label' => 'Ürünler', 'url' => route('products.index')],
            ['label' => $category->name, 'url' => null],
        ];

        return view('products.category', [
            'category' => $category,
            'seo' => $seo->forCategory($category),
            'breadcrumbs' => $breadcrumbs,
            'schemas' => [$seo->breadcrumbSchema($breadcrumbs)],
        ]);
    }

    public function show(string $categorySlug, string $slug, SeoService $seo)
    {
        $category = ProductCategory::where('slug', $categorySlug)->where('is_active', true)->firstOrFail();
        $product = Product::where('slug', $slug)
            ->where('product_category_id', $category->id)
            ->where('is_active', true)
            ->firstOrFail();

        $breadcrumbs = [
            ['label' => 'Ana Sayfa', 'url' => route('home')],
            ['label' => 'Ürünler', 'url' => route('products.index')],
            ['label' => $category->name, 'url' => route('products.category', $category->slug)],
            ['label' => $product->name, 'url' => null],
        ];

        return view('products.show', [
            'category' => $category,
            'product' => $product,
            'seo' => $seo->forProduct($product, $category),
            'breadcrumbs' => $breadcrumbs,
            'schemas' => [
                $seo->breadcrumbSchema($breadcrumbs),
                $seo->productSchema($product, $category),
            ],
            'configureUrl' => $product->isHosting()
                ? route('hosting.configure.start', [$category->slug, $product->slug])
                : null,
        ]);
    }

    public function addToCart(Request $request, string $categorySlug, string $slug, CartService $cart)
    {
        $validated = $request->validate([
            'billing_cycle' => 'required|in:'.implode(',', BillingCycle::all()),
            'install_panel' => 'sometimes|boolean',
        ]);

        $category = ProductCategory::where('slug', $categorySlug)->where('is_active', true)->firstOrFail();
        $product = Product::where('slug', $slug)
            ->where('product_category_id', $category->id)
            ->where('is_active', true)
            ->firstOrFail();

        try {
            $cart->add($product, $validated['billing_cycle'], 1, (bool) ($validated['install_panel'] ?? false));
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('cart.index')->with('success', 'Ürün sepete eklendi.');
    }
}
