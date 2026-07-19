@include('errors._page', [
    'code' => 403,
    'title' => 'Bu alana erişiminiz yok',
    'description' => 'İstenen HostVim sayfasına erişim yetkiniz bulunmuyor.',
    'message' => 'Bu içerik yalnızca yetkili kullanıcılar tarafından görüntülenebilir. Hesabınızla giriş yapabilir veya güvenle ana sayfaya dönebilirsiniz.',
    'accent' => '#f59e0b',
    'accentDark' => '#b45309',
    'icon' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>',
])
