@php
    $locale = app()->getLocale();
    $isTr = $locale === 'tr';
    $pageTitle = landing_t('license.success_title');
    $canonical = landing_url_with_lang(route('license.success', absolute: true), $locale);
    $ref = request('ref', '');
@endphp

<x-site.layout
    :title="$pageTitle"
    :description="landing_t('license.success_meta')"
    :canonical-url="$canonical"
>
    <div
        class="hv-container max-w-xl py-16"
        x-data="hvLicenseSuccess({
            ref: @js($ref),
            ordersBase: '{{ url('/api/v1/licensing/orders') }}',
            isTr: @js($isTr),
        })"
    >
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-50">{{ $pageTitle }}</h1>
        <p class="mt-4 text-slate-600 dark:text-slate-400">
            {{ landing_t('license.success_lead') }}
        </p>
        <p class="mt-3 text-sm text-slate-500 dark:text-slate-500">
            {{ landing_t('license.order_ref_label') }}:
            <code class="rounded bg-slate-100 px-1 font-mono text-xs dark:bg-slate-800" x-text="ref || '—'"></code>
        </p>

        {{-- Polling state --}}
        <div x-show="status === 'pending'" x-cloak class="mt-8 flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-900/50 dark:text-slate-300">
            <svg class="h-5 w-5 animate-spin text-[rgb(var(--hv-brand-600)/1)]" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"></path></svg>
            <span>{{ $isTr ? 'Ödeme onayı bekleniyor, lisans anahtarınız hazırlanıyor…' : 'Waiting for payment confirmation, preparing your license key…' }}</span>
        </div>

        {{-- License key revealed --}}
        <div x-show="licenseKey" x-cloak class="mt-8 rounded-2xl border border-emerald-200 bg-emerald-50 p-6 dark:border-emerald-900/40 dark:bg-emerald-950/40">
            <h2 class="text-lg font-semibold text-emerald-900 dark:text-emerald-100">{{ $isTr ? 'Lisans Anahtarınız' : 'Your License Key' }}</h2>
            <p class="mt-1 text-sm text-emerald-800 dark:text-emerald-200">
                {{ $isTr ? 'Anahtarı kopyalayıp panelinizde Admin → Lisans ekranına yapıştırın. Bir kopyası e-postanıza da gönderildi.' : 'Copy the key and paste it into Admin → License in your panel. A copy was also emailed to you.' }}
            </p>
            <div class="mt-4 flex items-stretch gap-2">
                <code class="flex-1 break-all rounded-lg bg-white px-3 py-2.5 font-mono text-xs text-emerald-900 dark:bg-slate-950/60 dark:text-emerald-100" x-text="licenseKey"></code>
                <button type="button" @click="copy()" class="shrink-0 rounded-lg bg-emerald-600 px-3 text-sm font-semibold text-white hover:opacity-95" x-text="copied ? ('{{ $isTr ? 'Kopyalandı' : 'Copied' }}') : ('{{ $isTr ? 'Kopyala' : 'Copy' }}')"></button>
            </div>
        </div>

        {{-- Email reveal fallback (no stored email / direct visit) --}}
        <div x-show="needEmail" x-cloak class="mt-8 rounded-2xl border border-slate-200 bg-white/80 p-6 dark:border-slate-800 dark:bg-slate-900/50">
            <p class="text-sm text-slate-600 dark:text-slate-300">
                {{ $isTr ? 'Lisans anahtarını görüntülemek için sipariş e-postanızı girin:' : 'Enter your order email to view the license key:' }}
            </p>
            <div class="mt-3 flex gap-2">
                <input type="email" x-model="email" placeholder="{{ $isTr ? 'e-posta' : 'email' }}"
                    class="flex-1 rounded-xl border border-slate-300/90 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-[rgb(var(--hv-brand-500)/1)] dark:border-slate-700 dark:bg-slate-950/60 dark:text-slate-100">
                <button type="button" @click="lookup()" class="shrink-0 rounded-xl bg-[rgb(var(--hv-brand-600)/1)] px-4 text-sm font-semibold text-white hover:opacity-95">{{ $isTr ? 'Göster' : 'Show' }}</button>
            </div>
            <p x-show="error" x-cloak class="mt-2 text-xs text-rose-600 dark:text-rose-400" x-text="error"></p>
        </div>

        <p class="mt-8">
            <a href="{{ route('site.pricing') }}" class="hv-link-quiet font-semibold">{{ landing_t('license.back_pricing') }}</a>
        </p>
    </div>

    <script>
        function hvLicenseSuccess(config) {
            return {
                ref: config.ref || '',
                ordersBase: config.ordersBase,
                isTr: config.isTr,
                email: '',
                licenseKey: '',
                status: '',
                needEmail: false,
                error: '',
                copied: false,
                pollsLeft: 12,
                init() {
                    let stored = null;
                    try { stored = JSON.parse(localStorage.getItem('hv-license-order') || 'null'); } catch (e) {}
                    if (stored && stored.ref) {
                        if (!this.ref) this.ref = stored.ref;
                        if (stored.email) this.email = stored.email;
                    }
                    if (this.ref && this.email) {
                        this.status = 'pending';
                        this.poll();
                    } else if (this.ref) {
                        this.needEmail = true;
                    }
                },
                async fetchStatus() {
                    const url = this.ordersBase + '/' + encodeURIComponent(this.ref) + '?email=' + encodeURIComponent(this.email);
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    return { ok: res.ok, data: await res.json().catch(() => ({})) };
                },
                async poll() {
                    if (this.pollsLeft-- <= 0) { this.status = 'timeout'; return; }
                    try {
                        const { ok, data } = await this.fetchStatus();
                        if (ok && data.license_key) {
                            this.licenseKey = data.license_key;
                            this.status = 'completed';
                            this.needEmail = false;
                            try { localStorage.removeItem('hv-license-order'); } catch (e) {}
                            return;
                        }
                    } catch (e) {}
                    setTimeout(() => this.poll(), 5000);
                },
                async lookup() {
                    this.error = '';
                    if (!this.email || !/^\S+@\S+\.\S+$/.test(this.email)) {
                        this.error = this.isTr ? 'Geçerli bir e-posta girin.' : 'Please enter a valid email.';
                        return;
                    }
                    try {
                        const { ok, data } = await this.fetchStatus();
                        if (ok && data.license_key) {
                            this.licenseKey = data.license_key;
                            this.needEmail = false;
                            return;
                        }
                        if (ok && data.status && data.status !== 'completed') {
                            this.needEmail = false;
                            this.status = 'pending';
                            this.pollsLeft = 12;
                            this.poll();
                            return;
                        }
                        this.error = this.isTr ? 'Bu e-posta ile sipariş bulunamadı.' : 'No order found for this email.';
                    } catch (e) {
                        this.error = this.isTr ? 'Bağlantı hatası.' : 'Network error.';
                    }
                },
                copy() {
                    navigator.clipboard?.writeText(this.licenseKey).then(() => {
                        this.copied = true;
                        setTimeout(() => { this.copied = false; }, 2000);
                    });
                },
            };
        }
    </script>
</x-site.layout>
