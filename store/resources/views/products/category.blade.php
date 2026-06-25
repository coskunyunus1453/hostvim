@extends('layouts.app')

@section('content')
<section class="bg-gradient-to-b from-orange-50/50 to-white py-16">
    <div class="mx-auto max-w-7xl px-4 lg:px-8">
        <nav class="text-sm text-stone-500"><a href="{{ route('products.index') }}" class="hover:text-[#C2410C]">Ürünler</a> / <span class="text-stone-800">{{ $category->name }}</span></nav>
        <h1 class="mt-4 text-4xl font-extrabold text-stone-900">{{ $category->name }}</h1>
        @if($category->description)<p class="mt-4 max-w-2xl text-lg text-stone-600">{{ $category->description }}</p>@endif
    </div>
</section>

<section class="py-16">
    <div class="mx-auto max-w-7xl px-4 lg:px-8">
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse($category->activeProducts as $product)
                @include('partials.pricing-card', ['product' => $product, 'category' => $category])
            @empty
                <p class="col-span-full text-center text-stone-500">Paketler yakında eklenecek.</p>
            @endforelse
        </div>
    </div>
</section>
@endsection
