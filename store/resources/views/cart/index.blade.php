@extends('layouts.app')

@section('title', 'Sepet')

@section('content')
<section class="py-16">
    <div class="mx-auto max-w-4xl px-4 lg:px-8">
        <h1 class="text-3xl font-bold text-stone-900">Sepetiniz</h1>

        @if(empty($items))
            <div class="mt-12 text-center">
                <p class="text-stone-500">Sepetiniz boş.</p>
                <a href="{{ route('products.index') }}" class="btn-primary mt-6 inline-flex">Paketlere Göz At</a>
            </div>
        @else
            <div class="mt-8 space-y-4">
                @foreach($items as $key => $item)
                    <div class="flex items-center justify-between rounded-xl border border-stone-200 bg-white p-4">
                        <div>
                            <h3 class="font-semibold text-stone-900">{{ $item['product_name'] }}</h3>
                            <p class="text-sm text-stone-500">
                                {{ \App\Support\BillingCycle::label($item['billing_cycle'] ?? 'monthly') }} × {{ $item['quantity'] }}
                                @if(!empty($item['service_domain']))
                                    · {{ $item['service_domain'] }}
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="font-bold text-[#C2410C]">₺{{ number_format($item['unit_price'] * $item['quantity'], 2, ',', '.') }}</span>
                            <form action="{{ route('cart.remove', $key) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-sm text-red-600 hover:underline">Kaldır</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 rounded-2xl border border-stone-200 bg-stone-50 p-6">
                <div class="flex justify-between text-sm text-stone-600">
                    <span>Ara toplam</span>
                    <span>₺{{ number_format($subtotal, 2, ',', '.') }}</span>
                </div>
                @if(($discount ?? 0) > 0)
                    <div class="mt-2 flex justify-between text-sm text-green-700">
                        <span>İndirim</span>
                        <span>-₺{{ number_format($discount, 2, ',', '.') }}</span>
                    </div>
                @endif
                <div class="mt-2 flex justify-between text-lg font-bold">
                    <span>Toplam</span>
                    <span class="text-[#C2410C]">₺{{ number_format($total, 2, ',', '.') }}</span>
                </div>
                <a href="{{ route('checkout.index') }}" class="btn-primary mt-6 w-full text-center">Ödemeye Geç</a>
            </div>
        @endif
    </div>
</section>
@endsection
