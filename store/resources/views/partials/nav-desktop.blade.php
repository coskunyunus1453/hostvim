        <nav class="hidden items-center gap-5 xl:gap-6 lg:flex" aria-label="Ana menü">
            <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'nav-link-active' : '' }}">Ana Sayfa</a>

            @include('partials.nav-services-mega')

            <a href="{{ route('domain.index') }}" class="nav-link {{ request()->routeIs('domain.*') ? 'nav-link-active' : '' }}">Domain</a>
            <a href="{{ route('blog.index') }}" class="nav-link {{ request()->routeIs('blog.*') ? 'nav-link-active' : '' }}">Blog</a>
            <a href="{{ route('pages.show', 'hakkimizda') }}" class="nav-link {{ request()->is('sayfa/hakkimizda') ? 'nav-link-active' : '' }}">Hakkımızda</a>
            <a href="{{ route('contact.index') }}" class="nav-link {{ request()->routeIs('contact.*') ? 'nav-link-active' : '' }}">İletişim</a>

            @if($isCustomerLoggedIn ?? false)
                <a href="{{ $accountUrl ?? route('account.dashboard') }}" class="nav-link {{ request()->routeIs('account.*') ? 'nav-link-active' : '' }}">Hesabım</a>
            @endif

            @if($headerMenu)
                @foreach($headerMenu->activeRootItems as $item)
                    @include('partials.nav-menu-item', ['item' => $item])
                @endforeach
            @endif
        </nav>
