@extends('layouts.app')

@section('title', 'Ödeme')

@section('content')
<section class="py-16">
    <div class="mx-auto max-w-5xl px-4 lg:px-8">
        <h1 class="text-3xl font-bold text-stone-900">Ödeme</h1>
        <form action="{{ route('checkout.process') }}" method="POST" class="mt-8 grid gap-8 lg:grid-cols-2">
            @csrf
            @if($errors->any())
                <div class="lg:col-span-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            <div class="space-y-6">
                <div class="card">
                    <h2 class="font-bold text-stone-900">İletişim Bilgileri</h2>
                    <div class="mt-4 space-y-4">
                        <input type="text" name="customer_name" placeholder="Ad Soyad *" required value="{{ old('customer_name', $customerDefaults['customer_name'] ?? '') }}" class="w-full rounded-xl border border-stone-300 px-4 py-3">
                        <input type="email" name="customer_email" placeholder="E-posta *" required value="{{ old('customer_email', $customerDefaults['customer_email'] ?? '') }}" class="w-full rounded-xl border border-stone-300 px-4 py-3">
                        <input type="text" name="customer_phone" placeholder="Telefon" value="{{ old('customer_phone', $customerDefaults['customer_phone'] ?? '') }}" class="w-full rounded-xl border border-stone-300 px-4 py-3">
                        <input type="text" name="customer_company" placeholder="Şirket" value="{{ old('customer_company', $customerDefaults['customer_company'] ?? '') }}" class="w-full rounded-xl border border-stone-300 px-4 py-3">
                        <textarea name="customer_address" placeholder="Adres" rows="2" class="w-full rounded-xl border border-stone-300 px-4 py-3">{{ old('customer_address', $customerDefaults['customer_address'] ?? '') }}</textarea>
                        @if(($hasHosting ?? false) && ($needsDomainInput ?? true))
                            <input type="text" name="service_domain" placeholder="Hosting alan adı (ör. siteim.com) *" required value="{{ old('service_domain') }}" class="w-full rounded-xl border border-stone-300 px-4 py-3">
                            <p class="text-xs text-stone-500">Hosting paketiniz bu alan adına kurulacaktır.</p>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <h2 class="font-bold text-stone-900">Ödeme Yöntemi</h2>
                    <div class="mt-4 space-y-3">
                        @foreach($paymentMethods as $method)
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-stone-200 p-4 hover:border-[#C2410C] has-[:checked]:border-[#C2410C] has-[:checked]:bg-orange-50/50">
                                <input type="radio" name="payment_method_id" value="{{ $method->id }}" required {{ old('payment_method_id') == $method->id ? 'checked' : '' }}>
                                <div>
                                    <span class="font-semibold text-stone-900">{{ $method->name }}</span>
                                    @if($method->description)<p class="text-sm text-stone-500">{{ $method->description }}</p>@endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div>
                <div class="card sticky top-24">
                    <h2 class="font-bold text-stone-900">Sipariş Özeti</h2>
                    <ul class="mt-4 space-y-2 text-sm text-stone-600">
                        @foreach($items as $item)
                            <li class="flex justify-between gap-4">
                                <span>{{ $item['product_name'] }}</span>
                                <span class="shrink-0">
                                    @if(($item['original_price'] ?? $item['unit_price']) > $item['unit_price'])
                                        <span class="text-stone-400 line-through">₺{{ number_format(($item['original_price'] ?? 0) * $item['quantity'], 2, ',', '.') }}</span>
                                    @endif
                                    ₺{{ number_format($item['unit_price'] * $item['quantity'], 2, ',', '.') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-4 border-t border-stone-200 pt-4">
                        @if($appliedCoupon)
                            <div class="mb-3 flex items-center justify-between rounded-lg bg-green-50 px-3 py-2 text-sm">
                                <span class="font-medium text-green-800">Kupon: {{ $appliedCoupon->code }}</span>
                                <form action="{{ route('checkout.coupon.remove') }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-green-700 hover:underline">Kaldır</button>
                                </form>
                            </div>
                        @else
                            <form action="{{ route('checkout.coupon.apply') }}" method="POST" class="flex gap-2">
                                @csrf
                                <input type="text" name="code" placeholder="Kupon kodu" class="flex-1 rounded-lg border border-stone-300 px-3 py-2 text-sm uppercase" value="{{ old('code') }}">
                                <button type="submit" class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800">Uygula</button>
                            </form>
                        @endif
                    </div>

                    <div class="mt-4 space-y-2 border-t border-stone-200 pt-4 text-sm">
                        <div class="flex justify-between"><span>Ara toplam</span><span>₺{{ number_format($subtotal, 2, ',', '.') }}</span></div>
                        @if($discount > 0)
                            <div class="flex justify-between text-green-700"><span>İndirim</span><span>-₺{{ number_format($discount, 2, ',', '.') }}</span></div>
                        @endif
                    </div>
                    <div class="mt-2 flex justify-between text-lg font-bold">
                        <span>Toplam</span><span class="text-[#C2410C]">₺{{ number_format($total, 2, ',', '.') }}</span>
                    </div>
                    <label class="mt-4 flex items-start gap-2 text-xs text-stone-600">
                        <input type="checkbox" name="terms_accepted" value="1" @checked(old('terms_accepted')) class="mt-0.5">
                        <span>
                            <a href="{{ route('pages.show', 'mesafeli-satis-sozlesmesi') }}" target="_blank" class="text-hv-primary hover:underline">Mesafeli Satış Sözleşmesi</a>,
                            <a href="{{ route('pages.show', 'iade-iptal-ve-cayma-politikasi') }}" target="_blank" class="text-hv-primary hover:underline">İade/İptal Politikası</a> ve
                            <a href="{{ route('pages.show', 'gizlilik') }}" target="_blank" class="text-hv-primary hover:underline">Gizlilik Politikası</a>'nı okudum, onaylıyorum.
                        </span>
                    </label>
                    @error('terms_accepted')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    <button type="submit" class="btn-primary mt-6 w-full">Siparişi Tamamla</button>
                    <p class="mt-4 text-center text-xs text-stone-500">256-bit SSL ile güvenli ödeme</p>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection
