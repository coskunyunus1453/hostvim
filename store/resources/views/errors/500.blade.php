@include('errors._page', [
    'code' => 500,
    'title' => 'Beklenmeyen bir sorun oluştu',
    'description' => 'HostVim isteği şu anda tamamlayamadı; ana sayfadan hizmetlere erişebilirsiniz.',
    'message' => 'İsteğiniz tamamlanırken geçici bir teknik sorun yaşandı. Ekibimiz sistemi izliyor; kısa süre sonra tekrar deneyebilir veya ana sayfadan devam edebilirsiniz.',
    'accent' => '#ef4444',
    'accentDark' => '#b91c1c',
    'icon' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.3 3.4 2.6 17a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 3.4a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4m0 3h.01"/></svg>',
    'showBack' => false,
])
