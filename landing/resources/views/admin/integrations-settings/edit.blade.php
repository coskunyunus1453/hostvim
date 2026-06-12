<x-admin.layout title="Panel entegrasyonlari">
    <form method="POST" action="{{ route('admin.integrations-settings.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="text-base font-semibold">Google Drive yedekleme (OAuth)</h2>
            <p class="mt-1 text-xs text-slate-500">
                Tum panel kurulumlari bu OAuth istemcisini kullanir. Her panel sunucusunda <code class="rounded bg-slate-100 px-1 dark:bg-slate-800">.env</code> yazmaya gerek yok;
                gecerli lisans ve <strong>backups_pro</strong> modulu acik olan paneller bu bilgiyi otomatik alir.
            </p>

            <label class="mt-4 flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-3 text-sm dark:border-slate-700">
                <input type="checkbox" name="google_drive_enabled" value="1" class="mr-1" @checked(old('google_drive_enabled', $googleDriveEnabled))>
                Google Drive entegrasyonu aktif
            </label>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium">OAuth Client ID</label>
                    <input type="text" name="google_drive_client_id" value="{{ old('google_drive_client_id', $googleDriveClientId) }}"
                        autocomplete="off"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-sm dark:border-slate-700 dark:bg-slate-900"
                        placeholder="123456789-abc.apps.googleusercontent.com">
                </div>
                <div>
                    <label class="block text-sm font-medium">OAuth Client Secret</label>
                    <input type="password" name="google_drive_client_secret" value=""
                        autocomplete="new-password"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-sm dark:border-slate-700 dark:bg-slate-900"
                        placeholder="{{ $googleDriveSecretConfigured ? $googleDriveSecretMask.' (degistirmek icin yazin)' : 'GOCSPX-...' }}">
                    @if ($googleDriveSecretConfigured)
                        <p class="mt-1 text-xs text-slate-500">Bos birakirsaniz mevcut secret korunur.</p>
                    @endif
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-amber-200 bg-amber-50/80 p-5 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100">
            <h3 class="font-semibold">Google Cloud kurulumu (bir kez)</h3>
            <ol class="mt-3 list-decimal space-y-2 pl-5 text-xs leading-relaxed">
                <li>
                    <a href="https://console.cloud.google.com/apis/library/drive.googleapis.com" target="_blank" rel="noopener" class="underline">Drive API</a> etkinlestirin.
                </li>
                <li>
                    <a href="https://console.cloud.google.com/apis/credentials/consent" target="_blank" rel="noopener" class="underline">OAuth izin ekrani</a> yapilandirin.
                </li>
                <li>
                    <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener" class="underline">OAuth istemci</a> olusturun (Web uygulamasi).
                </li>
                <li>
                    <strong>Yetkili yonlendirme URI</strong> (tum paneller icin yalnizca bu — Google Console):
                    <code class="mt-1 block rounded-lg border border-amber-300/60 bg-white/60 px-2 py-1.5 font-mono text-[11px] dark:border-amber-800 dark:bg-slate-900">
                        {{ rtrim(config('app.url'), '/') }}/backups/google-callback
                    </code>
                    OAuth panelze.com uzerinden doner; musteri paneline otomatik iletilir. Ayrica OAuth izin ekraninda <strong>Test kullanicilari</strong> listesine baglanacak Gmail adreslerini ekleyin (uygulama «Testing» modundaysa).
                </li>
                <li>Client ID ve Secret degerlerini yukariya kaydedin.</li>
                <li>Panel sunucularinda musteriler <em>Google Drive bagla</em> ile kendi Google hesaplarini baglar.</li>
            </ol>
        </section>

        <div class="flex justify-end">
            <button type="submit" class="rounded-xl bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-orange-500">
                Kaydet
            </button>
        </div>
    </form>
</x-admin.layout>
