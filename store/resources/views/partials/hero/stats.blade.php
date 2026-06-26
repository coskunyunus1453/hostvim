@php
    $stats = array_filter([
        ['value' => $hero?->stat_1_value, 'label' => $hero?->stat_1_label],
        ['value' => $hero?->stat_2_value, 'label' => $hero?->stat_2_label],
        ['value' => $hero?->stat_3_value, 'label' => $hero?->stat_3_label],
    ], fn ($s) => filled($s['value']));
@endphp

@if($stats !== [])
    <div class="mt-10 flex flex-wrap gap-8 text-sm text-hv-muted">
        @foreach($stats as $i => $stat)
            <div class="hv-hero-stat animate-fade-up" style="animation-delay: {{ 0.15 + $i * 0.1 }}s">
                <span class="block text-2xl font-bold {{ $i % 2 === 0 ? 'text-hv-secondary' : 'text-hv-primary' }}">{{ $stat['value'] }}</span>
                {{ $stat['label'] }}
            </div>
        @endforeach
    </div>
@endif
