@include('errors._page', [
    'code' => 422,
    'title' => 'Gönderilen bilgiler doğrulanamadı',
    'description' => 'HostVim formundaki bazı bilgiler doğrulanamadı.',
    'message' => 'Bazı alanlar eksik veya beklenen biçimde değil. Önceki sayfaya dönerek bilgileri kontrol edip yeniden gönderebilirsiniz.',
    'accent' => '#ec4899',
    'accentDark' => '#be185d',
    'icon' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 2.5 20h19L12 3Z"/><path d="M12 9v4m0 3h.01"/></svg>',
])
