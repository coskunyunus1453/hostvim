import { Navigate, useLocation } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { useProFeatures } from '../hooks/useProFeatures'
import { useAuthStore } from '../store/authStore'
import { Loader2, Lock } from 'lucide-react'

type Props = {
  children: React.ReactNode
  /** Modül anahtarı; boşsa path’ten çıkarılır */
  moduleKey?: string
}

export default function ProFeatureGate({ children, moduleKey }: Props) {
  const { t } = useTranslation()
  const location = useLocation()
  const isAdmin = useAuthStore((s) => s.user?.roles?.some((r) => r.name === 'admin'))
  const { isLoading, canAccessPath, isModuleEnabled, moduleKeyForPath } = useProFeatures()

  const key = moduleKey ?? moduleKeyForPath(location.pathname) ?? ''
  const allowed = key ? isModuleEnabled(key) : canAccessPath(location.pathname)

  if (isLoading) {
    return (
      <div className="flex min-h-[40vh] items-center justify-center text-gray-500">
        <Loader2 className="h-8 w-8 animate-spin" />
      </div>
    )
  }

  if (allowed) {
    return <>{children}</>
  }

  if (isAdmin) {
    return <Navigate to="/admin/license" replace state={{ from: location.pathname, proFeature: key }} />
  }

  return (
    <div className="card mx-auto max-w-lg p-8 text-center">
      <Lock className="mx-auto h-10 w-10 text-amber-500" />
      <h2 className="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
        {t('license.pro_locked_title')}
      </h2>
      <p className="mt-2 text-sm text-gray-500">{t('license.pro_locked_hint')}</p>
    </div>
  )
}
