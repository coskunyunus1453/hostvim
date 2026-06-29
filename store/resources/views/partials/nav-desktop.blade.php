        <nav class="hidden items-center gap-5 xl:gap-6 lg:flex" aria-label="Ana menü">
            @if($headerMenu)
                @foreach($headerMenu->activeRootItems as $item)
                    @include('partials.nav-menu-item', ['item' => $item])
                @endforeach
            @endif

            @if($isCustomerLoggedIn ?? false)
                <a href="{{ $accountUrl ?? route('account.dashboard') }}" class="nav-link {{ request()->routeIs('account.*') ? 'nav-link-active' : '' }}">Hesabım</a>
            @endif
        </nav>
