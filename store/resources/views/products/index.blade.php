@extends('layouts.app')

@section('content')
<section class="bg-gradient-to-b from-hv-surface to-hv-bg py-16">
    <div class="mx-auto max-w-7xl px-4 text-center lg:px-8">
        <h1 class="section-title text-4xl">Hosting & Sunucu Paketleri</h1>
        <p class="mx-auto mt-4 max-w-2xl text-lg text-hv-muted">İhtiyacınıza uygun paketi seçin. Şeffaf fiyatlandırma, anında kurulum ve 7/24 destek.</p>
    </div>
</section>

@foreach($categories as $category)
<section class="{{ $loop->even ? 'bg-hv-bg' : 'bg-hv-surface' }} py-16">
    <div class="mx-auto max-w-7xl px-4 lg:px-8">
        <h2 class="text-2xl font-bold text-hv-text">{{ $category->name }}</h2>
        @if($category->description)<p class="mt-2 text-hv-muted">{{ $category->description }}</p>@endif
        <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse($category->activeProducts as $product)
                @include('partials.pricing-card', ['product' => $product, 'category' => $category])
            @empty
                <p class="text-hv-muted">Bu kategoride henüz paket yok.</p>
            @endforelse
        </div>
    </div>
</section>
@endforeach
@endsection
