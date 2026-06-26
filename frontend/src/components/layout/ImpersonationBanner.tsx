import { useTranslation } from 'react-i18next'
import { useNavigate } from 'react-router-dom'
import { LogOut, UserCog } from 'lucide-react'
import { useAuthStore } from '../../store/authStore'
import api from '../../services/api'

export default function ImpersonationBanner() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const user = useAuthStore((s) => s.user)
  const impersonationBackup = useAuthStore((s) => s.impersonationBackup)
  const endImpersonation = useAuthStore((s) => s.endImpersonation)

  if (!impersonationBackup && !user?.impersonated_by) {
    return null
  }

  const adminName = impersonationBackup?.user.name ?? user?.impersonated_by?.name ?? 'Admin'

  const handleExit = async () => {
    try {
      await api.post('/auth/logout')
    } catch {
      /* impersonation token silinmiş olabilir */
    }
    if (endImpersonation()) {
      navigate('/admin/users', { replace: true })
    }
  }

  return (
    <div className="bg-amber-500 text-amber-950 px-4 py-2 flex flex-wrap items-center justify-between gap-2 text-sm shadow-md z-50 relative">
      <div className="flex items-center gap-2 font-medium">
        <UserCog className="h-4 w-4 shrink-0" />
        <span>
          {t('users.impersonation_banner', {
            customer: user?.name ?? user?.email,
            admin: adminName,
          })}
        </span>
      </div>
      <button
        type="button"
        onClick={() => void handleExit()}
        className="inline-flex items-center gap-1 rounded-md bg-amber-950/15 hover:bg-amber-950/25 px-3 py-1 text-xs font-semibold"
      >
        <LogOut className="h-3.5 w-3.5" />
        {t('users.impersonation_exit')}
      </button>
    </div>
  )
}
