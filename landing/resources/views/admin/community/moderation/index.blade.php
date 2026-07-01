<x-admin.layout title="Topluluk moderasyon kuyruğu">
    <x-admin.toolbar description="Yeni konu ve yanıtlar moderasyon ayarına veya gölge yasaklı üyelere göre burada birikir. Onaylanan konularla bekleyen yanıtlar da otomatik onaylanır." />

    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-50">Bekleyen konular ({{ $pendingTopics->count() }})</h2>
    <div class="admin-table-wrap mt-3">
        @if ($pendingTopics->isEmpty())
            <p class="px-4 py-8 text-center text-sm text-slate-500">Bekleyen konu yok.</p>
        @else
            <table class="min-w-full text-left text-sm">
                <thead class="admin-table-head">
                    <tr>
                        <th class="px-4 py-3">Başlık</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3">Üye</th>
                        <th class="sr-only">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="admin-table-body">
                    @foreach ($pendingTopics as $t)
                        <tr class="admin-table-row">
                            <td class="admin-td-strong px-4 py-2">{{ \Illuminate\Support\Str::limit($t->title, 72) }}</td>
                            <td class="px-4 py-2">{{ $t->category?->name }}</td>
                            <td class="px-4 py-2 text-xs">{{ $t->author?->email }}</td>
                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                <form method="post" action="{{ route('admin.community.moderation.topics.approve', $t) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="admin-link-emerald mr-2">Onayla</button>
                                </form>
                                <form method="post" action="{{ route('admin.community.moderation.topics.reject', $t) }}" class="inline" onsubmit="return confirm('Konu reddedilsin mi?');">
                                    @csrf
                                    <button type="submit" class="text-rose-600 dark:text-rose-400">Reddet</button>
                                </form>
                                <a href="{{ route('admin.community.topics.edit', $t) }}" class="ml-2 text-xs text-slate-500 hover:underline">Düzenle</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <h2 class="mt-10 text-lg font-semibold text-slate-900 dark:text-slate-50">Bekleyen yanıtlar ({{ $pendingPosts->count() }})</h2>
    <div class="admin-table-wrap mt-3">
        @if ($pendingPosts->isEmpty())
            <p class="px-4 py-8 text-center text-sm text-slate-500">Bekleyen yanıt yok.</p>
        @else
            <table class="min-w-full text-left text-sm">
                <thead class="admin-table-head">
                    <tr>
                        <th class="px-4 py-3">Konu</th>
                        <th class="px-4 py-3">Üye</th>
                        <th class="px-4 py-3">Önizleme</th>
                        <th class="sr-only">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="admin-table-body">
                    @foreach ($pendingPosts as $p)
                        <tr class="admin-table-row">
                            <td class="px-4 py-2">
                                <a href="{{ route('admin.community.topics.edit', $p->topic) }}" class="admin-link-emerald font-medium">{{ \Illuminate\Support\Str::limit($p->topic?->title ?? '—', 48) }}</a>
                            </td>
                            <td class="px-4 py-2 text-xs">{{ $p->author?->email }}</td>
                            <td class="max-w-md px-4 py-2 text-xs text-slate-600 dark:text-slate-400">{{ \Illuminate\Support\Str::limit(strip_tags((string) $p->body), 120) }}</td>
                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                <form method="post" action="{{ route('admin.community.moderation.posts.approve', $p) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="admin-link-emerald mr-2">Onayla</button>
                                </form>
                                <form method="post" action="{{ route('admin.community.moderation.posts.reject', $p) }}" class="inline" onsubmit="return confirm('Yanıt reddedilsin mi?');">
                                    @csrf
                                    <button type="submit" class="text-rose-600 dark:text-rose-400">Reddet</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-admin.layout>
