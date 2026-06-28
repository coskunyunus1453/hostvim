@props(['step' => 1])

@php
$steps = [
    1 => ['label' => 'Alan Adı', 'route' => 'hosting.configure.domain'],
    2 => ['label' => 'Hizmet Ayarları', 'route' => 'hosting.configure.options'],
    3 => ['label' => 'Sipariş Özeti', 'route' => 'hosting.configure.review'],
    4 => ['label' => 'Ödeme', 'route' => 'checkout.index'],
];
@endphp

<nav class="mb-10" aria-label="Sipariş adımları">
    <ol class="flex flex-wrap items-center gap-2 text-sm font-semibold sm:gap-0">
        @foreach($steps as $num => $info)
            @php
                $active = $num === $step;
                $done = $num < $step;
                $clickable = $done && $num < 4;
            @endphp
            <li class="flex items-center">
                @if($clickable)
                    <a href="{{ route($info['route']) }}" class="flex items-center gap-2 rounded-lg px-2 py-1 text-[#166534] hover:bg-green-50">
                @else
                    <span class="flex items-center gap-2 px-2 py-1 {{ $active ? 'text-hv-primary' : ($done ? 'text-[#166534]' : 'text-hv-muted') }}">
                @endif
                    <span class="flex h-8 w-8 items-center justify-center rounded-full text-xs {{ $active ? 'bg-hv-primary text-white' : ($done ? 'bg-[#166534] text-white' : 'bg-stone-200 text-stone-500') }}">
                        {{ $num }}
                    </span>
                    <span class="hidden sm:inline">{{ $info['label'] }}</span>
                @if($clickable)</a>@else</span>@endif
                @if($num < count($steps))
                    <span class="mx-2 hidden text-hv-muted sm:inline">›</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
