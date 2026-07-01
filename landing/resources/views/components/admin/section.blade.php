@props([
    'title' => null,
    'description' => null,
    'variant' => 'default',
])

@php
    $variantClass = match ($variant) {
        'info' => 'admin-section admin-section--info',
        'warning' => 'admin-section admin-section--warning',
        default => 'admin-section',
    };
@endphp

<section {{ $attributes->class([$variantClass]) }}>
    @if ($title || $description)
        <div class="admin-section__head">
            @if ($title)
                <h2 class="admin-section__title">{{ $title }}</h2>
            @endif
            @if ($description)
                <p class="admin-section__desc">{{ $description }}</p>
            @endif
        </div>
    @endif
    <div class="admin-section__body">
        {{ $slot }}
    </div>
</section>
