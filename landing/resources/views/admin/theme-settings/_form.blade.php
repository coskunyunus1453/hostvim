@php
    $top = old('theme_neon_top', $neonTop);
    $stackSec = old('theme_neon_stack_section', $neonStackSection);
    $stackRows = old('theme_neon_stack', $neonStackItems);
    $gridSec = old('theme_neon_grid_section', $neonGridSection);
    $gridRows = old('theme_neon_grid', $neonGridItems);
@endphp
@php($embedded = $embedded ?? false)

<div class="mx-auto max-w-3xl space-y-8">
    @if (! $embedded)
        <p class="admin-muted">
            Temaları gerçek kullanım alanına göre ayırdık: genel görünüm ayarları tüm temalara, Neon içerik alanları ise yalnızca Neon tema aktifken uygulanır.
        </p>
    @endif

    <form method="POST" action="{{ route('admin.theme-settings.update') }}" class="space-y-8" id="hv-theme-settings-form">
        @csrf
        @method('PUT')
        @if ($embedded)
            <input type="hidden" name="return_to" value="appearance">
            <input type="hidden" name="tab" value="theme">
        @endif

        <div class="admin-card hv-theme-tabs">
            <input type="radio" name="hv-theme-tab" id="hv-theme-tab-general" class="hv-tab-input" checked>
            <input type="radio" name="hv-theme-tab" id="hv-theme-tab-neon_top" class="hv-tab-input">
            <input type="radio" name="hv-theme-tab" id="hv-theme-tab-neon_features" class="hv-tab-input">

            <nav class="hv-tab-nav flex flex-wrap gap-2" aria-label="Tema alt sekmeleri">
                <label for="hv-theme-tab-general" class="admin-btn-outline hv-tab-label cursor-pointer px-4 py-2 text-xs">Genel tema</label>
                <label for="hv-theme-tab-neon_top" class="admin-btn-outline hv-tab-label cursor-pointer px-4 py-2 text-xs">Neon üst alan</label>
                <label for="hv-theme-tab-neon_features" class="admin-btn-outline hv-tab-label cursor-pointer px-4 py-2 text-xs">Neon özellik blokları</label>
            </nav>

            <div class="hv-tab-panel hv-tab-panel-general mt-6 space-y-8">
                <div>
                    <h2 class="admin-label-block text-base">Aktif tema</h2>
                    <p class="mt-1 text-xs text-slate-600 dark:text-slate-500">Orange ve Turquoise klasik yerleşimi kullanır. Neon, kendi header/footer ve ana sayfa düzenine geçer.</p>
                    <div class="mt-4 space-y-3">
                        @foreach ($themes as $key => $meta)
                            <label class="admin-radio-tile">
                                <input type="radio" name="active_theme" value="{{ $key }}" class="mt-1" @checked($activeTheme === $key)>
                                <div>
                                    <div class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $meta['label'] ?? $key }}</div>
                                    <div class="mt-0.5 text-xs text-slate-600 dark:text-slate-500">{{ $meta['description'] ?? '' }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <h2 class="admin-label-block text-base">Arka plan grafiği</h2>
                    <p class="mt-1 text-xs text-slate-600 dark:text-slate-500">Bu ayar yalnızca Orange/Turquoise temalarda etkilidir; Neon kendi arka plan katmanını kullanır.</p>
                    <select name="graphic_motif" class="admin-field mt-4">
                        @foreach ($motifs as $key => $label)
                            <option value="{{ $key }}" @selected($graphicMotif === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <h2 class="admin-label-block text-base">Ana renk (isteğe bağlı)</h2>
                    <p class="mt-1 text-xs text-slate-600 dark:text-slate-500">Hex değeri tüm temalarda vurgu renklerini günceller. Boş bırakırsanız tema varsayılanları kullanılır.</p>
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <input type="color" id="theme_primary_hex_picker" value="{{ strlen((string) $primaryHex) === 7 ? $primaryHex : '#22d3ee' }}" class="h-10 w-16 cursor-pointer rounded-lg border border-slate-300 bg-white dark:border-slate-600 dark:bg-slate-900">
                        <input type="text" name="theme_primary_hex" id="theme_primary_hex" value="{{ old('theme_primary_hex', $primaryHex) }}" placeholder="#RRGGBB" maxlength="7" pattern="#[0-9A-Fa-f]{6}" class="admin-field min-w-[10rem] flex-1">
                        <button type="button" id="clear_hex" class="admin-btn-outline px-3 py-2 text-xs">Temizle</button>
                    </div>
                </div>
            </div>

            <div class="hv-tab-panel hv-tab-panel-neon_top mt-6 space-y-6">
                <div>
                    <h2 class="admin-label-block text-base">Neon tema — üst tanıtım alanı</h2>
                    <p class="mt-1 text-xs text-slate-600 dark:text-slate-500">Bu içerik yalnızca Neon aktifken ana sayfanın üst bölümünde görünür.</p>
                </div>
                <div>
                    <label class="admin-label" for="neon_badge">Rozet</label>
                    <input id="neon_badge" type="text" name="theme_neon_top[badge]" value="{{ $top['badge'] ?? '' }}" class="admin-field mt-1">
                </div>
                <div>
                    <label class="admin-label" for="neon_title">Ana başlık</label>
                    <input id="neon_title" type="text" name="theme_neon_top[title]" value="{{ $top['title'] ?? '' }}" class="admin-field mt-1">
                </div>
                <div>
                    <label class="admin-label" for="neon_lead">Tanıtım metni</label>
                    <textarea id="neon_lead" name="theme_neon_top[lead]" rows="3" class="admin-field mt-1">{{ $top['lead'] ?? '' }}</textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="admin-label" for="neon_cta1">Birincil düğme</label>
                        <input id="neon_cta1" type="text" name="theme_neon_top[cta_primary]" value="{{ $top['cta_primary'] ?? '' }}" class="admin-field mt-1" placeholder="Panele git">
                    </div>
                    <div>
                        <label class="admin-label" for="neon_cta2">İkincil düğme</label>
                        <input id="neon_cta2" type="text" name="theme_neon_top[cta_secondary]" value="{{ $top['cta_secondary'] ?? '' }}" class="admin-field mt-1" placeholder="Kurulum">
                    </div>
                </div>
            </div>

            <div class="hv-tab-panel hv-tab-panel-neon_features mt-6 space-y-8">
                <div class="space-y-6">
                    <div>
                        <h2 class="admin-label-block text-base">Neon tema — orta bölüm (5 madde)</h2>
                        <p class="mt-1 text-xs text-slate-600 dark:text-slate-500">Başlık ve 5 özellik satırı, Neon ana sayfa akışında üst bölümün altında gösterilir.</p>
                    </div>
                    <div>
                        <label class="admin-label">Bölüm başlığı</label>
                        <input type="text" name="theme_neon_stack_section[title]" value="{{ $stackSec['title'] ?? '' }}" class="admin-field mt-1">
                    </div>
                    <div>
                        <label class="admin-label">Bölüm alt metni</label>
                        <textarea name="theme_neon_stack_section[lead]" rows="2" class="admin-field mt-1">{{ $stackSec['lead'] ?? '' }}</textarea>
                    </div>
                    @foreach ($stackRows as $idx => $row)
                        <div class="rounded-xl border border-slate-200/90 p-4 dark:border-slate-700">
                            <p class="mb-3 text-xs font-semibold text-slate-500">Madde {{ $idx + 1 }} / 5</p>
                            <div class="space-y-3">
                                <div>
                                    <label class="admin-label text-xs">Başlık</label>
                                    <input type="text" name="theme_neon_stack[{{ $idx }}][title]" value="{{ $row['title'] ?? '' }}" class="admin-field mt-1">
                                </div>
                                <div>
                                    <label class="admin-label text-xs">Açıklama</label>
                                    <textarea name="theme_neon_stack[{{ $idx }}][body]" rows="2" class="admin-field mt-1">{{ $row['body'] ?? '' }}</textarea>
                                </div>
                                <div>
                                    <label class="admin-label text-xs">İkon</label>
                                    <select name="theme_neon_stack[{{ $idx }}][icon]" class="admin-field mt-1">
                                        @foreach ($featureIcons as $ikey => $ilabel)
                                            <option value="{{ $ikey }}" @selected(($row['icon'] ?? 'layers') === $ikey)>{{ $ilabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="space-y-6">
                    <div>
                        <h2 class="admin-label-block text-base">Neon tema — alt ızgara (6 kutu)</h2>
                        <p class="mt-1 text-xs text-slate-600 dark:text-slate-500">İkinci özellik bölümü, masaüstünde 3x2 düzeninde; mobilde tek sütunda akar.</p>
                    </div>
                    <div>
                        <label class="admin-label">Bölüm başlığı</label>
                        <input type="text" name="theme_neon_grid_section[title]" value="{{ $gridSec['title'] ?? '' }}" class="admin-field mt-1">
                    </div>
                    <div>
                        <label class="admin-label">Bölüm alt metni</label>
                        <textarea name="theme_neon_grid_section[lead]" rows="2" class="admin-field mt-1">{{ $gridSec['lead'] ?? '' }}</textarea>
                    </div>
                    @foreach ($gridRows as $idx => $row)
                        <div class="rounded-xl border border-slate-200/90 p-4 dark:border-slate-700">
                            <p class="mb-3 text-xs font-semibold text-slate-500">Kutu {{ $idx + 1 }} / 6</p>
                            <div class="space-y-3">
                                <div>
                                    <label class="admin-label text-xs">Başlık</label>
                                    <input type="text" name="theme_neon_grid[{{ $idx }}][title]" value="{{ $row['title'] ?? '' }}" class="admin-field mt-1">
                                </div>
                                <div>
                                    <label class="admin-label text-xs">Açıklama</label>
                                    <textarea name="theme_neon_grid[{{ $idx }}][body]" rows="2" class="admin-field mt-1">{{ $row['body'] ?? '' }}</textarea>
                                </div>
                                <div>
                                    <label class="admin-label text-xs">İkon</label>
                                    <select name="theme_neon_grid[{{ $idx }}][icon]" class="admin-field mt-1">
                                        @foreach ($featureIcons as $ikey => $ilabel)
                                            <option value="{{ $ikey }}" @selected(($row['icon'] ?? 'layers') === $ikey)>{{ $ilabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="rounded-full bg-orange-500 px-6 py-2.5 text-sm font-semibold text-white hover:bg-orange-600">Kaydet</button>
            <a href="{{ route('landing.home') }}" target="_blank" rel="noopener" class="admin-btn-outline items-center px-6 py-2.5">Siteyi aç</a>
        </div>
    </form>
</div>

<script>
    (function () {
        var p = document.getElementById('theme_primary_hex_picker');
        var t = document.getElementById('theme_primary_hex');
        var c = document.getElementById('clear_hex');
        if (p && t) {
            p.addEventListener('input', function () { t.value = p.value; });
            t.addEventListener('input', function () {
                if (/^#[0-9A-Fa-f]{6}$/.test(t.value)) p.value = t.value;
            });
        }
        if (c && t && p) {
            c.addEventListener('click', function () { t.value = ''; p.value = '#22d3ee'; });
        }
    })();
</script>
