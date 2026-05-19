<x-admin.layout title="Panel sürümü — {{ $release->version }}">
    <form method="POST" action="{{ route('admin.panel-releases.update', $release) }}" class="max-w-3xl space-y-4">
        @csrf
        @method('PUT')
        @include('admin.panel-releases._form', ['release' => $release])
        <div class="flex flex-wrap gap-3">
            <button type="submit" class="admin-btn-emerald">Kaydet</button>
            @if (! $release->is_published)
                <button type="submit" formaction="{{ route('admin.panel-releases.publish', $release) }}" formmethod="POST" class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-500">
                    Yayınla
                </button>
            @endif
            <a href="{{ route('admin.panel-releases.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm dark:border-slate-700">Listeye dön</a>
        </div>
    </form>
</x-admin.layout>
