@php
    $variant = $hero?->layout_variant ?? 'split';
    $variant = in_array($variant, ['split', 'centered', 'aurora'], true) ? $variant : 'split';
@endphp

@include('partials.hero.' . $variant, ['hero' => $hero])
