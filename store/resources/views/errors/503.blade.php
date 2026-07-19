@include('errors._page', [
    'code' => 503,
    'title' => 'Kısa bir bakım molasındayız',
    'description' => 'HostVim hizmetleri planlı bakım nedeniyle kısa süreliğine kullanılamıyor.',
    'message' => 'Daha hızlı ve güvenli bir deneyim için sistem üzerinde çalışıyoruz. Birkaç dakika sonra yeniden deneyebilirsiniz.',
    'accent' => '#22c55e',
    'accentDark' => '#15803d',
    'icon' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m14.7 6.3 3-3 3 3-3 3M4 20l7.5-7.5"/><path d="M16 4a6 6 0 0 0-8 8l-5 5 4 4 5-5a6 6 0 0 0 8-8"/></svg>',
    'showBack' => false,
])
