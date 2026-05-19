<x-admin.layout title="Panel sürümleri">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="admin-muted">
            Müşteri sunucularındaki Hostvim paneli bu sürümleri kontrol eder. Yayınlanan sürümler için müşteri yöneticisine bildirim gider; güncelleme müşterinin onayı ile uygulanır.
        </p>
        <a href="{{ route('admin.panel-releases.create') }}" class="admin-btn-emerald">Yeni sürüm</a>
    </div>

    <div class="admin-table-wrap">
        <table class="min-w-full text-left text-sm">
            <thead class="admin-table-head">
                <tr>
                    <th class="px-4 py-3">Sürüm</th>
                    <th class="px-4 py-3">Başlık</th>
                    <th class="px-4 py-3">Kanal</th>
                    <th class="px-4 py-3">Profil</th>
                    <th class="px-4 py-3">Yayın</th>
                    <th class="px-4 py-3">Kaynak</th>
                    <th class="px-4 py-3 text-right">İşlem</th>
                </tr>
            </thead>
            <tbody class="admin-table-body">
                @forelse ($releases as $r)
                    <tr class="admin-table-row">
                        <td class="px-4 py-3 font-mono text-xs font-semibold">{{ $r->version }}</td>
                        <td class="px-4 py-3 admin-td-strong">{{ $r->title }}</td>
                        <td class="px-4 py-3 text-xs">{{ $r->channel }}</td>
                        <td class="px-4 py-3 text-xs">{{ $r->profile }}</td>
                        <td class="px-4 py-3 text-xs">
                            @if ($r->is_published && $r->published_at)
                                <span class="text-emerald-700 dark:text-emerald-400">Yayında</span>
                                <div class="text-slate-500">{{ $r->published_at->format('Y-m-d H:i') }}</div>
                            @else
                                <span class="text-slate-500">Taslak</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-400">
                            @if ($r->artifact_url)
                                <span title="{{ $r->artifact_url }}">artifact</span>
                            @endif
                            @if ($r->git_tag)
                                <span class="font-mono">{{ $r->git_tag }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-xs whitespace-nowrap">
                            <a href="{{ route('admin.panel-releases.edit', $r) }}" class="admin-link-emerald">Düzenle</a>
                            @if (! $r->is_published)
                                <form action="{{ route('admin.panel-releases.publish', $r) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="ml-2 text-sky-600">Yayınla</button>
                                </form>
                                <form action="{{ route('admin.panel-releases.destroy', $r) }}" method="POST" class="inline" onsubmit="return confirm('Silinsin mi?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ml-2 text-rose-600">Sil</button>
                                </form>
                            @else
                                <form action="{{ route('admin.panel-releases.unpublish', $r) }}" method="POST" class="inline" onsubmit="return confirm('Yayından kaldırılsın mı?');">
                                    @csrf
                                    <button type="submit" class="ml-2 text-amber-600">Kaldır</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">Henüz sürüm yok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $releases->links() }}</div>
</x-admin.layout>
