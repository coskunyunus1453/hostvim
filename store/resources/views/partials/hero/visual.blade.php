@php
    $visualClass = $class ?? '';
@endphp

<div class="hv-hero-visual {{ $visualClass }}">
    @if($hero?->image)
        <div class="hv-hero-visual-image hv-hero-slide-in mb-4 overflow-hidden rounded-2xl border border-hv-border shadow-lg">
            <img src="{{ asset('storage/' . $hero->image) }}" alt="" class="aspect-video w-full object-cover">
        </div>
    @endif

    @include('partials.hero.illustration', ['class' => $illustrationClass ?? 'max-w-xl'])
</div>
