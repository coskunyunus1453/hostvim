import { useEffect, useRef } from 'react'
import { useTranslation } from 'react-i18next'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { useQueryClient } from '@tanstack/react-query'
import { Loader2 } from 'lucide-react'
import toast from 'react-hot-toast'
import api from '../services/api'

/**
 * Google OAuth dönüşü — API kökü /backups altında yanlış çözülmesin diye ayrı sayfa.
 */
export default function GoogleDriveCallbackPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const qc = useQueryClient()
  const started = useRef(false)

  useEffect(() => {
    if (started.current) return
    started.current = true

    const gdriveError = searchParams.get('gdrive_error')
    if (gdriveError) {
      toast.error(gdriveError)
      navigate('/backups', { replace: true })
      return
    }

    const code = searchParams.get('code')
    const state = searchParams.get('state')
    if (!code || !state) {
      navigate('/backups', { replace: true })
      return
    }

    ;(async () => {
      try {
        await api.post('/backups/google-drive/complete', { code, state })
        toast.success(t('backups.google_connected'))
        await qc.invalidateQueries({ queryKey: ['backups-gdrive'] })
        await qc.invalidateQueries({ queryKey: ['backup-destinations'] })
      } catch (err: unknown) {
        const ax = err as { response?: { data?: { message?: string } } }
        toast.error(ax.response?.data?.message ?? String(err))
      } finally {
        navigate('/backups', { replace: true })
      }
    })()
  }, [navigate, qc, searchParams, t])

  return (
    <div className="flex min-h-[50vh] flex-col items-center justify-center gap-3 text-gray-600 dark:text-gray-300">
      <Loader2 className="h-8 w-8 animate-spin text-blue-500" />
      <p className="text-sm">{t('backups.google_callback_processing')}</p>
    </div>
  )
}
