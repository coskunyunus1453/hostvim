import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Navigate } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useAuthStore } from '../store/authStore'
import api from '../services/api'
import { KeyRound } from 'lucide-react'
import toast from 'react-hot-toast'

type LicenseSummary = {
  valid?: boolean
  plan?: string | null
  plan_name?: string | null
  status?: string | null
  expires_at?: string | null
  owner?: string | null
  license_id?: string | null
}

type LicenseStatus = {
  local_key_set?: boolean
  key_source?: 'env' | 'database' | 'none'
  key_preview?: string | null
  source?: string
  summary?: LicenseSummary
}

export default function AdminLicensePage() {
  const { t } = useTranslation()
  const qc = useQueryClient()
  const user = useAuthStore((s) => s.user)
  const isAdmin = user?.roles?.some((r) => r.name === 'admin')
  const [keyInput, setKeyInput] = useState('')

  const statusQ = useQuery({
    queryKey: ['license-status'],
    queryFn: async () => (await api.get('/license')).data as LicenseStatus,
    enabled: !!isAdmin,
  })

  const activateM = useMutation({
    mutationFn: async (key: string) => api.post('/license/activate', { key }),
    onSuccess: (res) => {
      qc.invalidateQueries({ queryKey: ['license-status'] })
      qc.invalidateQueries({ queryKey: ['config-ui-links'] })
      setKeyInput('')
      const d = res.data as { hub?: { plan_name?: string } }
      const plan = d?.hub && typeof d.hub.plan_name === 'string' ? d.hub.plan_name : ''
      toast.success(plan ? t('license.activate_ok_plan', { plan }) : t('license.activate_ok'))
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const clearM = useMutation({
    mutationFn: async () => api.post('/license/clear'),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['license-status'] })
      qc.invalidateQueries({ queryKey: ['config-ui-links'] })
      toast.success(t('license.cleared_ok'))
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const testM = useMutation({
    mutationFn: async (key: string) => api.post('/license/validate', { key }),
    onSuccess: (res) => {
      const d = res.data as Record<string, unknown>
      const msg =
        typeof d?.message === 'string'
          ? d.message
          : `${t('license.validate_ok')}: ${JSON.stringify(d)}`
      toast.success(msg, { duration: 6000 })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  if (!isAdmin) {
    return <Navigate to="/dashboard" replace />
  }

  const source = statusQ.data?.key_source ?? 'none'
  const summary = statusQ.data?.summary
  const hasKey = Boolean(statusQ.data?.local_key_set)
  const valid = Boolean(summary?.valid)
  const state = summary?.status ?? (valid ? 'active' : 'invalid')
  const planLabel = summary?.plan_name || summary?.plan || null

  const formatDate = (iso?: string | null): string | null => {
    if (!iso) return null
    const d = new Date(iso)
    return Number.isNaN(d.getTime()) ? null : d.toLocaleDateString()
  }
  const daysLeft = (iso?: string | null): number | null => {
    if (!iso) return null
    const d = new Date(iso).getTime()
    if (Number.isNaN(d)) return null
    return Math.ceil((d - Date.now()) / 86_400_000)
  }
  const expiry = formatDate(summary?.expires_at)
  const remaining = daysLeft(summary?.expires_at)

  const stateLabel =
    state === 'active'
      ? t('license.state_active')
      : state === 'grace'
        ? t('license.state_grace')
        : state === 'expired'
          ? t('license.state_expired')
          : t('license.state_invalid')
  const stateClass =
    state === 'active'
      ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
      : state === 'grace'
        ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
        : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'

  const Row = ({ label, children }: { label: string; children: React.ReactNode }) => (
    <div className="flex flex-col gap-0.5 sm:flex-row sm:items-center sm:justify-between sm:gap-4 py-2 border-b border-gray-100 last:border-0 dark:border-gray-800">
      <span className="text-sm text-gray-500 dark:text-gray-400">{label}</span>
      <span className="text-sm font-medium text-gray-900 dark:text-white">{children}</span>
    </div>
  )

  return (
    <div className="space-y-6 max-w-3xl">
      <div className="flex items-center gap-3">
        <KeyRound className="h-8 w-8 text-amber-500" />
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{t('nav.license')}</h1>
          <p className="text-gray-500 dark:text-gray-400 text-sm">{t('license.subtitle')}</p>
        </div>
      </div>

      <div className="card p-6 space-y-4">
        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">{t('license.status')}</h2>
        {statusQ.isLoading ? (
          <p className="text-gray-500">{t('common.loading')}</p>
        ) : !hasKey ? (
          <div className="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-5 text-center">
            <p className="font-medium text-gray-900 dark:text-white">{t('license.none_title')}</p>
            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">{t('license.none_hint')}</p>
          </div>
        ) : (
          <div className="divide-y divide-gray-100 dark:divide-gray-800">
            <Row label={t('license.plan')}>
              {planLabel ? <span className="capitalize">{planLabel}</span> : '—'}
            </Row>
            <Row label={t('license.state')}>
              <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${stateClass}`}>
                {stateLabel}
              </span>
            </Row>
            <Row label={t('license.expires')}>
              {expiry ? (
                <span>
                  {expiry}
                  {typeof remaining === 'number' && remaining >= 0 && (
                    <span className="ml-2 text-xs font-normal text-gray-500 dark:text-gray-400">
                      ({t('license.days_left', { days: remaining })})
                    </span>
                  )}
                </span>
              ) : (
                t('license.expires_never')
              )}
            </Row>
            {summary?.owner ? <Row label={t('license.owner')}>{summary.owner}</Row> : null}
            {summary?.license_id ? (
              <Row label={t('license.ref')}>
                <span className="font-mono text-xs">{summary.license_id}</span>
              </Row>
            ) : null}
            {statusQ.data?.key_preview ? (
              <Row label={t('license.key_preview')}>
                <span className="font-mono text-xs break-all">{statusQ.data.key_preview}</span>
              </Row>
            ) : null}
          </div>
        )}
      </div>

      <div className="card p-6 space-y-4">
        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">{t('license.activate_title')}</h2>
        <p className="text-sm text-gray-500 dark:text-gray-400">{t('license.activate_hint')}</p>
        <input
          type="password"
          autoComplete="off"
          className="input w-full font-mono text-sm"
          placeholder={t('license.key_placeholder')}
          value={keyInput}
          onChange={(e) => setKeyInput(e.target.value)}
        />
        <div className="flex flex-wrap gap-3">
          <button
            type="button"
            className="btn-primary"
            disabled={activateM.isPending || !keyInput.trim()}
            onClick={() => activateM.mutate(keyInput.trim())}
          >
            {t('license.activate')}
          </button>
          <button
            type="button"
            className="btn-secondary"
            disabled={testM.isPending || !keyInput.trim()}
            onClick={() => testM.mutate(keyInput.trim())}
          >
            {t('license.test_only')}
          </button>
          {source === 'database' ? (
            <button
              type="button"
              className="text-sm text-red-600 hover:text-red-700 dark:text-red-400"
              disabled={clearM.isPending}
              onClick={() => {
                if (window.confirm(t('license.clear_confirm'))) {
                  clearM.mutate()
                }
              }}
            >
              {t('license.clear_stored')}
            </button>
          ) : null}
        </div>
      </div>
    </div>
  )
}
