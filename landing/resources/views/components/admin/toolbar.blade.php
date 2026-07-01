@props([
    'description' => null,
])

<div {{ $attributes->class(['admin-toolbar']) }}>
    @if ($description || isset($actions))
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            @if ($description)
                <p class="admin-muted max-w-2xl">{{ $description }}</p>
            @else
                <div class="hidden sm:block sm:flex-1"></div>
            @endif
            @isset($actions)
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif
    {{ $slot }}
</div>
