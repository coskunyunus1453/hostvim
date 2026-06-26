@php($embedded = $embedded ?? false)
@php($settings = $installSettings ?? \App\Services\InstallGuide::settings())
@php($sections = \App\Services\InstallGuide::sectionsForLocale(app()->getLocale()))

<div class="mx-auto max-w-4xl space-y-8">
    @if ($embedded)
        <p class="text-sm text-slate-600 dark:text-slate-400">
            Müşteriye gösterilen kurulum URL’lerini düzenleyin. Boş alanlar <code class="text-xs">config/panelze.php</code> varsayılanlarını kullanır.
        </p>
    @else
        <div>
            <h1 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Kurulum komutları</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                Ana sayfa, <a href="{{ route('site.setup') }}" class="text-sky-600 underline" target="_blank" rel="noopener">/setup</a> ve dokümantasyonda gösterilen komutlar buradan üretilir.
                Betikler <code class="rounded bg-slate-200 px-1 text-xs dark:bg-slate-800">deploy/host/</code> ile uyumludur.
            </p>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.install-settings.update') }}" class="admin-card space-y-4">
        @csrf
        @method('PUT')
        @if ($embedded)
            <input type="hidden" name="return_to" value="appearance">
            <input type="hidden" name="tab" value="install">
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="admin-label" for="install_get_url">Tek satır domain (get.panelze.sh)</label>
                <input id="install_get_url" type="url" name="install_get_url" value="{{ old('install_get_url', $settings['get_url']) }}" class="admin-field mt-1" placeholder="https://get.panelze.sh">
            </div>
            <div class="sm:col-span-2">
                <label class="admin-label" for="install_community_script">Community betiği URL</label>
                <input id="install_community_script" type="url" name="install_community_script" value="{{ old('install_community_script', $settings['community_script']) }}" class="admin-field mt-1">
            </div>
            <div class="sm:col-span-2">
                <label class="admin-label" for="install_pro_script">Pro betiği URL</label>
                <input id="install_pro_script" type="url" name="install_pro_script" value="{{ old('install_pro_script', $settings['pro_script']) }}" class="admin-field mt-1">
            </div>
            <div class="sm:col-span-2">
                <label class="admin-label" for="install_remote_url">remote-install.sh URL</label>
                <input id="install_remote_url" type="url" name="install_remote_url" value="{{ old('install_remote_url', $settings['remote_script']) }}" class="admin-field mt-1">
            </div>
            <div class="sm:col-span-2">
                <label class="admin-label" for="install_motor_script">install.sh (motor) URL</label>
                <input id="install_motor_script" type="url" name="install_motor_script" value="{{ old('install_motor_script', $settings['motor_script']) }}" class="admin-field mt-1">
            </div>
            <div class="sm:col-span-2">
                <label class="admin-label" for="install_repo_url">Git repo URL</label>
                <input id="install_repo_url" type="url" name="install_repo_url" value="{{ old('install_repo_url', $settings['repo_url']) }}" class="admin-field mt-1">
            </div>
            <div>
                <label class="admin-label" for="install_repo_branch">Git dalı</label>
                <input id="install_repo_branch" type="text" name="install_repo_branch" value="{{ old('install_repo_branch', $settings['repo_branch']) }}" class="admin-field mt-1" placeholder="main">
            </div>
            <div>
                <label class="admin-label" for="install_home">Kurulum dizini</label>
                <input id="install_home" type="text" name="install_home" value="{{ old('install_home', $settings['install_home']) }}" class="admin-field mt-1" placeholder="/var/www/panelze">
            </div>
            <div class="sm:col-span-2">
                <label class="admin-label" for="install_admin_login_file">İlk admin dosyası</label>
                <input id="install_admin_login_file" type="text" name="install_admin_login_file" value="{{ old('install_admin_login_file', $settings['admin_login_file']) }}" class="admin-field mt-1" placeholder="/root/panelze-admin-login.txt">
            </div>
        </div>

        <div class="flex flex-wrap gap-2 pt-2">
            <button type="submit" class="admin-btn-primary">Kaydet</button>
            <a href="{{ route('site.setup') }}" target="_blank" rel="noopener" class="admin-btn-outline">Kurulum sayfasını aç</a>
        </div>
    </form>

    <div class="admin-card">
        <h2 class="admin-label-block text-base">Canlı önizleme (müşteri metni)</h2>
        <div class="mt-4 space-y-4">
            @foreach ($sections as $section)
                <div>
                    <div class="text-xs font-semibold text-slate-600 dark:text-slate-400">{{ $section['label'] }}</div>
                    <pre class="mt-1 overflow-x-auto rounded-lg bg-slate-950 p-3 text-xs text-emerald-300"><code>{{ $section['command'] }}</code></pre>
                </div>
            @endforeach
        </div>
    </div>

    <div class="admin-card text-sm text-slate-600 dark:text-slate-400">
        <h2 class="font-medium text-slate-900 dark:text-slate-100">CDN / sunucu yayını</h2>
        <ul class="mt-2 list-inside list-disc space-y-1 text-xs">
            <li><code>deploy/get.panelze.sh</code> → DNS <strong>get.panelze.sh</strong> kökünde sunun.</li>
            <li>Community/Pro betiklerini kendi CDN’nize kopyalayın veya GitHub raw URL kullanın.</li>
            <li>Kurulum sonrası: <code>sudo certbot --nginx -d panel.ornek.com</code> ve panel <code>APP_URL</code> HTTPS olmalı.</li>
            <li>Sorun giderme: <code>sudo panelze-post-install</code> · MySQL: <code>sudo panelze-repair-mysql</code></li>
        </ul>
    </div>
</div>
