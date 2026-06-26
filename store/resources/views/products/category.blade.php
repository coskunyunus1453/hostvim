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
                <p class="col-span-full text-center text-hv-muted">Paketler yakında eklenecek.</p>
            @endforelse
        </div>
    </div>
</section>
@endsection
