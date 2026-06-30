<div class="fi-topbar-custom-actions hv-topbar shrink-0">
    <div class="hv-topbar__group">
        <livewire:admin-topbar-notifications />

        <a
            href="{{ url('/') }}"
            target="_blank"
            rel="noopener noreferrer"
            title="Siteyi gör"
            class="fi-icon-btn rounded-lg outline-none transition duration-75 focus-visible:ring-2 fi-color-gray fi-icon-btn-size-md"
            style="display:inline-flex;align-items:center;justify-content:center;position:relative"
        >
            <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="h-5 w-5" />
        </a>

        <button
            type="button"
            wire:click="clearCache"
            wire:loading.attr="disabled"
            title="Önbelleği temizle"
            class="fi-icon-btn rounded-lg outline-none transition duration-75 focus-visible:ring-2 fi-color-gray fi-icon-btn-size-md"
            style="display:inline-flex;align-items:center;justify-content:center;position:relative"
        >
            <x-filament::icon
                icon="heroicon-o-arrow-path"
                class="h-5 w-5"
                wire:loading.class="animate-spin"
                wire:target="clearCache"
            />
        </button>
    </div>

    <span aria-hidden="true" class="hv-topbar__divider"></span>
</div>
