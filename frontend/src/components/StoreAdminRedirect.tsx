import { useEffect } from 'react'

type Props = {
  path: string
  message?: string
}

/**
 * Panelze yönetici ekranları hostvim.com Filament adminine taşındı.
 */
export default function StoreAdminRedirect({ path, message }: Props) {
  useEffect(() => {
    const envUrl = (import.meta as ImportMeta & { env?: { VITE_STORE_URL?: string } }).env?.VITE_STORE_URL
    const base = (typeof envUrl === 'string' ? envUrl.trim() : '') || 'https://hostvim.com'
    const target = base.replace(/\/$/, '') + path
    window.location.replace(target)
  }, [path])

  return (
    <div className="flex min-h-[40vh] flex-col items-center justify-center gap-2 p-6 text-center text-sm text-gray-500 dark:text-gray-400">
      <p>{message ?? 'Bu yönetim ekranı satış sitesi admin paneline taşındı.'}</p>
      <p>Yönlendiriliyorsunuz…</p>
    </div>
  )
}
