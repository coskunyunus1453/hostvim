@php
    $footerStyle = $themeFooterStyle ?? 'default';
    $footerClass = 'border-t border-hv-border hv-footer-' . $footerStyle;
    $legalLinks = [
        'mesafeli-satis-sozlesmesi' => 'Mesafeli Satış Sözleşmesi',
        'iade-iptal-politikasi' => 'İade & İptal',
        'kvkk' => 'KVKK',
        'gizlilik' => 'Gizlilik',
        'kullanim-sartlari' => 'Kullanım Şartları',
        'cerez-politikasi' => 'Çerez Politikası',
    ];
@endphp
<footer class="{{ $footerClass }}">
    @if($footerStyle === 'minimal')
        <div class="mx-auto max-w-7xl px-4 py-8 lg:px-8">
            <div class="flex flex-col items-center justify-between gap-4 text-center text-sm text-hv-muted md:flex-row">
                <div class="flex items-center gap-2">
                    @include('partials.site-logo', ['height' => $siteLogoFooterHeight ?? 32, 'nameClass' => 'text-sm font-bold'])
                </div>
                <p>&copy; {{ date('Y') }} {{ $siteName }}. Tüm hakları saklıdır.</p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="{{ route('domain.index') }}" class="hover:text-hv-primary">Domain</a>
                    @if($panelLoginUrl && $panelLoginUrl !== '/login')
                        <a href="{{ $panelLoginUrl }}" class="hover:text-hv-primary" target="_blank" rel="noopener noreferrer">Müşteri Paneli</a>
                    @endif
                    <a href="{{ route('blog.index') }}" class="hover:text-hv-primary">Blog</a>
                    <a href="{{ route('contact.index') }}" class="hover:text-hv-primary">İletişim</a>
                    @if($footerMenu)
                        @foreach($footerMenu->activeItems as $item)
                            <a href="{{ $item->href }}" class="hover:text-hv-primary" target="{{ $item->safe_target }}">{{ $item->label }}</a>
                        @endforeach
                    @endif
                </div>
            </div>
            <div class="mt-4 flex flex-wrap justify-center gap-x-4 gap-y-1 border-t border-hv-border pt-4 text-xs text-hv-muted">
                @foreach($legalLinks as $slug => $label)
                    <a href="{{ route('pages.show', $slug) }}" class="hover:text-hv-primary">{{ $label }}</a>
                @endforeach
            </div>
        </div>
    @else
        <div class="mx-auto max-w-7xl px-4 py-16 lg:px-8">
            <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-5">
                <div>
                    <div class="flex items-center gap-2">
                        @include('partials.site-logo', ['height' => $siteLogoFooterHeight ?? 32, 'nameClass' => 'text-lg font-bold'])
                    </div>
                    <p class="mt-4 text-sm leading-relaxed text-hv-muted">
                        {{ $siteSettings['footer_text'] ?? 'Türkiye\'nin güvenilir hosting, VPS, VDS ve sunucu çözüm ortağı. 7/24 teknik destek, yüksek performans altyapısı.' }}
                    </p>
                </div>

                <div>
                    <h4 class="font-semibold text-hv-text">Hizmetler</h4>
                    <ul class="mt-4 space-y-2">
                        @foreach($navCategories->take(5) as $cat)
                            <li><a href="{{ route('products.category', $cat->slug) }}" class="text-sm text-hv-muted hover:text-hv-primary">{{ $cat->name }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold text-hv-text">Destek</h4>
                    <ul class="mt-4 space-y-2 text-sm text-hv-muted">
                        @if($panelLoginUrl && $panelLoginUrl !== '/login')
                            <li><a href="{{ $panelLoginUrl }}" class="hover:text-hv-primary" target="_blank" rel="noopener noreferrer">Müşteri Paneli</a></li>
                        @endif
                        <li><a href="{{ route('pages.show', 'sss') }}" class="hover:text-hv-primary">SSS</a></li>
                        <li><a href="{{ route('contact.index') }}" class="hover:text-hv-primary">İletişim & Destek</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold text-hv-text">Kurumsal</h4>
                    <ul class="mt-4 space-y-2 text-sm text-hv-muted">
                        <li><a href="{{ route('domain.index') }}" class="hover:text-hv-primary">Domain Sorgula</a></li>
                        <li><a href="{{ route('blog.index') }}" class="hover:text-hv-primary">Blog</a></li>
                        <li><a href="{{ route('contact.index') }}" class="hover:text-hv-primary">İletişim</a></li>
                        @if($footerMenu)
                            @foreach($footerMenu->activeItems as $item)
                                <li><a href="{{ $item->href }}" class="hover:text-hv-primary" target="{{ $item->safe_target }}" @if($item->safe_target === '_blank') rel="noopener noreferrer" @endif>{{ $item->label }}</a></li>
                            @endforeach
                        @endif
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold text-hv-text">İletişim</h4>
                    <ul class="mt-4 space-y-2 text-sm text-hv-muted">
                        @if($phone = $siteSettings['contact_phone'] ?? null)
                            <li>{{ $phone }}</li>
                        @endif
                        @if($email = $siteSettings['contact_email'] ?? null)
                            <li><a href="mailto:{{ $email }}" class="hover:text-hv-primary">{{ $email }}</a></li>
                        @endif
                        @if($address = $siteSettings['contact_address'] ?? null)
                            <li>{{ $address }}</li>
                        @endif
                    </ul>
                </div>
            </div>

            <div class="mt-12 flex flex-wrap justify-center gap-x-5 gap-y-2 border-t border-hv-border pt-8 text-sm text-hv-muted md:justify-start">
                @foreach($legalLinks as $slug => $label)
                    <a href="{{ route('pages.show', $slug) }}" class="hover:text-hv-primary">{{ $label }}</a>
                @endforeach
            </div>
            <div class="mt-6 flex flex-col items-center justify-between gap-4 text-sm text-hv-muted md:flex-row">
                <p>&copy; {{ date('Y') }} {{ $siteName }}. Tüm hakları saklıdır.</p>
                @if($themeFooterShowStats ?? true)
                    <div class="flex gap-4">
                        <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-hv-secondary"></span> 7/24 Destek</span>
                        <span>%99.9 Uptime SLA</span>
                    </div>
                @endif
            </div>
        </div>
    @endif
</footer>
