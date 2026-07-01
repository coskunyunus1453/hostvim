@props([
    'width' => 'default',
])

@php
    $widthClass = match ($width) {
        'full' => 'admin-page admin-page--full',
        'hub' => 'admin-page admin-page--hub',
        'form' => 'admin-page admin-page--form',
        'wide' => 'admin-page admin-page--wide',
        'narrow' => 'admin-page admin-page--narrow',
        default => 'admin-page',
    };
@endphp

<div {{ $attributes->class([$widthClass, 'space-y-6']) }}>
    {{ $slot }}
</div>
