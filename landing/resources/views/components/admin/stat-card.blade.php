@props([
    'label',
    'value',
    'hint' => null,
    'href' => null,
    'linkLabel' => 'Yönet →',
    'accent' => 'orange',
])

@php
    $accentClass = match ($accent) {
        'sky' => 'admin-stat-card--sky',
        'violet' => 'admin-stat-card--violet',
        'emerald' => 'admin-stat-card--emerald',
        'indigo' => 'admin-stat-card--indigo',
        'amber' => 'admin-stat-card--amber',
        default => 'admin-stat-card--orange',
    };
@endphp

<div {{ $attributes->class(['admin-stat-card', $accentClass]) }}>
    <div class="admin-stat-card__top">
        <div>
            <p class="admin-stat-card__label">{{ $label }}</p>
            <p class="admin-stat-card__value">{{ $value }}</p>
            @if ($hint)
                <p class="admin-stat-card__hint">{{ $hint }}</p>
            @endif
        </div>
        @if (isset($icon))
            <div class="admin-stat-card__icon">{{ $icon }}</div>
        @endif
    </div>
    @if ($href)
        <a href="{{ $href }}" class="admin-stat-card__link">{{ $linkLabel }}</a>
    @endif
    {{ $slot }}
</div>
