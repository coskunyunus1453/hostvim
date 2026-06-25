<div class="me-2 flex shrink-0 items-center gap-1">
    <livewire:admin-topbar-notifications />
    <a
        href="{{ url('/') }}"
        target="_blank"
        rel="noopener noreferrer"
        title="Siteyi gör"
        class="fi-icon-btn relative flex items-center justify-center rounded-lg outline-none transition duration-75 focus-visible:ring-2 fi-color-gray fi-icon-btn-size-md"
    >
        <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="h-5 w-5" />
    </a>
    <button
        type="button"
        wire:click="clearCache"
        wire:loading.attr="disabled"
        title="Önbelleği temizle"
        class="fi-icon-btn relative flex items-center justify-center rounded-lg outline-none transition duration-75 focus-visible:ring-2 fi-color-gray fi-icon-btn-size-md"
    >
        <x-filament::icon
            icon="heroicon-o-arrow-path"
            class="h-5 w-5"
            wire:loading.class="animate-spin"
            wire:target="clearCache"
        />
    </button>
</div>
