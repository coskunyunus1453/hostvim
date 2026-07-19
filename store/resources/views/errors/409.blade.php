@include('errors._page', [
    'code' => 409,
    'title' => 'İşlem mevcut durumla çakıştı',
    'description' => 'HostVim üzerinde gönderilen işlem mevcut kayıtla çakıştı.',
    'message' => 'İşlem başka bir değişiklikle çakıştığı için tamamlanamadı. Sayfayı yenileyip tekrar deneyebilir veya ana sayfadan devam edebilirsiniz.',
    'accent' => '#8b5cf6',
    'accentDark' => '#6d28d9',
    'icon' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 7h11l-3-3m3 3-3 3M16 17H5l3 3m-3-3 3-3"/></svg>',
])
