<x-admin.layout title="Topluluk konuları">
    <x-admin.toolbar description="Forum konularını durum ve moderasyon filtresiyle arayın." />

    <form method="get" class="admin-toolbar flex flex-wrap gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Ara…" class="admin-field min-w-[200px] w-auto" />
        <select name="status" class="admin-field w-auto">
            <option value="">Tüm durumlar</option>
            <option value="published" @selected(request('status') === 'published')>Yayında</option>
            <option value="hidden" @selected(request('status') === 'hidden')>Gizli</option>
        </select>
        <select name="moderation" class="admin-field w-auto">
            <option value="">Tüm moderasyon</option>
            <option value="approved" @selected(request('moderation') === 'approved')>Onaylı</option>
            <option value="pending" @selected(request('moderation') === 'pending')>Bekleyen</option>
            <option value="rejected" @selected(request('moderation') === 'rejected')>Reddedilen</option>
        </select>
        <button type="submit" class="admin-btn-primary">Filtrele</button>
    </form>

    <div class="admin-table-wrap">
        <table class="min-w-full text-left text-sm">
            <thead class="admin-table-head">
                <tr>
                    <th class="px-4 py-3">Başlık</th>
                    <th class="px-4 py-3">Kategori</th>
                    <th class="px-4 py-3">Açan üye</th>
                    <th class="px-4 py-3">Durum</th>
                    <th class="px-4 py-3">Mod.</th>
                    <th class="px-4 py-3 text-right">İşlem</th>
                </tr>
            </thead>
            <tbody class="admin-table-body">
                @foreach ($topics as $topic)
                    <tr class="admin-table-row">
                        <td class="admin-td-strong px-4 py-2">{{ $topic->title }}</td>
                        <td class="px-4 py-2">{{ $topic->category?->name }}</td>
                        <td class="px-4 py-2">
                            @if ($topic->author)
                                <div class="font-medium text-slate-800 dark:text-slate-200">{{ $topic->author->name }}</div>
                                <div class="font-mono text-xs text-slate-500">{{ $topic->author->email }}</div>
                                @if (! $topic->author->is_admin)
                                    <a href="{{ route('admin.community.members.edit', $topic->author) }}" class="admin-link-emerald text-xs">Üye kartı</a>
                                @else
                                    <span class="text-xs text-slate-400">Yönetici</span>
                                @endif
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 font-mono text-xs">{{ $topic->status }}</td>
                        <td class="px-4 py-2 font-mono text-xs">{{ $topic->moderation_status ?? '—' }}</td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('admin.community.topics.edit', $topic) }}" class="admin-link-emerald">Moderasyon</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $topics->links() }}</div>
</x-admin.layout>
