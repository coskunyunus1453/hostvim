@props(['context' => 'default'])

@php
    $captcha = app(\App\Services\Security\CaptchaService::class);
    $active = $captcha->enabledFor($context);
    $provider = $active ? $captcha->provider() : null;
    $native = ($active && $provider === 'native') ? $captcha->newNativeChallenge() : null;
    $hpField = \App\Services\Security\CaptchaService::HONEYPOT_FIELD;
@endphp

@if ($active)
    {{-- Honeypot: insanlar gormez, botlar doldurur --}}
    <div aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;height:0;width:0;overflow:hidden" tabindex="-1">
        <label>Bu alani bos birakin
            <input type="text" name="{{ $hpField }}" value="" tabindex="-1" autocomplete="off">
        </label>
    </div>

    <div class="hv-captcha">
        @if ($provider === 'native')
            <label for="captcha_answer" class="auth-label">Güvenlik doğrulaması</label>
            <div class="mt-1 flex items-center gap-3">
                <span class="inline-flex select-none items-center rounded-lg border border-hv-border bg-stone-50 px-3 py-2 text-sm font-semibold tracking-wide text-hv-text">
                    {{ $native['a'] }} + {{ $native['b'] }} = ?
                </span>
                <input
                    id="captcha_answer"
                    type="text"
                    name="captcha_answer"
                    inputmode="numeric"
                    autocomplete="off"
                    required
                    class="auth-input w-24"
                    placeholder="Sonuç"
                >
            </div>
            <p class="mt-1 text-xs text-hv-muted">Lütfen yukarıdaki toplamın sonucunu yazın.</p>
        @elseif ($provider === 'turnstile')
            <div class="cf-turnstile" data-sitekey="{{ $captcha->siteKey() }}" data-theme="auto"></div>
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @elseif ($provider === 'recaptcha')
            <div class="g-recaptcha" data-sitekey="{{ $captcha->siteKey() }}"></div>
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        @endif

        @error('captcha')
            <p class="auth-error mt-2">{{ $message }}</p>
        @enderror
    </div>
@endif
