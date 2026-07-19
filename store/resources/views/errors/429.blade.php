@include('errors._page', [
    'code' => 429,
    'title' => 'Çok fazla istek gönderildi',
    'description' => 'HostVim güvenlik sınırı nedeniyle istek geçici olarak durduruldu.',
    'message' => 'Kısa sürede çok sayıda işlem yapıldı. Lütfen bir dakika bekleyip yeniden deneyin; hizmetlerimiz çalışmaya devam ediyor.',
    'accent' => '#eab308',
    'accentDark' => '#a16207',
    'icon' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h13m-4-4 4 4-4 4"/><path d="M19 5v14"/></svg>',
])
