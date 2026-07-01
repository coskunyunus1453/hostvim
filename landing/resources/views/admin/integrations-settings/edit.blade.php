<x-admin.layout title="Panel entegrasyonları">
    <x-admin.toolbar description="Müşteri panellerinin merkezi olarak kullandığı OAuth ve API entegrasyonlarını buradan yönetin." />

    <form method="POST" action="{{ route('admin.integrations-settings.update') }}" class="admin-form admin-form--wide space-y-6">
        @csrf
        @method('PUT')

        <x-admin.section title="Google Drive yedekleme (OAuth)" description="Geçerli lisans ve backups_pro modülü açık olan paneller bu bilgiyi otomatik alır; müşteri sunucusunda .env gerekmez.">
            <label class="admin-radio-tile text-sm">
                <input type="checkbox" name="google_drive_enabled" value="1" class="admin-checkbox" @checked(old('google_drive_enabled', $googleDriveEnabled))>
                Google Drive entegrasyonu aktif
            </label>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="admin-label-block">OAuth Client ID</label>
                    <input type="text" name="google_drive_client_id" value="{{ old('google_drive_client_id', $googleDriveClientId) }}"
                        autocomplete="off"
                        class="admin-field mt-1 font-mono"
                        placeholder="123456789-abc.apps.googleusercontent.com">
                </div>
                <div>
                    <label class="admin-label-block">OAuth Client Secret</label>
                    <input type="password" name="google_drive_client_secret" value=""
                        autocomplete="new-password"
                        class="admin-field mt-1 font-mono"
                        placeholder="{{ $googleDriveSecretConfigured ? $googleDriveSecretMask.' (değiştirmek için yazın)' : 'GOCSPX-...' }}">
                    @if ($googleDriveSecretConfigured)
                        <p class="admin-muted mt-1 text-xs">Boş bırakırsanız mevcut secret korunur.</p>
                    @endif
                </div>
            </div>
        </x-admin.section>

        <x-admin.section variant="warning" title="Google Cloud kurulumu (bir kez)">
            <ol class="list-decimal space-y-2 pl-5 text-sm leading-relaxed">
                <li>
                    <a href="https://console.cloud.google.com/apis/library/drive.googleapis.com" target="_blank" rel="noopener" class="admin-link">Drive API</a> etkinleştirin.
                </li>
                <li>
                    <a href="https://console.cloud.google.com/apis/credentials/consent" target="_blank" rel="noopener" class="admin-link">OAuth izin ekranı</a> yapılandırın.
                </li>
                <li>
                    <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener" class="admin-link">OAuth istemci</a> oluşturun (Web uygulaması).
                </li>
                <li>
                    <strong>Yetkili yönlendirme URI</strong> (tüm paneller için):
                    <code class="admin-key mt-2 block rounded-lg border border-amber-300/60 bg-white/60 px-3 py-2 text-xs dark:border-amber-800 dark:bg-slate-900">
                        {{ rtrim(config('app.url'), '/') }}/backups/google-callback
                    </code>
                    OAuth panelze.com üzerinden döner. Uygulama «Testing» modundaysa test kullanıcıları listesine Gmail adreslerini ekleyin.
                </li>
                <li>Client ID ve Secret değerlerini yukarıya kaydedin.</li>
                <li>Müşteriler panelde <em>Google Drive bağla</em> ile kendi hesaplarını bağlar.</li>
            </ol>
        </x-admin.section>

        <div class="flex justify-end">
            <button type="submit" class="admin-btn-primary-lg">Kaydet</button>
        </div>
    </form>
</x-admin.layout>
