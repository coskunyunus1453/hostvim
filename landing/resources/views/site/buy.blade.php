@php
    $isTr = app()->getLocale() === 'tr';
    $pageTitle = $isTr ? 'Lisans Satın Al' : 'Buy a License';
    $meta = $isTr
        ? 'Panelze Pro lisansını anında satın alın; ödeme sonrası lisans anahtarınız e-posta ile teslim edilir.'
        : 'Buy your Panelze Pro license instantly; your license key is delivered by email after payment.';
@endphp

<x-site.layout :title="$pageTitle" :description="$meta" :canonical-url="$seoCanonical ?? null">
    <div
        class="hv-container"
        x-data="hvCheckout({
            products: @js($products),
            bankEnabled: @js($bankEnabled),
            displayCurrency: @js($displayCurrency),
            endpoint: '{{ url('/api/v1/licensing/checkout') }}',
            isTr: @js($isTr),
        })"
    >
        <div class="mb-10 max-w-3xl">
            <div class="hv-section-eyebrow mb-4">{{ $isTr ? 'Satın Al' : 'Checkout' }}</div>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl dark:text-slate-50">
                {{ $isTr ? 'Lisansınızı saniyeler içinde alın' : 'Get your license in seconds' }}
            </h1>
            <p class="mt-3 text-lg leading-relaxed text-slate-600 dark:text-slate-400">
                {{ $isTr
                    ? 'Bir plan seçin, e-postanızı girin ve ödemeyi tamamlayın. Lisans anahtarınız ödeme onaylanır onaylanmaz e-posta ile gönderilir.'
                    : 'Pick a plan, enter your email and complete payment. Your license key is emailed the moment your payment is confirmed.' }}
            </p>
        </div>

        @if ($products->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300/90 bg-slate-50/80 px-8 py-12 text-center text-base text-slate-500 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-500">
                {{ $isTr ? 'Şu anda satışta ürün bulunmuyor.' : 'No products are available for purchase right now.' }}
            </div>
        @else
            {{-- Plan selection --}}
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($products as $product)
                    <button
                        type="button"
                        @click="select(@js($product['code']))"
                        :class="selected === @js($product['code'])
                            ? 'ring-2 ring-[rgb(var(--hv-brand-500)/1)] border-[rgb(var(--hv-brand-500)/0.6)]'
                            : 'border-slate-200/90 dark:border-slate-800'"
                        class="relative flex flex-col rounded-2xl border bg-white/90 p-6 text-left transition dark:bg-slate-900/60"
                    >
                        <span
                            x-show="selected === @js($product['code'])"
                            class="absolute right-4 top-4 inline-flex h-6 w-6 items-center justify-center rounded-full bg-[rgb(var(--hv-brand-600)/1)] text-white"
                        >
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <h2 class="pr-8 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $product['name'] }}</h2>
                        @if ($product['description'])
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $product['description'] }}</p>
                        @endif
                        <div class="mt-4 flex items-baseline gap-1">
                            <span class="text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ $product['price_label'] }}</span>
                            @if ($product['recurring'])
                                <span class="text-sm text-slate-500 dark:text-slate-500">
                                    @if ($product['interval'] === 'year')
                                        {{ $isTr ? '/ yıl' : '/ year' }}
                                    @else
                                        {{ $isTr ? '/ ay' : '/ month' }}
                                    @endif
                                </span>
                            @endif
                        </div>
                        @if ($product['max_sites'])
                            <p class="mt-4 text-sm text-slate-600 dark:text-slate-300">
                                {{ $isTr ? 'En fazla' : 'Up to' }} {{ $product['max_sites'] }} {{ $isTr ? 'site' : 'sites' }}
                            </p>
                        @endif
                    </button>
                @endforeach
            </div>

            {{-- Checkout form --}}
            <div class="mt-10 max-w-xl rounded-2xl border border-slate-200/90 bg-white/90 p-6 dark:border-slate-800 dark:bg-slate-900/60">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                    {{ $isTr ? 'Sipariş Bilgileri' : 'Order Details' }}
                </h3>

                <div class="mt-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ $isTr ? 'E-posta' : 'Email' }} *</label>
                        <input type="email" x-model="email" autocomplete="email"
                            class="mt-1 w-full rounded-xl border border-slate-300/90 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-[rgb(var(--hv-brand-500)/1)] dark:border-slate-700 dark:bg-slate-950/60 dark:text-slate-100"
                            placeholder="{{ $isTr ? 'lisans@ornek.com' : 'license@example.com' }}">
                        <p class="mt-1 text-xs text-slate-500">{{ $isTr ? 'Lisans anahtarı bu adrese gönderilecek.' : 'Your license key will be sent here.' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ $isTr ? 'Ad Soyad / Firma' : 'Name / Company' }}</label>
                        <input type="text" x-model="name" autocomplete="name"
                            class="mt-1 w-full rounded-xl border border-slate-300/90 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-[rgb(var(--hv-brand-500)/1)] dark:border-slate-700 dark:bg-slate-950/60 dark:text-slate-100">
                    </div>

                    @if ($bankEnabled)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ $isTr ? 'Ödeme Yöntemi' : 'Payment Method' }}</label>
                            <select x-model="billing"
                                class="mt-1 w-full rounded-xl border border-slate-300/90 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-[rgb(var(--hv-brand-500)/1)] dark:border-slate-700 dark:bg-slate-950/60 dark:text-slate-100">
                                <option value="auto">{{ $isTr ? 'Kredi / Banka Kartı' : 'Credit / Debit Card' }}</option>
                                <option value="bank_transfer">{{ $isTr ? 'Banka Havalesi / EFT' : 'Bank Transfer' }}</option>
                            </select>
                        </div>
                    @endif
                </div>

                <div x-show="error" x-cloak class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-200" x-text="error"></div>

                <button type="button" @click="submit()" :disabled="loading || !selected"
                    class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[rgb(var(--hv-brand-600)/1)] px-5 py-3 text-base font-semibold text-white transition hover:opacity-95 disabled:cursor-not-allowed disabled:opacity-50">
                    <svg x-show="loading" x-cloak class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"></path></svg>
                    <span x-show="!loading" x-text="selected ? buttonLabel() : '{{ $isTr ? 'Önce bir plan seçin' : 'Select a plan first' }}'"></span>
                    <span x-show="loading" x-cloak>{{ $isTr ? 'Yönlendiriliyor…' : 'Redirecting…' }}</span>
                </button>

                <p class="mt-3 text-center text-xs text-slate-500 dark:text-slate-500">
                    {{ $isTr ? 'Ödeme güvenli sağlayıcı üzerinden alınır. Devam ederek' : 'Payment is processed by a secure provider. By continuing you accept the' }}
                    <a href="{{ route('site.page', ['slug' => 'kullanim-kosullari']) }}" class="hv-link-quiet font-semibold">{{ $isTr ? 'kullanım koşullarını' : 'terms of service' }}</a>
                    {{ $isTr ? 'kabul etmiş olursunuz.' : '.' }}
                </p>
            </div>

            {{-- Bank transfer result --}}
            <div x-show="bank" x-cloak class="mt-6 max-w-xl rounded-2xl border border-emerald-200 bg-emerald-50 p-6 dark:border-emerald-900/40 dark:bg-emerald-950/40">
                <h3 class="text-lg font-semibold text-emerald-900 dark:text-emerald-100">{{ $isTr ? 'Havale Bilgileri' : 'Bank Transfer Details' }}</h3>
                <p class="mt-1 text-sm text-emerald-800 dark:text-emerald-200">
                    {{ $isTr ? 'Aşağıdaki hesaba ödeme yapın. Açıklama alanına sipariş numaranızı yazın.' : 'Please transfer to the account below. Add your order reference to the description.' }}
                </p>
                <dl class="mt-4 space-y-1.5 text-sm text-emerald-900 dark:text-emerald-100">
                    <div class="flex justify-between gap-4"><dt class="opacity-70">{{ $isTr ? 'Sipariş No' : 'Order Ref' }}</dt><dd class="font-mono" x-text="bankRef"></dd></div>
                    <template x-if="bank && bank.account_name"><div class="flex justify-between gap-4"><dt class="opacity-70">{{ $isTr ? 'Hesap Adı' : 'Account' }}</dt><dd x-text="bank.account_name"></dd></div></template>
                    <template x-if="bank && bank.bank_name"><div class="flex justify-between gap-4"><dt class="opacity-70">{{ $isTr ? 'Banka' : 'Bank' }}</dt><dd x-text="bank.bank_name"></dd></div></template>
                    <template x-if="bank && bank.iban"><div class="flex justify-between gap-4"><dt class="opacity-70">IBAN</dt><dd class="font-mono" x-text="bank.iban"></dd></div></template>
                </dl>
                <p x-show="bank && bank.instructions" class="mt-3 text-xs text-emerald-800 dark:text-emerald-200" x-text="bank ? bank.instructions : ''"></p>
            </div>
        @endif

        <p class="mt-12 text-center text-sm text-slate-500 dark:text-slate-500">
            <a href="{{ route('site.pricing') }}" class="hv-link-quiet font-semibold">{{ $isTr ? 'Plan karşılaştırmasına dön' : 'Back to plan comparison' }}</a>
        </p>
    </div>

    <script>
        function hvCheckout(config) {
            return {
                products: config.products || [],
                bankEnabled: config.bankEnabled,
                displayCurrency: config.displayCurrency,
                endpoint: config.endpoint,
                isTr: config.isTr,
                selected: null,
                email: '',
                name: '',
                billing: 'auto',
                loading: false,
                error: '',
                bank: null,
                bankRef: '',
                init() {
                    if (this.products.length === 1) {
                        this.selected = this.products[0].code;
                    }
                    const p = new URLSearchParams(window.location.search).get('plan');
                    if (p && this.products.some(x => x.code === p)) {
                        this.selected = p;
                    }
                },
                select(code) {
                    this.selected = code;
                    this.error = '';
                },
                current() {
                    return this.products.find(x => x.code === this.selected) || null;
                },
                buttonLabel() {
                    const c = this.current();
                    const pay = this.isTr ? 'Öde' : 'Pay';
                    return c ? (pay + ' · ' + c.price_label) : pay;
                },
                async submit() {
                    this.error = '';
                    this.bank = null;
                    if (!this.selected) {
                        this.error = this.isTr ? 'Lütfen bir plan seçin.' : 'Please select a plan.';
                        return;
                    }
                    if (!this.email || !/^\S+@\S+\.\S+$/.test(this.email)) {
                        this.error = this.isTr ? 'Geçerli bir e-posta girin.' : 'Please enter a valid email.';
                        return;
                    }
                    this.loading = true;
                    try {
                        const res = await fetch(this.endpoint, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({
                                product_code: this.selected,
                                email: this.email,
                                name: this.name || null,
                                billing: this.billing,
                            }),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            this.error = data.message || (this.isTr ? 'Ödeme başlatılamadı.' : 'Could not start checkout.');
                            this.loading = false;
                            return;
                        }
                        // Remember order so the success page can reveal the key.
                        if (data.order_ref) {
                            try {
                                localStorage.setItem('hv-license-order', JSON.stringify({ ref: data.order_ref, email: this.email }));
                            } catch (e) {}
                        }
                        if (data.checkout_url) { window.location = data.checkout_url; return; }
                        if (data.iframe_url) { window.location = data.iframe_url; return; }
                        if (data.provider === 'bank_transfer') {
                            this.bank = data.bank || {};
                            this.bankRef = data.order_ref || '';
                            this.loading = false;
                            return;
                        }
                        this.error = this.isTr ? 'Beklenmeyen yanıt alındı.' : 'Unexpected response.';
                        this.loading = false;
                    } catch (e) {
                        this.error = this.isTr ? 'Bağlantı hatası. Lütfen tekrar deneyin.' : 'Network error. Please try again.';
                        this.loading = false;
                    }
                },
            };
        }
    </script>
</x-site.layout>
