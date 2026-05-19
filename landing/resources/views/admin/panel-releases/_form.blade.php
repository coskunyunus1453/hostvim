@php
    $release = $release ?? null;
@endphp
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium">Sürüm * <span class="text-slate-500">(semver, örn. 0.2.0)</span></label>
        <input type="text" name="version" value="{{ old('version', $release?->version) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-sm dark:border-slate-700 dark:bg-slate-900">
    </div>
    <div>
        <label class="block text-sm font-medium">Git etiketi</label>
        <input type="text" name="git_tag" value="{{ old('git_tag', $release?->git_tag) }}" placeholder="v0.2.0" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-sm dark:border-slate-700 dark:bg-slate-900">
    </div>
</div>
<div>
    <label class="block text-sm font-medium">Başlık *</label>
    <input type="text" name="title" value="{{ old('title', $release?->title) }}" required maxlength="255" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 dark:border-slate-700 dark:bg-slate-900">
</div>
<div class="grid gap-4 sm:grid-cols-3">
    <div>
        <label class="block text-sm font-medium">Kanal</label>
        <select name="channel" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 dark:border-slate-700 dark:bg-slate-900">
            @foreach (['stable' => 'Stable', 'beta' => 'Beta'] as $val => $label)
                <option value="{{ $val }}" @selected(old('channel', $release?->channel ?? 'stable') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium">Hedef profil</label>
        <select name="profile" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 dark:border-slate-700 dark:bg-slate-900">
            @foreach (['all' => 'Tümü', 'customer' => 'Community', 'pro' => 'Pro'] as $val => $label)
                <option value="{{ $val }}" @selected(old('profile', $release?->profile ?? 'all') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium">Min. mevcut sürüm</label>
        <input type="text" name="min_panel_version" value="{{ old('min_panel_version', $release?->min_panel_version) }}" placeholder="0.1.0" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-sm dark:border-slate-700 dark:bg-slate-900">
    </div>
</div>
<div>
    <label class="block text-sm font-medium">Yenilikler / değişiklikler *</label>
    <p class="text-xs text-slate-500 mb-1">Müşteri panelinde güncelleme öncesi gösterilir.</p>
    <textarea name="changelog" rows="12" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">{{ old('changelog', $release?->changelog) }}</textarea>
</div>
<div>
    <label class="block text-sm font-medium">Artifact URL (önerilen)</label>
    <input type="url" name="artifact_url" value="{{ old('artifact_url', $release?->artifact_url) }}" placeholder="https://.../hostvim-customer-....tar.gz" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-xs dark:border-slate-700 dark:bg-slate-900">
</div>
<div>
    <label class="block text-sm font-medium">Artifact SHA256</label>
    <input type="text" name="artifact_sha256" value="{{ old('artifact_sha256', $release?->artifact_sha256) }}" maxlength="64" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-xs dark:border-slate-700 dark:bg-slate-900">
</div>
<p class="text-xs text-slate-500">GitHub Release veya <code class="text-xs">build-profile-artifact.sh customer</code> çıktısını HTTPS ile barındırın. Git etiketi yedek yol olarak kullanılabilir.</p>
<div class="flex flex-wrap items-center gap-4">
    <label class="flex items-center gap-2 text-sm">
        <input type="hidden" name="requires_engine_restart" value="0">
        <input type="checkbox" name="requires_engine_restart" value="1" @checked(old('requires_engine_restart', $release?->requires_engine_restart ?? true)) class="rounded border-slate-300">
        Engine yeniden başlatılsın
    </label>
    @if ($release)
        <label class="flex items-center gap-2 text-sm">
            <input type="hidden" name="is_published" value="0">
            <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $release->is_published)) class="rounded border-slate-300">
            Yayında
        </label>
        <div>
            <label class="block text-xs text-slate-500">Yayın tarihi</label>
            <input type="datetime-local" name="published_at" value="{{ old('published_at', optional($release->published_at)->format('Y-m-d\TH:i')) }}" class="mt-1 rounded-xl border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
        </div>
    @endif
</div>
