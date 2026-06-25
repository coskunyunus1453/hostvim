@extends('layouts.app')

@section('content')
<section class="py-12">
    <div class="mx-auto max-w-7xl px-4 lg:px-8">
        <div class="grid gap-12 lg:grid-cols-2">
            <div>
                <span class="text-sm font-bold uppercase text-[#166534]">{{ $category->name }}</span>
                <h1 class="mt-2 text-4xl font-extrabold text-stone-900">{{ $product->name }}</h1>
                <p class="mt-4 text-lg text-stone-600">{{ $product->short_description }}</p>

                @if($product->description)
                    <div class="prose-hostvim mt-8">{!! safe_html($product->description) !!}</div>
                @endif

                @if($product->specs)
                    <div class="mt-8">
                        <h3 class="font-bold text-stone-900">Teknik Özellikler</h3>
                        <dl class="mt-4 grid grid-cols-2 gap-3">
                            @foreach($product->specs as $key => $value)
                                <div class="rounded-lg bg-stone-50 px-4 py-3">
                                    <dt class="text-xs font-semibold uppercase text-stone-500">{{ $key }}</dt>
                                    <dd class="mt-1 font-semibold text-stone-900">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif
            </div>

            <div class="lg:sticky lg:top-24 lg:self-start">
                <div class="rounded-2xl border border-stone-200 bg-white p-8 shadow-lg">
                    @if(!empty($configureUrl))
                        <a href="{{ $configureUrl }}" class="btn-primary flex w-full items-center justify-center py-3 text-center">
                            Satın Al — Yapılandır
                        </a>
                        <p class="mt-3 text-center text-xs text-stone-500">Alan adı seçimi ve paket ayarları ile devam edersiniz.</p>
                    @else
                    <form action="{{ route('products.cart.add', [$category->slug, $product->slug]) }}" method="POST">
                        @csrf
                        <label class="block text-sm font-semibold text-stone-700">Fatura Dönemi</label>
                        <select name="billing_cycle" id="billing-cycle-select" class="mt-2 w-full rounded-xl border border-stone-300 px-4 py-3 focus:border-[#C2410C] focus:ring-[#C2410C]">
                            @if($product->price_monthly)
                                @php $m = $campaignService->pricingFor($product, 'monthly'); @endphp
                                <option value="monthly" data-original="{{ $m['original'] }}" data-final="{{ $m['final'] }}" data-discount="{{ $m['discount'] }}">
                                    Aylık — ₺{{ number_format($m['final'], 2, ',', '.') }}
                                    @if($m['discount'] > 0) (₺{{ number_format($m['original'], 2, ',', '.') }} yerine) @endif
                                </option>
                            @endif
                            @if($product->price_yearly)
                                @php $y = $campaignService->pricingFor($product, 'yearly'); @endphp
                                <option value="yearly" data-original="{{ $y['original'] }}" data-final="{{ $y['final'] }}" data-discount="{{ $y['discount'] }}">
                                    Yıllık — ₺{{ number_format($y['final'], 2, ',', '.') }}
                                    @if($y['discount'] > 0) (₺{{ number_format($y['original'], 2, ',', '.') }} yerine) @endif
                                </option>
                            @endif
                            @if($product->price_onetime)
                                @php $o = $campaignService->pricingFor($product, 'onetime'); @endphp
                                <option value="onetime" data-original="{{ $o['original'] }}" data-final="{{ $o['final'] }}" data-discount="{{ $o['discount'] }}">
                                    Tek Sefer — ₺{{ number_format($o['final'], 2, ',', '.') }}
                                    @if($o['discount'] > 0) (₺{{ number_format($o['original'], 2, ',', '.') }} yerine) @endif
                                </option>
                            @endif
                        </select>

                        @if($product->isCloudProvision())
                            <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-xl border border-stone-200 bg-stone-50 px-4 py-3">
                                <input type="checkbox" name="install_panel" value="1" class="mt-1 rounded border-stone-300 text-[#C2410C] focus:ring-[#C2410C]">
                                <span class="text-sm text-stone-700">
                                    <span class="font-semibold">Panelze hosting paneli kurulsun</span>
                                    <span class="block text-xs text-stone-500">Sunucunuz teslim edilirken cPanel benzeri Panelze paneli otomatik kurulur. İşaretlemezseniz sunucu boş (yalnızca işletim sistemi) teslim edilir.</span>
                                </span>
                            </label>
                        @endif

                        @php $defaultPricing = $campaignService->pricingFor($product, $product->price_monthly ? 'monthly' : ($product->price_yearly ? 'yearly' : 'onetime')); @endphp
                        @if($defaultPricing['discount'] > 0)
                            <div class="mt-4 rounded-xl bg-orange-50 px-4 py-3 text-sm">
                                <span class="font-semibold text-[#C2410C]">{{ $defaultPricing['badge'] }}</span>
                                <span class="text-stone-600"> kampanyası geçerli</span>
                            </div>
                        @endif

                        <button type="submit" class="btn-primary mt-6 w-full">Sepete Ekle</button>
                    </form>
                    @endif
                    <ul class="mt-6 space-y-2 border-t border-stone-100 pt-6 text-sm text-stone-600">
                        <li class="flex gap-2"><span class="text-[#166534]">✓</span> Anında aktivasyon</li>
                        <li class="flex gap-2"><span class="text-[#166534]">✓</span> 7/24 destek</li>
                        <li class="flex gap-2"><span class="text-[#166534]">✓</span> Güvenli ödeme</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
