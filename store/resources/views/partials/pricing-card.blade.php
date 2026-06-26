<div class="pricing-card {{ $product->is_popular ? 'pricing-card-popular' : '' }}">
    @if($product->is_popular)
        <span class="badge-popular">En Popüler</span>
    @endif
    @php
        $monthlyPricing = $campaignService->pricingFor($product, 'monthly');
        $yearlyPricing = $product->price_yearly ? $campaignService->pricingFor($product, 'yearly') : null;
    @endphp
    @if($monthlyPricing['campaign'] && $monthlyPricing['discount'] > 0)
        <span class="pricing-campaign-badge">{{ $monthlyPricing['badge'] }}</span>
    @endif
    <h3 class="text-xl font-bold text-stone-900">{{ $product->name }}</h3>
    <p class="mt-2 min-h-[40px] text-sm text-stone-500">{{ $product->short_description }}</p>

    <div class="my-6">
        @if($product->price_monthly)
            @include('partials.pricing-price', ['product' => $product, 'cycle' => 'monthly', 'suffix' => '/ay'])
            @if($product->price_yearly)
                <p class="mt-1 text-xs text-[#166534]">
                    Yıllık
                    @if($yearlyPricing && $yearlyPricing['discount'] > 0)
                        <span class="line-through text-stone-400">₺{{ number_format($yearlyPricing['original'], 0, ',', '.') }}</span>
                        <span class="font-semibold">₺{{ number_format($yearlyPricing['final'], 0, ',', '.') }}</span>
                    @else
                        ₺{{ number_format($product->price_yearly, 0, ',', '.') }}
                    @endif
                    — tasarruf edin
                </p>
            @endif
        @elseif($product->price_onetime)
            @include('partials.pricing-price', ['product' => $product, 'cycle' => 'onetime', 'suffix' => 'tek sefer'])
        @endif
    </div>

    @if($product->features)
        <ul class="mb-8 flex-1 space-y-2.5">
            @foreach($product->features as $feature)
                <li class="flex items-start gap-2 text-sm text-stone-600">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#166534]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    {{ is_array($feature) ? ($feature['feature'] ?? reset($feature)) : $feature }}
                </li>
            @endforeach
        </ul>
    @endif

    <a href="{{ route('products.show', [$category->slug, $product->slug]) }}" class="{{ $product->is_popular ? 'btn-primary w-full text-center' : 'btn-secondary w-full text-center' }}">
        Detay & Satın Al
    </a>
</div>
