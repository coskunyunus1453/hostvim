@php
    $pricing = $campaignService->pricingFor($product, $cycle ?? 'monthly');
    $hasDiscount = $pricing['discount'] > 0;
@endphp
<div class="pricing-amount">
    @if($hasDiscount)
        <span class="pricing-badge-discount">{{ $pricing['badge'] }}</span>
    @endif
    <div class="flex items-baseline gap-2 flex-wrap">
        <span class="text-4xl font-extrabold text-[#C2410C]">₺{{ number_format($pricing['final'], 0, ',', '.') }}</span>
        @if($hasDiscount)
            <span class="text-lg text-stone-400 line-through">₺{{ number_format($pricing['original'], 0, ',', '.') }}</span>
        @endif
        <span class="text-stone-500">{{ $suffix ?? '/ay' }}</span>
    </div>
</div>
