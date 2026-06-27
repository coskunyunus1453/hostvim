@extends('layouts.app')

@section('content')
<section class="py-10">
    <div class="mx-auto max-w-4xl px-4 lg:px-8">
        <h1 class="text-2xl font-extrabold text-stone-900">Sipariş Özeti</h1>
        <p class="mt-1 text-stone-600">Bilgileri kontrol edip sepete ekleyin.</p>

        @include('hosting.configure.partials.wizard-nav', ['step' => $step])

        <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <h2 class="font-bold text-stone-900">{{ $product->name }}</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-stone-600">Alan adı</dt>
                    <dd class="font-semibold">{{ $config['domain_name'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-stone-600">Alan adı seçeneği</dt>
                    <dd class="font-semibold">
                        @switch($config['domain_mode'])
                            @case('register') Yeni tescil @break
                            @case('transfer') Transfer @break
                            @default Mevcut alan adım @endswitch
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-stone-600">Ödeme sıklığı</dt>
                    <dd class="font-semibold">{{ \App\Support\BillingCycle::label($config['billing_cycle']) }}</dd>
                </div>
                <div class="flex justify-between border-t border-stone-100 pt-3">
                    <dt class="text-stone-600">Hosting</dt>
                    <dd class="font-semibold">₺{{ number_format($pricing['final'], 2, ',', '.') }}</dd>
                </div>
                @foreach($addonLines as $line)
                    <div class="flex justify-between">
                        <dt class="text-stone-600">{{ $line['name'] }}</dt>
                        <dd class="font-semibold">₺{{ number_format($line['price'], 2, ',', '.') }}</dd>
                    </div>
                @endforeach
                @if(($config['domain_mode'] ?? '') === 'register')
                    <div class="flex justify-between text-stone-500">
                        <dt>Alan adı kaydı</dt>
                        <dd>Sepette hesaplanır</dd>
                    </div>
                @endif
                <div class="flex justify-between border-t border-stone-200 pt-4 text-base">
                    <dt class="font-bold text-stone-900">Hosting toplamı</dt>
                    <dd class="font-bold text-[#C2410C]">₺{{ number_format($hostingTotal, 2, ',', '.') }}</dd>
                </div>
            </dl>
        </div>

        <form method="POST" action="{{ route('hosting.configure.complete') }}" class="mt-6 flex justify-between gap-3">
            @csrf
            <a href="{{ route('hosting.configure.options') }}" class="rounded-xl border border-stone-300 px-6 py-3 font-semibold text-stone-700">Geri</a>
            <button type="submit" class="btn-primary px-8 py-3">Sepete Ekle ve Ödemeye Geç</button>
        </form>
    </div>
</section>
@endsection
