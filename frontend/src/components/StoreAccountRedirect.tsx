import { useEffect } from 'react'
import { useLocation } from 'react-router-dom'

const STORE_ACCOUNT_PATHS = new Set(['/invoices', '/domain-portfolio', '/billing', '/support'])

/**
 * Müşteri faturalama / domain portföyü artık satış sitesinde (hostvim.com/hesabim).
 */
export default function StoreAccountRedirect({ storeAccountUrl }: { storeAccountUrl?: string }) {
  const location = useLocation()

  useEffect(() => {
    if (!STORE_ACCOUNT_PATHS.has(location.pathname)) {
      return
    }

    const envUrl = (import.meta as ImportMeta & { env?: { VITE_STORE_ACCOUNT_URL?: string } }).env
      ?.VITE_STORE_ACCOUNT_URL
    const base = storeAccountUrl?.trim() || (typeof envUrl === 'string' ? envUrl.trim() : '') || 'https://hostvim.com/hesabim'

    const path =
      location.pathname === '/invoices'
        ? '/faturalarim'
        : location.pathname === '/domain-portfolio'
          ? '/alan-adlarim'
          : location.pathname === '/support'
            ? '/destek'
          : '/hesabim'

    const target = base.replace(/\/$/, '') + path
    window.location.replace(target)
  }, [location.pathname, storeAccountUrl])

  return (
    <div className="flex min-h-[40vh] flex-col items-center justify-center gap-2 p-6 text-center text-sm text-gray-500 dark:text-gray-400">
      <p>Hesap işlemleri satış sitesine taşındı.</p>
      <p>Yönlendiriliyorsunuz…</p>
    </div>
  )
}
