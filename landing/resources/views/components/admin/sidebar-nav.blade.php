@props([
    'closeOnNavigate' => false,
])

@php
    use App\Support\AdminNavigation;

    $openIds = AdminNavigation::openGroupIds();
    $initialOpen = array_fill_keys(
        array_column(AdminNavigation::groups(), 'id'),
        false
    );
    foreach ($openIds as $id) {
        $initialOpen[$id] = true;
    }
@endphp

<nav class="admin-sidebar-nav flex-1 overflow-y-auto p-2.5"
     x-data="{ open: @js($initialOpen) }"
     aria-label="Yönetim menüsü">
    @foreach (AdminNavigation::topLevel() as $item)
        @php $active = AdminNavigation::isActive($item['active']); @endphp
        <a href="{{ route($item['route']) }}"
           @if ($closeOnNavigate) @click="$dispatch('hv-admin-close-drawer')" @endif
           @class([
               'admin-nav-link admin-nav-link-top mb-1.5 flex items-center gap-2.5 rounded-lg px-3 py-2 font-medium transition-colors',
               'admin-nav-link-active bg-orange-500/15 text-orange-800 ring-1 ring-orange-500/30 dark:text-orange-200' => $active,
               'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-900/80' => ! $active,
           ])>
            <span @class([
                'h-2 w-2 shrink-0 rounded-full',
                'bg-orange-500' => $active,
                'bg-slate-300 dark:bg-slate-600' => ! $active,
            ])></span>
            {{ $item['label'] }}
        </a>
    @endforeach

    @foreach (AdminNavigation::groups() as $group)
        @php $groupActive = AdminNavigation::isGroupActive($group); @endphp
        <div @class([
            'pt-1.5',
            'border-t border-slate-200/80 dark:border-slate-800/80' => ! $loop->first,
        ])>
            <button type="button"
                    @click="open['{{ $group['id'] }}'] = !open['{{ $group['id'] }}']"
                    @class([
                        'flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-2 text-left transition-colors',
                        'text-orange-700 dark:text-orange-300' => $groupActive,
                        'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' => ! $groupActive,
                    ])
                    :aria-expanded="open['{{ $group['id'] }}']">
                <span class="admin-nav-group-label font-semibold uppercase tracking-wide">{{ $group['label'] }}</span>
                <svg xmlns="http://www.w3.org/2000/svg"
                     class="h-4 w-4 shrink-0 transition-transform duration-150"
                     :class="open['{{ $group['id'] }}'] ? 'rotate-90' : ''"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     aria-hidden="true">
                    <path d="M9 6l6 6-6 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <div x-show="open['{{ $group['id'] }}']"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="opacity-0 -translate-y-0.5"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="space-y-0.5 pb-1.5 pl-2">
                @foreach ($group['items'] as $item)
                    @php $active = AdminNavigation::isActive($item['active']); @endphp
                    <a href="{{ route($item['route']) }}"
                       @if ($closeOnNavigate) @click="$dispatch('hv-admin-close-drawer')" @endif
                       @class([
                           'admin-nav-link block rounded-md px-2.5 py-1.5 transition-colors',
                           'admin-nav-link-active bg-orange-500/15 font-medium text-orange-800 ring-1 ring-orange-500/25 dark:text-orange-200' => $active,
                           'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-900/80 dark:hover:text-slate-200' => ! $active,
                       ])>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach
</nav>
