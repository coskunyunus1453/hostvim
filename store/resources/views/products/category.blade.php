@extends('layouts.app')

@section('content')
<section class="bg-gradient-to-b from-hv-surface to-hv-bg py-16">
    <div class="mx-auto max-w-7xl px-4 lg:px-8">
        <h1 class="mt-4 text-4xl font-extrabold text-hv-text">{{ $category->name }}</h1>
        @if($category->description)<p class="mt-4 max-w-2xl text-lg text-hv-muted">{{ $category->description }}</p>@endif
    </div>
</section>

<section class="py-16">
    <div class="mx-auto max-w-7xl px-4 lg:px-8">
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse($category->activeProducts as $product)
                @include('partials.pricing-card', ['product' => $product, 'category' => $category])
            @empty
                <div class="col-span-full mx-auto max-w-xl rounded-2xl border border-hv-border bg-hv-elevated p-10 text-center">
                    @if(in_array($category->slug, ['vds', 'dedicated'], true))
                        <p class="text-lg font-bold text-hv-text">{{ $category->name }} — özel teklif</p>
                        <p class="mt-3 text-sm leading-relaxed text-hv-muted">
                            VDS ve dedicated sunucular projenize göre yapılandırılır. Online paket listesi yerine size özel kaynak ve fiyat teklifi hazırlıyoruz.
                        </p>
                        <a href="{{ url('/iletisim') }}?konu={{ $category->slug }}" class="mt-6 inline-flex rounded-xl bg-hv-primary px-6 py-3 text-sm font-bold text-white transition hover:opacity-90">
                            Teklif Al
                        </a>
                    @else
                        <p class="text-hv-muted">Paketler yakında eklenecek.</p>
                        <a href="{{ url('/iletisim') }}" class="mt-4 inline-flex text-sm font-semibold text-hv-primary hover:underline">Bilgi alın →</a>
                    @endif
                </div>
            @endforelse
        </div>
    </div>
</section>
@endsection
