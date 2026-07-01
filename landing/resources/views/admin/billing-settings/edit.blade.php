<x-admin.layout title="Ödeme yöntemleri">
    <x-admin.toolbar description="PayTR, Stripe ve havale/EFT seçeneklerini tek ekrandan yönetin. Ön yüzdeki ödeme akışı bu ayarlara göre çalışır." />

    <form method="POST" action="{{ route('admin.billing-settings.update') }}" class="admin-form admin-form--wide space-y-6">
        @csrf
        @method('PUT')

        <x-admin.section title="Genel yönetim" description="Ödeme sağlayıcılarını açıp kapatın ve varsayılan yönlendirmeyi belirleyin.">
            <div class="grid gap-3 sm:grid-cols-3">
                <label class="admin-radio-tile text-sm">
                    <input type="checkbox" name="paytr_enabled" value="1" class="admin-checkbox" @checked(old('paytr_enabled', $paytrEnabled))>
                    PayTR aktif
                </label>
                <label class="admin-radio-tile text-sm">
                    <input type="checkbox" name="stripe_enabled" value="1" class="admin-checkbox" @checked(old('stripe_enabled', $stripeEnabled))>
                    Stripe aktif
                </label>
                <label class="admin-radio-tile text-sm">
                    <input type="checkbox" name="bank_transfer_enabled" value="1" class="admin-checkbox" @checked(old('bank_transfer_enabled', $bankTransferEnabled))>
                    Havale / EFT aktif
                </label>
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="admin-label-block">Varsayılan sağlayıcı</label>
                    <select name="default_provider" class="admin-field mt-1">
                        @foreach (['auto' => 'Otomatik', 'paytr' => 'PayTR', 'stripe' => 'Stripe', 'bank_transfer' => 'Havale / EFT'] as $key => $label)
                            <option value="{{ $key }}" @selected(old('default_provider', $defaultProvider) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="admin-label-block">Sağlayıcıyı zorla</label>
                    <select name="force_provider" class="admin-field mt-1">
                        <option value="">Yok</option>
                        @foreach (['paytr' => 'PayTR', 'stripe' => 'Stripe', 'bank_transfer' => 'Havale / EFT'] as $key => $label)
                            <option value="{{ $key }}" @selected(old('force_provider', $forceProvider) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="admin-label-block">PayTR locale listesi</label>
                    <input type="text" name="tr_locales" value="{{ old('tr_locales', $trLocales) }}" placeholder="tr,tr-TR" class="admin-field mt-1">
                </div>
            </div>
        </x-admin.section>

        <x-admin.section title="Satış para birimi ve kurlar" description="Ön yüz gösterimi ve havale talimatı bu para birimine göre hesaplanır. PayTR her zaman TRY kullanır.">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="admin-label-block">Satış / havale para birimi</label>
                    <select name="sales_display_currency" class="admin-field mt-1">
                        @foreach (['TRY' => 'TRY (TL)', 'USD' => 'USD', 'EUR' => 'EUR'] as $key => $label)
                            <option value="{{ $key }}" @selected(old('sales_display_currency', $salesDisplayCurrency) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="admin-label-block">Stripe Checkout para birimi</label>
                    <select name="stripe_checkout_currency" class="admin-field mt-1">
                        <option value="usd" @selected(old('stripe_checkout_currency', $stripeCheckoutCurrency) === 'usd')>USD</option>
                        <option value="eur" @selected(old('stripe_checkout_currency', $stripeCheckoutCurrency) === 'eur')>EUR</option>
                    </select>
                </div>
                <div>
                    <label class="admin-label-block">1 USD = ? TRY</label>
                    <input type="text" inputmode="decimal" name="fx_try_per_usd" value="{{ old('fx_try_per_usd', $fxTryPerUsd) }}" class="admin-field mt-1 font-mono" required>
                </div>
                <div>
                    <label class="admin-label-block">1 EUR = ? TRY</label>
                    <input type="text" inputmode="decimal" name="fx_try_per_eur" value="{{ old('fx_try_per_eur', $fxTryPerEur) }}" class="admin-field mt-1 font-mono" required>
                </div>
            </div>
        </x-admin.section>

        <x-admin.section title="PayTR ayarları">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="admin-label-block">Merchant ID</label>
                    <input type="text" name="paytr_merchant_id" value="{{ old('paytr_merchant_id', $paytrMerchantId) }}" class="admin-field mt-1">
                </div>
                <div>
                    <label class="admin-label-block">Merchant Key</label>
                    <input type="text" name="paytr_merchant_key" value="{{ old('paytr_merchant_key', $paytrMerchantKey) }}" class="admin-field mt-1">
                </div>
                <div>
                    <label class="admin-label-block">Merchant Salt</label>
                    <input type="text" name="paytr_merchant_salt" value="{{ old('paytr_merchant_salt', $paytrMerchantSalt) }}" class="admin-field mt-1">
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="admin-label-block">Test modu</label>
                        <select name="paytr_test_mode" class="admin-field mt-1">
                            <option value="0" @selected(old('paytr_test_mode', $paytrTestMode) === '0')>Kapalı</option>
                            <option value="1" @selected(old('paytr_test_mode', $paytrTestMode) === '1')>Açık</option>
                        </select>
                    </div>
                    <div>
                        <label class="admin-label-block">Debug</label>
                        <select name="paytr_debug_on" class="admin-field mt-1">
                            <option value="0" @selected(old('paytr_debug_on', $paytrDebugOn) === '0')>Kapalı</option>
                            <option value="1" @selected(old('paytr_debug_on', $paytrDebugOn) === '1')>Açık</option>
                        </select>
                    </div>
                    <div>
                        <label class="admin-label-block">Timeout (dk)</label>
                        <input type="number" min="1" max="120" name="paytr_timeout_minutes" value="{{ old('paytr_timeout_minutes', $paytrTimeoutMinutes) }}" class="admin-field mt-1">
                    </div>
                </div>
            </div>
        </x-admin.section>

        <x-admin.section title="Stripe ayarları">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="admin-label-block">Secret key</label>
                    <input type="text" name="stripe_secret" value="{{ old('stripe_secret', $stripeSecret) }}" class="admin-field mt-1 font-mono">
                </div>
                <div>
                    <label class="admin-label-block">Webhook secret</label>
                    <input type="text" name="stripe_webhook_secret" value="{{ old('stripe_webhook_secret', $stripeWebhookSecret) }}" class="admin-field mt-1 font-mono">
                </div>
            </div>
        </x-admin.section>

        <x-admin.section title="Havale / EFT ayarları">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="admin-label-block">Hesap sahibi</label>
                    <input type="text" name="bank_account_name" value="{{ old('bank_account_name', $bankAccountName) }}" class="admin-field mt-1">
                </div>
                <div>
                    <label class="admin-label-block">Banka adı</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name', $bankName) }}" class="admin-field mt-1">
                </div>
                <div>
                    <label class="admin-label-block">Şube</label>
                    <input type="text" name="bank_branch" value="{{ old('bank_branch', $bankBranch) }}" class="admin-field mt-1">
                </div>
                <div>
                    <label class="admin-label-block">IBAN</label>
                    <input type="text" name="bank_iban" value="{{ old('bank_iban', $bankIban) }}" class="admin-field mt-1 font-mono">
                </div>
            </div>
            <div>
                <label class="admin-label-block">Ödeme talimatı</label>
                <textarea name="bank_instructions" rows="4" class="admin-field mt-1">{{ old('bank_instructions', $bankInstructions) }}</textarea>
            </div>
        </x-admin.section>

        <button type="submit" class="admin-btn-primary-lg">Kaydet</button>
    </form>
</x-admin.layout>
