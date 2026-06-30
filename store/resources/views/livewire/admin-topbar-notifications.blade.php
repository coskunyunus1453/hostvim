<div
    class="hv-notif"
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
            <span class="hv-notif__badge">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
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
        class="hv-notif__panel"
    >
        <div class="hv-notif__header">
            <div>
                <p class="hv-notif__h-title">Bildirimler</p>
                <p class="hv-notif__h-sub">
                    @if ($unreadCount > 0)
                        {{ $unreadCount }} okunmamış
                    @else
                        Tümü okundu
                    @endif
                </p>
            </div>
            @if ($unreadCount > 0)
                <button type="button" wire:click="markAllAsRead" class="hv-notif__markall">
                    Tümünü okundu işaretle
                </button>
            @endif
        </div>

        <div class="hv-notif__list">
            @forelse ($notifications as $notification)
                @php
                    $iconVariant = in_array($notification->color, ['primary', 'success', 'warning', 'danger', 'info'], true)
                        ? $notification->color
                        : 'gray';
                @endphp
                <button
                    type="button"
                    wire:click="openNotification({{ $notification->id }})"
                    wire:key="admin-notification-{{ $notification->id }}"
                    @class([
                        'hv-notif__item',
                        'hv-notif__item--unread' => $notification->isUnread(),
                    ])
                >
                    <span class="hv-notif__icon hv-notif__icon--{{ $iconVariant }}">
                        <x-filament::icon :icon="$notification->icon ?: 'heroicon-o-bell'" class="h-5 w-5" />
                    </span>

                    <span class="hv-notif__body">
                        <span class="hv-notif__row">
                            <span class="hv-notif__name">{{ $notification->title }}</span>
                            @if ($notification->isUnread())
                                <span class="hv-notif__dot"></span>
                            @endif
                        </span>
                        @if ($notification->body)
                            <span class="hv-notif__desc">{{ $notification->body }}</span>
                        @endif
                        <span class="hv-notif__time">{{ $notification->created_at->diffForHumans() }}</span>
                    </span>
                </button>
            @empty
                <div class="hv-notif__empty">
                    <x-filament::icon icon="heroicon-o-bell-slash" class="h-8 w-8" />
                    <p>Henüz bildirim yok.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
