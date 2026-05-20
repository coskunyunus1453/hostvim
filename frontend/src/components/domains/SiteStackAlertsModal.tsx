import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useNavigate } from 'react-router-dom'
import api from '../../services/api'
import toast from 'react-hot-toast'
import clsx from 'clsx'
import { AlertTriangle, Loader2, Sparkles, Wrench, X } from 'lucide-react'

type StackIssue = {
  code: string
  severity: string
  message: string
  fixable?: boolean
}

type StackAlertItem = {
  id: number
  domain_id: number
  domain_name: string
  profile: string
  severity: string
  issue_count: number
  issues: StackIssue[]
  scan?: { guidance?: string; profile_label?: string }
}

type Props = {
  open: boolean
  onClose: () => void
}

export default function SiteStackAlertsModal({ open, onClose }: Props) {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const qc = useQueryClient()
  const [fixingId, setFixingId] = useState<number | null>(null)

  const alertsQ = useQuery({
    queryKey: ['stack-alerts'],
    enabled: open,
    queryFn: async () => (await api.get<{ items: StackAlertItem[] }>('/domains/stack-alerts')).data.items,
    staleTime: 30_000,
  })

  useEffect(() => {
    if (open) void alertsQ.refetch()
  }, [open])

  const dismissM = useMutation({
    mutationFn: async (id: number) => {
      await api.post(`/domains/stack-alerts/${id}/dismiss`)
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['stack-alerts'] })
      void qc.invalidateQueries({ queryKey: ['notifications-feed'] })
      void qc.invalidateQueries({ queryKey: ['stack-alerts', 'bootstrap'] })
    },
  })

  const fixM = useMutation({
    mutationFn: async (domainId: number) => {
      const { data } = await api.post(`/domains/${domainId}/stack-fix`, { fix_ids: [] })
      return data
    },
    onSuccess: () => {
      toast.success(t('domains.stack_fix_ok', { count: 1 }))
      void qc.invalidateQueries({ queryKey: ['stack-alerts'] })
      void qc.invalidateQueries({ queryKey: ['domains'] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
    onSettled: () => setFixingId(null),
  })

  if (!open) return null

  const items = alertsQ.data ?? []

  return (
    <div className="fixed inset-0 z-[60] flex items-center justify-center bg-black/55 p-4">
      <div className="card max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col bg-white dark:bg-gray-900">
        <div className="flex items-start justify-between gap-3 border-b border-gray-200 dark:border-gray-700 px-5 py-4">
          <div className="flex gap-2">
            <Sparkles className="h-5 w-5 text-violet-600 shrink-0" />
            <div>
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white">{t('domains.stack_alerts_modal_title')}</h2>
              <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{t('domains.stack_alerts_modal_hint')}</p>
            </div>
          </div>
          <button type="button" className="btn-ghost p-1" onClick={onClose} aria-label={t('common.close')}>
            <X className="h-5 w-5" />
          </button>
        </div>

        <div className="flex-1 overflow-y-auto px-5 py-4 space-y-4">
          {alertsQ.isLoading && (
            <p className="flex items-center gap-2 text-sm text-gray-500">
              <Loader2 className="h-4 w-4 animate-spin" />
              {t('domains.stack_scanning')}
            </p>
          )}

          {!alertsQ.isLoading && items.length === 0 && (
            <p className="text-sm text-gray-600 dark:text-gray-400">{t('domains.stack_alerts_empty')}</p>
          )}

          {items.map((item) => (
            <div
              key={item.id}
              className={clsx(
                'rounded-xl border p-4',
                item.severity === 'critical'
                  ? 'border-red-200 bg-red-50/50 dark:border-red-900/50 dark:bg-red-950/20'
                  : 'border-amber-200 bg-amber-50/40 dark:border-amber-900/40 dark:bg-amber-950/15',
              )}
            >
              <div className="flex flex-wrap items-start justify-between gap-2">
                <div>
                  <p className="font-semibold text-gray-900 dark:text-white">{item.domain_name}</p>
                  <p className="text-xs text-gray-500">
                    {item.scan?.profile_label ?? item.profile} · {item.issue_count} {t('domains.stack_issues_label')}
                  </p>
                </div>
                <span
                  className={clsx(
                    'text-xs font-medium px-2 py-0.5 rounded',
                    item.severity === 'critical'
                      ? 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200'
                      : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
                  )}
                >
                  {item.severity === 'critical' ? t('domains.stack_severity_critical') : t('domains.stack_severity_warning')}
                </span>
              </div>

              {item.scan?.guidance ? (
                <p className="mt-2 text-xs text-violet-900/90 dark:text-violet-200/90 rounded-lg bg-violet-50/80 dark:bg-violet-950/30 px-2.5 py-2">
                  {item.scan.guidance}
                </p>
              ) : null}

              <ul className="mt-3 space-y-1.5">
                {item.issues.slice(0, 6).map((issue) => (
                  <li key={issue.code + issue.message} className="flex gap-2 text-xs text-gray-700 dark:text-gray-300">
                    <AlertTriangle className="h-3.5 w-3.5 shrink-0 text-amber-600" />
                    {issue.message}
                  </li>
                ))}
              </ul>

              <div className="mt-3 flex flex-wrap gap-2">
                <button
                  type="button"
                  className="btn-primary text-xs inline-flex items-center gap-1"
                  disabled={fixingId === item.domain_id || fixM.isPending}
                  onClick={() => {
                    setFixingId(item.domain_id)
                    fixM.mutate(item.domain_id)
                  }}
                >
                  {fixingId === item.domain_id ? <Loader2 className="h-3.5 w-3.5 animate-spin" /> : <Wrench className="h-3.5 w-3.5" />}
                  {t('domains.stack_fix_all')}
                </button>
                <button
                  type="button"
                  className="btn-secondary text-xs"
                  onClick={() => {
                    onClose()
                    navigate('/domains')
                  }}
                >
                  {t('domains.stack_open_domains')}
                </button>
                <button
                  type="button"
                  className="btn-ghost text-xs"
                  onClick={() => dismissM.mutate(item.id)}
                  disabled={dismissM.isPending}
                >
                  {t('domains.stack_dismiss_alert')}
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}
