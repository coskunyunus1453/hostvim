/**
 * Panel `public/` altında veya sanal kökte yayın için taban yolu.
 * Üretimde `index.html` içindeki `./assets/index-*.js` adresinden çıkarılır (XAMPP alt klasörü vb.).
 */
export function inferPublicPathPrefix(): string {
  if (typeof document === 'undefined') {
    return ''
  }
  const el = document.querySelector(
    'script[type="module"][src*="/assets/index-"]',
  ) as HTMLScriptElement | null
  if (!el?.src) {
    return ''
  }
  let path = new URL(el.src).pathname
  // Yanlış VITE_BASE (/admin/) + zaten /admin altında yayın → /admin/admin/assets/...
  if (path.includes('/admin/admin/assets/')) {
    path = path.replace('/admin/admin/', '/admin/')
  }
  const marker = '/assets/index-'
  const idx = path.indexOf(marker)
  if (idx <= 0) {
    return ''
  }
  return path.slice(0, idx)
}

/** Laravel API kökü — deep link (/backups/google-callback vb.) yolundan türetilmez. */
export function panelAppRootForApi(): string {
  const prefix = inferPublicPathPrefix().replace(/\/+$/, '')
  if (prefix) {
    return `${prefix.replace(/\/admin$/, '')}/`
  }
  if (typeof window !== 'undefined' && window.location.pathname.startsWith('/admin')) {
    return '/admin/'
  }
  return '/'
}
