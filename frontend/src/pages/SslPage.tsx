import { useTranslation } from 'react-i18next'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import api from '../services/api'
import { Lock, RefreshCw, ShieldOff, Info, Loader2 } from 'lucide-react'
import toast from 'react-hot-toast'
import { useHostingTargets, type HostingTarget } from '../hooks/useHostingTargets'
import { useAuthStore } from '../store/authStore'
import { tokenHasAbility } from '../lib/abilities'
import SslProgressModal, {
  type SslJobAction,
  type SslJobState,
} from '../components/ssl/SslProgressModal'

const SSL_TIMEOUT_MS = 900_000

type Cert = {
  id: number
  domain_id: number
  site_subdomain_id?: number | null
  provider: string
  status: string
  auto_renew: boolean
  expires_at: string | null
  domain?: { id: number; name: string; force_https?: boolean; ssl_enabled?: boolean }
  site_subdomain?: { id: number; hostname: string } | null
}

type DiagnosticRow = { key?: string; ok?: boolean; message?: string }

function statusLabel(status: string | undefined, t: (k: string) => string): string {
  switch (status) {
    case 'active':
      return t('ssl.status_active')
    case 'pending':
      return t('ssl.status_pending')
    case 'failed':
      return t('ssl.status_failed')
    default:
      return t('ssl.status_none')
  }
}

function providerLabel(provider: string | undefined, t: (k: string) => string): string {
  if (provider === 'letsencrypt') return t('ssl.provider_letsencrypt')
  if (provider === 'manual') return t('ssl.provider_manual')
  return provider ?? '—'
}

function statusBadgeClass(status: string | undefined): string {
  switch (status) {
    case 'active':
      return 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'
    case 'pending':
      return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'
    case 'failed':
      return 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300'
    default:
      return 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
  }
}

function certKey(domainId: number, subdomainId?: number | null): string {
  return subdomainId ? `${domainId}:s:${subdomainId}` : `${domainId}:d`
}

function formatDiagnostics(rows: DiagnosticRow[] | undefined): string[] {
  if (!rows?.length) return []
  return rows.map((row) => {
    const mark = row.ok ? '✓' : '✗'
    const msg = row.message ?? row.key ?? ''
    return `${mark} ${msg}`
  })
}

export default function SslPage() {
  const { t } = useTranslation()
  const qc = useQueryClient()
  const abilities = useAuthStore((s) => s.user?.abilities)
  const canWrite = tokenHasAbility(abilities, 'ssl:write')
  const [searchParams] = useSearchParams()
  const focusHost = (searchParams.get('focus') ?? '').trim().toLowerCase()
  const focusRowRef = useRef<HTMLTableRowElement | null>(null)
  const [manualTarget, setManualTarget] = useState<HostingTarget | null>(null)
  const [manualCert, setManualCert] = useState('')
  const [manualKey, setManualKey] = useState('')
  const [job, setJob] = useState<SslJobState | null>(null)
  const [settingsDomainId, setSettingsDomainId] = useState<number | null>(null)

  const sslQ = useQuery({
    queryKey: ['ssl'],
    queryFn: async () => (await api.get('/ssl')).data as { certificates: Cert[] },
    refetchInterval: job?.status === 'running' ? 2500 : false,
  })

  const targetsQ = useHostingTargets()
  const targets = targetsQ.data ?? []

  const focusTargetKey = useMemo(() => {
    if (!focusHost) return null
    const hit = targets.find((ht) => ht.hostname.toLowerCase() === focusHost)
    return hit?.key ?? null
  }, [focusHost, targets])

  const certs = sslQ.data?.certificates ?? []

  const certByTarget = new Map<string, Cert>()
  for (const c of certs) {
    certByTarget.set(certKey(c.domain_id, c.site_subdomain_id), c)
  }

  const invalidate = () => {
    qc.invalidateQueries({ queryKey: ['ssl'] })
    qc.invalidateQueries({ queryKey: ['domains'] })
    qc.invalidateQueries({ queryKey: ['hosting-targets'] })
  }

  const sslBody = (target: HostingTarget) =>
    target.subdomain_id ? { subdomain_id: target.subdomain_id } : {}

  const startJob = useCallback((target: HostingTarget, action: SslJobAction, initialLogs: string[] = []) => {
    const startedAt = Date.now()
    setJob({
      target,
      action,
      startedAt,
      progress: 8,
      stepIndex: 0,
      logs: initialLogs,
      status: 'running',
    })
  }, [])

  const finishJob = useCallback(
    (success: boolean, logs: string[], errorMessage?: string) => {
      setJob((prev) => {
        if (!prev) return prev
        return {
          ...prev,
          status: success ? 'success' : 'error',
          progress: success ? 100 : prev.progress,
          stepIndex: success
            ? ACTION_STEP_COUNT(prev.action) - 1
            : prev.stepIndex,
          logs: [...prev.logs, ...logs],
          errorMessage,
        }
      })
    },
    [],
  )

  const onProgressTick = useCallback((progress: number, stepIndex: number) => {
    setJob((prev) => {
      if (!prev || prev.status !== 'running') return prev
      return { ...prev, progress, stepIndex }
    })
  }, [])

  const issueM = useMutation({
    mutationFn: (target: HostingTarget) =>
      api.post(`/domains/${target.domain_id}/ssl/issue`, sslBody(target), {
        timeout: SSL_TIMEOUT_MS,
      }),
    onMutate: (target) =>
      startJob(target, 'issue', [t('ssl.progress_started', { host: target.hostname })]),
    onSuccess: (res) => {
      const data = res.data ?? {}
      finishJob(
        true,
        [
          ...formatDiagnostics(data.diagnostics),
          String(data.message ?? t('ssl.issued')),
        ],
      )
      toast.success(String(data.message ?? t('ssl.issued')))
      invalidate()
    },
    onError: (err: unknown) => {
      const ax = err as {
        response?: { data?: { message?: string; diagnostics?: DiagnosticRow[] } }
        message?: string
      }
      const msg = ax.response?.data?.message ?? ax.message ?? String(err)
      finishJob(false, formatDiagnostics(ax.response?.data?.diagnostics), msg)
      toast.error(msg)
    },
  })

  const renewM = useMutation({
    mutationFn: (target: HostingTarget) =>
      api.post(`/domains/${target.domain_id}/ssl/renew`, sslBody(target), {
        timeout: SSL_TIMEOUT_MS,
      }),
    onMutate: (target) =>
      startJob(target, 'renew', [t('ssl.progress_started', { host: target.hostname })]),
    onSuccess: (res) => {
      const data = res.data ?? {}
      finishJob(true, [String(data.message ?? t('ssl.renewed'))])
      toast.success(String(data.message ?? t('ssl.renewed')))
      invalidate()
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } }; message?: string }
      const msg = ax.response?.data?.message ?? ax.message ?? String(err)
      finishJob(false, [], msg)
      toast.error(msg)
    },
  })

  const revokeM = useMutation({
    mutationFn: (target: HostingTarget) =>
      api.post(`/domains/${target.domain_id}/ssl/revoke`, sslBody(target), {
        timeout: 120_000,
      }),
    onMutate: (target) =>
      startJob(target, 'revoke', [t('ssl.progress_started', { host: target.hostname })]),
    onSuccess: (res) => {
      const data = res.data ?? {}
      finishJob(true, [String(data.message ?? t('ssl.revoked'))])
      toast.success(String(data.message ?? t('ssl.revoked')))
      invalidate()
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } }; message?: string }
      const msg = ax.response?.data?.message ?? ax.message ?? String(err)
      finishJob(false, [], msg)
      toast.error(msg)
    },
  })

  const settingsM = useMutation({
    mutationFn: async (vars: { id: number; auto_renew?: boolean; force_https?: boolean }) =>
      api.patch(`/domains/${vars.id}/ssl/settings`, {
        ...(vars.auto_renew !== undefined ? { auto_renew: vars.auto_renew } : {}),
        ...(vars.force_https !== undefined ? { force_https: vars.force_https } : {}),
      }),
    onMutate: (vars) => {
      setSettingsDomainId(vars.id)
    },
    onSuccess: () => {
      toast.success(t('ssl.settings_saved'))
      invalidate()
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
    onSettled: () => {
      setSettingsDomainId(null)
    },
  })

  const manualM = useMutation({
    mutationFn: async (vars: { target: HostingTarget; certificate: string; private_key: string }) =>
      api.post(
        `/domains/${vars.target.domain_id}/ssl/manual`,
        {
          certificate: vars.certificate,
          private_key: vars.private_key,
          ...(vars.target.subdomain_id ? { subdomain_id: vars.target.subdomain_id } : {}),
        },
        { timeout: 120_000 },
      ),
    onMutate: (vars) =>
      startJob(vars.target, 'manual', [t('ssl.progress_started', { host: vars.target.hostname })]),
    onSuccess: (res) => {
      const data = res.data ?? {}
      finishJob(true, [String(data.message ?? t('ssl.manual_uploaded'))])
      toast.success(String(data.message ?? t('ssl.manual_uploaded')))
      setManualTarget(null)
      setManualCert('')
      setManualKey('')
      invalidate()
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } }; message?: string }
      const msg = ax.response?.data?.message ?? ax.message ?? String(err)
      finishJob(false, [], msg)
      toast.error(msg)
    },
  })

  const busyKey = job?.status === 'running' ? job.target.key : null

  const isRowBusy = (ht: HostingTarget, action?: SslJobAction) => {
    if (!busyKey || busyKey !== ht.key) return false
    if (!action || !job) return true
    return job.action === action
  }

  const daysUntil = (iso: string | null | undefined): number | null => {
    if (!iso) return null
    const ms = new Date(iso).getTime() - Date.now()
    return Math.ceil(ms / 86400000)
  }

  const loading = targetsQ.isLoading || sslQ.isLoading
  const loadError = targetsQ.isError || sslQ.isError

  const refetchAll = () => {
    void sslQ.refetch()
    void targetsQ.refetch()
  }

  useEffect(() => {
    if (loading || !focusTargetKey || !focusRowRef.current) return
    focusRowRef.current.scrollIntoView({ behavior: 'smooth', block: 'center' })
  }, [focusTargetKey, loading])

  return (
    <div className="space-y-6">
      <SslProgressModal
        job={job}
        onClose={() => setJob(null)}
        onTick={onProgressTick}
      />

      <div className="flex items-center gap-3">
        <Lock className="h-8 w-8 text-green-500" />
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{t('nav.ssl')}</h1>
          <p className="text-gray-500 dark:text-gray-400 text-sm">{t('ssl.subtitle')}</p>
        </div>
      </div>

      <div className="card flex gap-3 p-4 text-sm text-gray-600 dark:text-gray-300">
        <Info className="mt-0.5 h-5 w-5 shrink-0 text-primary-500" />
        <div>
          <p className="font-medium text-gray-900 dark:text-white">{t('ssl.info_title')}</p>
          <p className="mt-1">{t('ssl.info_body')}</p>
          <p className="mt-2 text-xs text-gray-500">{t('ssl.subdomain_hint')}</p>
        </div>
      </div>

      {!canWrite && (
        <p className="text-sm text-amber-700 dark:text-amber-300">{t('ssl.read_only_hint')}</p>
      )}

      {loadError ? (
        <div className="card rounded-lg border border-red-200 bg-red-50 px-4 py-8 text-center dark:border-red-900/40 dark:bg-red-950/30">
          <p className="text-sm text-red-700 dark:text-red-300">{t('ssl.load_error')}</p>
          <button type="button" className="btn-secondary mt-3 text-sm" onClick={refetchAll}>
            {t('domains.refresh')}
          </button>
        </div>
      ) : (
      <div className="card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[960px] text-sm">
            <thead className="bg-gray-50 dark:bg-gray-800/80">
              <tr>
                <th className="text-left px-4 py-2">{t('domains.name')}</th>
                <th className="text-left px-4 py-2">{t('ssl.provider')}</th>
                <th className="text-left px-4 py-2">{t('common.status')}</th>
                <th className="text-left px-4 py-2">{t('ssl.expires')}</th>
                <th className="text-left px-4 py-2">{t('ssl.col_settings')}</th>
                {canWrite && (
                  <th className="text-right px-4 py-2">{t('common.actions')}</th>
                )}
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr>
                  <td colSpan={canWrite ? 6 : 5} className="px-4 py-8 text-center text-gray-500">
                    {t('common.loading')}
                  </td>
                </tr>
              ) : (
                targets.map((ht) => {
                  const c = certByTarget.get(certKey(ht.domain_id, ht.subdomain_id))
                  const isSub = ht.kind === 'subdomain'
                  const hasSsl = Boolean(c?.status === 'active' || ht.ssl_enabled)
                  const forceHttps = !isSub && (c?.domain?.force_https ?? true)
                  const autoRenew = c?.auto_renew ?? false
                  const days = daysUntil(c?.expires_at ?? ht.ssl_expiry ?? null)
                  const expiringSoon = days !== null && days >= 0 && days <= 30
                  const rowLocked = busyKey === ht.key
                  const rowSettingsBusy = settingsDomainId === ht.domain_id
                  const isFocused = focusTargetKey === ht.key

                  return (
                    <tr
                      key={ht.key}
                      ref={isFocused ? focusRowRef : undefined}
                      className={`border-t border-gray-100 dark:border-gray-800 ${
                        rowLocked
                          ? 'bg-primary-50/40 dark:bg-primary-900/10'
                          : isFocused
                            ? 'bg-amber-50/80 ring-2 ring-inset ring-amber-400 dark:bg-amber-950/30'
                            : ''
                      }`}
                    >
                      <td className="px-4 py-3 font-medium">
                        <span className={isSub ? 'pl-4 font-mono text-sm' : ''}>
                          {isSub ? `↳ ${ht.hostname}` : ht.hostname}
                        </span>
                        {isSub && (
                          <p className="pl-4 text-[11px] font-normal text-gray-500">
                            {t('domains.subdomain_of', { domain: ht.parent_domain })}
                          </p>
                        )}
                      </td>
                      <td className="px-4 py-3 text-gray-600 dark:text-gray-400">
                        {c ? providerLabel(c.provider, t) : '—'}
                      </td>
                      <td className="px-4 py-3">
                        <span
                          className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${statusBadgeClass(c?.status ?? ht.ssl_status ?? undefined)}`}
                        >
                          {c
                            ? statusLabel(c.status, t)
                            : ht.ssl_status
                              ? statusLabel(ht.ssl_status, t)
                              : t('ssl.no_cert')}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-gray-500">
                        {(c?.expires_at ?? ht.ssl_expiry) ? (
                          <span className={expiringSoon ? 'text-amber-600 dark:text-amber-400' : ''}>
                            {new Date(c?.expires_at ?? ht.ssl_expiry!).toLocaleString()}
                            {expiringSoon && days !== null && (
                              <span className="ml-1 block text-xs">
                                {t('ssl.expiring_soon', { days })}
                              </span>
                            )}
                          </span>
                        ) : (
                          '—'
                        )}
                      </td>
                      <td className="px-4 py-3">
                        {isSub ? (
                          <span className="text-xs text-gray-400">{t('ssl.subdomain_settings_na')}</span>
                        ) : canWrite ? (
                          <div className="space-y-2">
                            <label
                              className={`flex items-center gap-2 text-xs ${hasSsl ? 'cursor-pointer' : 'opacity-50'}`}
                              title={t('ssl.force_https_hint')}
                            >
                              <input
                                type="checkbox"
                                className="rounded border-gray-300"
                                checked={forceHttps}
                                disabled={!hasSsl || rowSettingsBusy || rowLocked}
                                onChange={(e) =>
                                  settingsM.mutate({ id: ht.domain_id, force_https: e.target.checked })
                                }
                              />
                              <span>{t('ssl.force_https')}</span>
                            </label>
                            <label
                              className={`flex items-center gap-2 text-xs ${c ? 'cursor-pointer' : 'opacity-50'}`}
                              title={t('ssl.auto_renew_hint')}
                            >
                              <input
                                type="checkbox"
                                className="rounded border-gray-300"
                                checked={autoRenew}
                                disabled={!c || rowSettingsBusy || rowLocked}
                                onChange={(e) =>
                                  settingsM.mutate({ id: ht.domain_id, auto_renew: e.target.checked })
                                }
                              />
                              <span>{t('ssl.auto_renew')}</span>
                            </label>
                          </div>
                        ) : (
                          <div className="space-y-1 text-xs text-gray-500">
                            <p>
                              {t('ssl.force_https')}: {forceHttps ? t('common.yes') : t('common.no')}
                            </p>
                            <p>
                              {t('ssl.auto_renew')}: {autoRenew ? t('common.yes') : t('common.no')}
                            </p>
                          </div>
                        )}
                      </td>
                      {canWrite && (
                      <td className="px-4 py-3 text-right">
                        <div className="flex flex-wrap justify-end gap-1">
                          <button
                            type="button"
                            className="btn-secondary text-xs py-1 px-2 inline-flex items-center gap-1"
                            disabled={rowLocked}
                            onClick={() => issueM.mutate(ht)}
                          >
                            {isRowBusy(ht, 'issue') ? (
                              <Loader2 className="h-3 w-3 animate-spin" />
                            ) : null}
                            {t('ssl.issue')}
                          </button>
                          <button
                            type="button"
                            className="btn-secondary text-xs py-1 px-2 inline-flex items-center gap-1"
                            disabled={!c || rowLocked}
                            onClick={() => renewM.mutate(ht)}
                          >
                            {isRowBusy(ht, 'renew') ? (
                              <Loader2 className="h-3 w-3 animate-spin" />
                            ) : (
                              <RefreshCw className="h-3 w-3" />
                            )}
                            {t('ssl.renew')}
                          </button>
                          <button
                            type="button"
                            className="btn-secondary text-xs py-1 px-2 text-red-600 inline-flex items-center gap-1"
                            disabled={!hasSsl || rowLocked}
                            onClick={() => {
                              if (window.confirm(t('ssl.confirm_revoke'))) revokeM.mutate(ht)
                            }}
                          >
                            {isRowBusy(ht, 'revoke') ? (
                              <Loader2 className="h-3 w-3 animate-spin" />
                            ) : (
                              <ShieldOff className="h-3 w-3" />
                            )}
                            {t('ssl.revoke')}
                          </button>
                          {!isSub && (
                            <button
                              type="button"
                              className="btn-secondary text-xs py-1 px-2"
                              disabled={rowLocked}
                              onClick={() => setManualTarget(ht)}
                            >
                              {t('ssl.manual_upload')}
                            </button>
                          )}
                        </div>
                      </td>
                      )}
                    </tr>
                  )
                })
              )}
            </tbody>
          </table>
        </div>
        {!loading && targets.length === 0 && (
          <p className="p-6 text-center text-gray-500">{t('common.no_data')}</p>
        )}
      </div>
      )}

      {manualTarget && canWrite && (
        <div className="card space-y-4 p-4">
          <h2 className="font-semibold text-gray-900 dark:text-white">
            {t('ssl.manual_upload')} — {manualTarget.hostname}
          </h2>
          <textarea
            className="input font-mono text-xs min-h-[120px]"
            placeholder={t('ssl.cert_pem')}
            value={manualCert}
            onChange={(e) => setManualCert(e.target.value)}
          />
          <textarea
            className="input font-mono text-xs min-h-[120px]"
            placeholder={t('ssl.private_key_pem')}
            value={manualKey}
            onChange={(e) => setManualKey(e.target.value)}
          />
          <div className="flex gap-2">
            <button
              type="button"
              className="btn-primary inline-flex items-center gap-2"
              disabled={manualM.isPending || manualCert.length < 64 || manualKey.length < 64}
              onClick={() =>
                manualM.mutate({
                  target: manualTarget,
                  certificate: manualCert,
                  private_key: manualKey,
                })
              }
            >
              {manualM.isPending && <Loader2 className="h-4 w-4 animate-spin" />}
              {t('ssl.manual_upload')}
            </button>
            <button type="button" className="btn-secondary" onClick={() => setManualTarget(null)}>
              {t('common.cancel')}
            </button>
          </div>
        </div>
      )}
    </div>
  )
}

function ACTION_STEP_COUNT(action: SslJobAction): number {
  switch (action) {
    case 'issue':
      return 5
    case 'renew':
      return 4
    case 'revoke':
      return 3
    case 'manual':
      return 2
    default:
      return 3
  }
}
