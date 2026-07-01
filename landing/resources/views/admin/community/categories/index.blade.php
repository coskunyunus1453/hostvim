<x-admin.layout title="Topluluk kategorileri">
    <x-admin.toolbar description="Herkese açık forumda kullanılacak kategoriler. Slug URL'de /community/c/slug olarak kullanılır.">
        <x-slot:actions>
            <a href="{{ route('admin.community.categories.create') }}" class="admin-btn-emerald">Yeni kategori</a>
        </x-slot:actions>
    </x-admin.toolbar>

    <div class="admin-table-wrap">
        <table class="min-w-full text-left text-sm">
            <thead class="admin-table-head">
                <tr>
                    <th class="px-4 py-3">Sıra</th>
                    <th class="px-4 py-3">Ad</th>
                    <th class="px-4 py-3">Slug</th>
                    <th class="px-4 py-3">Aktif</th>
                    <th class="px-4 py-3 text-right">İşlem</th>
                </tr>
            </thead>
            <tbody class="admin-table-body">
                @foreach ($categories as $cat)
                    <tr class="admin-table-row">
                        <td class="px-4 py-2">{{ $cat->sort_order }}</td>
                        <td class="admin-td-strong px-4 py-2">{{ $cat->name }}</td>
                        <td class="px-4 py-2 font-mono text-xs">{{ $cat->slug }}</td>
                        <td class="px-4 py-2">{{ $cat->is_active ? 'Evet' : 'Hayır' }}</td>
                        <td class="px-4 py-2 text-right text-xs">
                            <a href="{{ route('admin.community.categories.edit', $cat) }}" class="admin-link-emerald">Düzenle</a>
                            <form action="{{ route('admin.community.categories.destroy', $cat) }}" method="post" class="inline" onsubmit="return confirm('Silinsin mi?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ml-3 text-rose-600 hover:underline dark:text-rose-400">Sil</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-admin.layout>
