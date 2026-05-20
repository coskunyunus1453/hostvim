import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Download, RefreshCcw, X, Loader2 } from 'lucide-react'
import toast from 'react-hot-toast'
import api from '../../services/api'
import { useAuthStore } from '../../store/authStore'
import { isHostingSuperAdmin } from '../../lib/authRoles'
import clsx from 'clsx'

type ReleaseInfo = {
  version: string
  title: string
  changelog: string
  published_at?: string
  requires_engine_restart?: boolean
}

type UpdateStatus = {
  current_version: string
  update_available: boolean
  latest?: ReleaseInfo | null
  updating: boolean
  active_run_id?: number | null
  dismissed_version?: string | null
  hub_error?: string | null
  hub_configured?: boolean
}

type UpdateRun = {
  id: number
  status: string
  progress: number
  message?: string
  output?: string
  to_version?: string
}

export default function PanelUpdateCard({ compact = false }: { compact?: boolean }) {
  const { t } = useTranslation()
  const qc = useQueryClient()
  const user = useAuthStore((s) => s.user)
  const isAdmin = isHostingSuperAdmin(user)
  const [showChangelog, setShowChangelog] = useState(false)

  const statusQ = useQuery({
    queryKey: ['panel-update-status'],
    queryFn: async () => (await api.get('/system/panel/update/status')).data as UpdateStatus,
    enabled: isAdmin,
    refetchInterval: (q) => {
      const d = q.state.data
      if (d?.updating || d?.active_run_id) return 3000
      return 60_000
    },
  })

  const runQ = useQuery({
    queryKey: ['panel-update-run', statusQ.data?.active_run_id],
    queryFn: async () => {
      const id = statusQ.data?.active_run_id
      if (!id) return null
      const { data } = await api.get(`/system/panel/update/runs/${id}`)
      return data.run as UpdateRun
    },
    enabled: !!statusQ.data?.active_run_id,
    refetchInterval: 2500,
  })

  const checkM = useMutation({
    mutationFn: async () => api.post('/system/panel/update/check'),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['panel-update-status'] })
      void qc.invalidateQueries({ queryKey: ['notifications-feed'] })
      toast.success(t('panel_update.check_done'))
    },
    onError: () => toast.error(t('panel_update.check_failed')),
  })

  const applyM = useMutation({
    mutationFn: async () => api.post('/system/panel/update/apply'),
    onSuccess: (res) => {
      void qc.invalidateQueries({ queryKey: ['panel-update-status'] })
      const bg = res.data?.background
      if (bg) {
        toast.success(t('panel_update.apply_started'))
      } else if (res.data?.run?.status === 'success') {
        toast.success(t('panel_update.apply_success'))
      } else {
        toast.error(t('panel_update.apply_failed'))
      }
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message || t('panel_update.apply_failed'))
    },
  })

  const dismissM = useMutation({
    mutationFn: async (version: string) => api.post('/system/panel/update/dismiss', { version }),
    onSuccess: () => void qc.invalidateQueries({ queryKey: ['panel-update-status'] }),
  })

  const status = statusQ.data
  const latest = status?.latest
  const run = runQ.data
  const busy = status?.updating || applyM.isPending || run?.status === 'running' || run?.status === 'queued'

  const dismissed = status?.dismissed_version === latest?.version
  const showBanner =
    status?.update_available && latest && !dismissed && latest.version !== status.current_version

  useEffect(() => {
    if (run?.status === 'success') {
      void qc.invalidateQueries({ queryKey: ['panel-update-status'] })
      void qc.invalidateQueries({ queryKey: ['notifications-feed'] })
    }
  }, [run?.status, qc])

  if (!isAdmin) {
    return null
  }

  if (!status && statusQ.isLoading) {
    return null
  }

  return (
    <div
      className={clsx(
        'card border',
        showBanner
          ? 'border-sky-300 bg-sky-50/80 dark:border-sky-800 dark:bg-sky-950/30'
          : 'border-gray-200 dark:border-gray-800',
        compact ? 'p-4' : 'p-5',
      )}
    >
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h3 className={clsx('font-semibold text-gray-900 dark:text-white', compact ? 'text-sm' : 'text-base')}>
            {t('panel_update.title')}
          </h3>
          <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
            {t('panel_update.current')}: <span className="font-mono">{status?.current_version ?? '—'}</span>
            {latest && showBanner && (
              <>
                {' '}
                → <span className="font-mono text-sky-700 dark:text-sky-300">{latest.version}</span>
              </>
            )}
          </p>
          {status?.hub_error && (
            <p className="text-xs text-amber-700 dark:text-amber-300 mt-2">{status.hub_error}</p>
          )}
        </div>
        <div className="flex flex-wrap gap-2">
          <button
            type="button"
            className="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs dark:border-gray-600"
            onClick={() => checkM.mutate()}
            disabled={checkM.isPending || busy}
          >
            {checkM.isPending ? <Loader2 className="h-3.5 w-3.5 animate-spin" /> : <RefreshCcw className="h-3.5 w-3.5" />}
            {t('panel_update.check')}
          </button>
          {showBanner && (
            <>
              <button
                type="button"
                className="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs dark:border-gray-600"
                onClick={() => setShowChangelog((v) => !v)}
              >
                {t('panel_update.view_changelog')}
              </button>
              <button
                type="button"
                className="inline-flex items-center gap-1.5 rounded-lg bg-sky-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-sky-500 disabled:opacity-50"
                onClick={() => {
                  if (
                    window.confirm(
                      t('panel_update.confirm', {
                        version: latest.version,
                      }),
                    )
                  ) {
                    applyM.mutate()
                  }
                }}
                disabled={busy}
              >
                {busy ? <Loader2 className="h-3.5 w-3.5 animate-spin" /> : <Download className="h-3.5 w-3.5" />}
                {t('panel_update.apply')}
              </button>
              <button
                type="button"
                className="inline-flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs text-gray-500"
                onClick={() => dismissM.mutate(latest.version)}
                title={t('panel_update.dismiss')}
              >
                <X className="h-3.5 w-3.5" />
              </button>
            </>
          )}
        </div>
      </div>

      {busy && (run || status?.updating) && (
        <div className="mt-3 rounded-lg bg-white/60 dark:bg-gray-900/40 p-3 text-xs">
          <div className="flex items-center gap-2 text-sky-800 dark:text-sky-200">
            <Loader2 className="h-4 w-4 animate-spin" />
            {run?.message || t('panel_update.in_progress')}
          </div>
          <div className="mt-2 h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
            <div
              className="h-full bg-sky-500 transition-all"
              style={{ width: `${run?.progress ?? 10}%` }}
            />
          </div>
        </div>
      )}

      {showChangelog && latest?.changelog && (
        <pre className="mt-3 max-h-48 overflow-auto rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-3 text-xs whitespace-pre-wrap font-sans text-gray-700 dark:text-gray-300">
          {latest.title ? `${latest.title}\n\n` : ''}
          {latest.changelog}
        </pre>
      )}

      {run?.status === 'failed' && run.output && (
        <pre className="mt-3 max-h-32 overflow-auto rounded-lg bg-rose-50 dark:bg-rose-950/30 p-2 text-[10px] text-rose-800 dark:text-rose-200">
          {run.output.slice(-2000)}
        </pre>
      )}
    </div>
  )
}
