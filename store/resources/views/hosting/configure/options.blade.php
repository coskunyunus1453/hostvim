@extends('layouts.app')

@section('content')
<section class="py-10">
    <div class="mx-auto max-w-5xl px-4 lg:px-8">
        <h1 class="text-2xl font-extrabold text-hv-text">{{ $product->name }}</h1>
        <p class="mt-1 text-hv-muted">Ödeme sıklığı ve isteğe bağlı eklentileri seçin.</p>

        @include('hosting.configure.partials.wizard-nav', ['step' => $step])

        <form method="POST" action="{{ route('hosting.configure.options.store') }}" class="grid gap-8 lg:grid-cols-3">
            @csrf
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-2xl border border-hv-border bg-hv-elevated p-6 shadow-sm">
                    <h2 class="font-bold text-hv-text">Ödeme Sıklığı</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach($cycles as $cycle => $info)
                            <label class="hv-choice">
                                <input type="radio" name="billing_cycle" value="{{ $cycle }}" class="sr-only" @checked(old('billing_cycle', $config['billing_cycle'] ?? array_key_first($cycles)) === $cycle) required>
                                <span class="hv-choice__dot" aria-hidden="true"></span>
                                <span class="min-w-0">
                                    <span class="block font-semibold">{{ $info['label'] }}</span>
                                    <span class="mt-1 block text-lg font-bold text-hv-primary">₺{{ number_format($info['price'], 2, ',', '.') }}</span>
                                    @if($info['savings'])
                                        <span class="mt-1 inline-block rounded bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800">%{{ $info['savings'] }} tasarruf</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                @if($addons->isNotEmpty())
                    <div class="rounded-2xl border border-hv-border bg-hv-elevated p-6 shadow-sm">
                        <h2 class="font-bold text-hv-text">Mevcut Eklentiler</h2>
                        <p class="mt-1 text-sm text-hv-muted">Ana ürününüzle birlikte alabileceğiniz isteğe bağlı hizmetler.</p>
                        <ul class="mt-4 space-y-3">
                            @foreach($addons as $addon)
                                <li>
                                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-hv-border p-4 hover:bg-stone-50">
                                        <input type="checkbox" name="addon_ids[]" value="{{ $addon->id }}" class="mt-1 rounded border-hv-border text-hv-primary"
                                            @checked(in_array($addon->id, old('addon_ids', $config['addon_ids'] ?? [])))>
                                        <span class="flex-1">
                                            <span class="font-semibold text-hv-text">{{ $addon->name }}</span>
                                            @if($addon->description)
                                                <span class="mt-1 block text-sm text-hv-muted">{{ $addon->description }}</span>
                                            @endif
                                        </span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="lg:col-span-1">
                <div class="sticky top-24 rounded-2xl border border-hv-border bg-hv-surface p-6">
                    <h3 class="font-bold text-hv-text">Paket Özellikleri</h3>
                    @if($product->specs)
                        <dl class="mt-4 space-y-2 text-sm">
                            @foreach($product->specs as $k => $v)
                                <div class="flex justify-between gap-2 border-b border-hv-border pb-2">
                                    <dt class="text-hv-muted">{{ $k }}</dt>
                                    <dd class="font-semibold text-hv-text">{{ $v }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                    @if($product->features)
                        <ul class="mt-4 space-y-1 text-sm text-hv-muted">
                            @foreach($product->features as $f)
                                <li>✓ {{ $f }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <div class="mt-6 flex flex-col gap-2">
                        <a href="{{ route('hosting.configure.domain') }}" class="text-center text-sm font-semibold text-hv-muted">← Alan adına dön</a>
                        <button type="submit" class="btn-primary w-full py-3">Devam Et</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection
