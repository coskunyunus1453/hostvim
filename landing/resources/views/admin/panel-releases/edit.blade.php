<x-admin.layout title="Panel sürümü — {{ $release->version }}">
    <form method="POST" action="{{ route('admin.panel-releases.update', $release) }}" class="admin-form">
        @csrf
        @method('PUT')
        @include('admin.panel-releases._form', ['release' => $release])
        <div class="flex flex-wrap gap-3">
            <button type="submit" class="admin-btn-emerald">Kaydet</button>
            <a href="{{ route('admin.panel-releases.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm dark:border-slate-700">Listeye dön</a>
        </div>
    </form>
    @if (! $release->is_published)
        <form method="POST" action="{{ route('admin.panel-releases.publish', $release) }}" class="mt-3">
            @csrf
            <button type="submit" class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-500">
                Yayınla (kaydedilmiş taslak)
            </button>
            <p class="mt-1 text-xs text-slate-500">Önce «Kaydet» ile değişiklikleri kaydedin; ardından yayınlayın.</p>
        </form>
    @endif
</x-admin.layout>
