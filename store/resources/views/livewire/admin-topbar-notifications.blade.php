<div
    class="me-1"
    style="position:relative;display:inline-flex;align-items:center"
    x-data="{ open: @entangle('open') }"
    @keydown.escape.window="open = false"
    wire:poll.60s
>
    <button
        type="button"
        @click="open = !open"
        title="Bildirimler"
        class="fi-icon-btn rounded-lg outline-none transition duration-75 focus-visible:ring-2 fi-color-gray fi-icon-btn-size-md"
        style="display:inline-flex;align-items:center;justify-content:center;position:relative"
        aria-label="Bildirimler"
        :aria-expanded="open"
    >
        <x-filament::icon icon="heroicon-o-bell" class="h-5 w-5" />
        @if ($unreadCount > 0)
            <span class="absolute -end-0.5 -top-0.5 inline-flex min-h-4 min-w-4 items-center justify-center rounded-full bg-danger-600 px-1 text-[10px] font-bold leading-none text-white ring-2 ring-white dark:ring-gray-900">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.outside="open = false"
        class="absolute end-0 z-50 mt-2 w-[22rem] max-w-[calc(100vw-1.5rem)] overflow-hidden rounded-xl bg-white shadow-lg ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10"
    >
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-white/10">
            <div>
                <p class="text-sm font-semibold text-gray-950 dark:text-white">Bildirimler</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    @if ($unreadCount > 0)
                        {{ $unreadCount }} okunmamış
                    @else
                        Tümü okundu
                    @endif
                </p>
            </div>
            @if ($unreadCount > 0)
                <button
                    type="button"
                    wire:click="markAllAsRead"
                    class="text-xs font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
                >
                    Tümünü okundu işaretle
                </button>
            @endif
        </div>

        <div class="max-h-[28rem] overflow-y-auto">
            @forelse ($notifications as $notification)
                <button
                    type="button"
                    wire:click="openNotification({{ $notification->id }})"
                    wire:key="admin-notification-{{ $notification->id }}"
                    @class([
                        'flex w-full gap-3 border-b border-gray-100 px-4 py-3 text-start transition hover:bg-gray-50 dark:border-white/5 dark:hover:bg-white/5',
                        'bg-primary-50/60 dark:bg-primary-400/5' => $notification->isUnread(),
                    ])
                >
                    <span @class([
                        'mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg',
                        'bg-primary-100 text-primary-700 dark:bg-primary-400/15 dark:text-primary-300' => $notification->color === 'primary',
                        'bg-success-100 text-success-700 dark:bg-success-400/15 dark:text-success-300' => $notification->color === 'success',
                        'bg-warning-100 text-warning-700 dark:bg-warning-400/15 dark:text-warning-300' => $notification->color === 'warning',
                        'bg-danger-100 text-danger-700 dark:bg-danger-400/15 dark:text-danger-300' => $notification->color === 'danger',
                        'bg-info-100 text-info-700 dark:bg-info-400/15 dark:text-info-300' => $notification->color === 'info',
                        'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300' => ! in_array($notification->color, ['primary', 'success', 'warning', 'danger', 'info'], true),
                    ])>
                        <x-filament::icon :icon="$notification->icon ?: 'heroicon-o-bell'" class="h-5 w-5" />
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="flex items-start justify-between gap-2">
                            <span class="text-sm font-medium text-gray-950 dark:text-white">{{ $notification->title }}</span>
                            @if ($notification->isUnread())
                                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-primary-600 dark:bg-primary-400"></span>
                            @endif
                        </span>
                        @if ($notification->body)
                            <span class="mt-0.5 block text-xs leading-relaxed text-gray-600 dark:text-gray-300">
                                {{ $notification->body }}
                            </span>
                        @endif
                        <span class="mt-1 block text-[11px] text-gray-400 dark:text-gray-500">
                            {{ $notification->created_at->diffForHumans() }}
                        </span>
                    </span>
                </button>
            @empty
                <div class="px-4 py-10 text-center">
                    <x-filament::icon icon="heroicon-o-bell-slash" class="mx-auto h-8 w-8 text-gray-300 dark:text-gray-600" />
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Henüz bildirim yok.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
