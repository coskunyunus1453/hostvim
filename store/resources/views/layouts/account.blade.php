@extends('layouts.app')

@section('title', $pageTitle ?? 'Hesabım')

@section('content')
<section class="py-10 md:py-14">
    <div class="mx-auto max-w-7xl px-4 lg:px-8">
        <div class="flex flex-col gap-8 lg:flex-row">
            <aside class="lg:w-64 shrink-0">
                <div class="rounded-2xl border border-hv-border bg-hv-elevated p-4 shadow-sm">
                    <p class="text-sm font-semibold text-hv-text">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-hv-muted truncate">{{ auth()->user()->email }}</p>
                    <nav class="mt-4 space-y-1" aria-label="Hesap menüsü">
                        @foreach([
                            ['route' => 'account.dashboard', 'label' => 'Özet'],
                            ['route' => 'account.profile', 'label' => 'Profil & Adres'],
                            ['route' => 'account.domains', 'label' => 'Alan Adlarım'],
                            ['route' => 'account.hosting', 'label' => 'Hostinglerim'],
                            ['route' => 'account.invoices', 'label' => 'Faturalarım'],
                            ['route' => 'account.orders', 'label' => 'Siparişlerim'],
                            ['route' => 'account.support.index', 'label' => 'Destek Talepleri', 'active' => 'account.support.*'],
                        ] as $item)
                            <a href="{{ route($item['route']) }}"
                               class="block rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs($item['active'] ?? $item['route']) ? 'bg-hv-primary text-white' : 'text-hv-muted hover:bg-hv-surface hover:text-hv-primary' }}">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </nav>
                    <form action="{{ route('logout') }}" method="POST" class="mt-4 border-t border-hv-border pt-4">
                        @csrf
                        <button type="submit" class="btn-ghost w-full justify-start text-sm">Çıkış Yap</button>
                    </form>
                </div>
            </aside>
            <div class="min-w-0 flex-1">
                @if(!empty($pageTitle))
                    <h1 class="section-title mb-6">{{ $pageTitle }}</h1>
                @endif
                @yield('account')
            </div>
        </div>
    </div>
</section>
@endsection
