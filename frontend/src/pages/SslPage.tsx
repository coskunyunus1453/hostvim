import { useTranslation } from 'react-i18next'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import api from '../services/api'
import { Lock, RefreshCw, ShieldOff, Info } from 'lucide-react'
import toast from 'react-hot-toast'

type Cert = {
  id: number
  domain_id: number
  provider: string
  status: string
  auto_renew: boolean
  expires_at: string | null
  domain?: { id: number; name: string; force_https?: boolean; ssl_enabled?: boolean }
}

type DomainOpt = { id: number; name: string; force_https?: boolean; ssl_enabled?: boolean }

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

export default function SslPage() {
  const { t } = useTranslation()
  const qc = useQueryClient()
  const [manualDomainId, setManualDomainId] = useState<number | null>(null)
  const [manualCert, setManualCert] = useState('')
  const [manualKey, setManualKey] = useState('')

  const sslQ = useQuery({
    queryKey: ['ssl'],
    queryFn: async () => (await api.get('/ssl')).data as { certificates: Cert[] },
  })

  const domainsQ = useQuery({
    queryKey: ['domains', 'paginated'],
    queryFn: async () => (await api.get('/domains')).data,
  })

  const certs = sslQ.data?.certificates ?? []
  const domains: DomainOpt[] = domainsQ.data?.data ?? []

  const certByDomain = new Map<number, Cert>()
  for (const c of certs) {
    certByDomain.set(c.domain_id, c)
  }

  const invalidate = () => {
    qc.invalidateQueries({ queryKey: ['ssl'] })
    qc.invalidateQueries({ queryKey: ['domains'] })
  }

  const issueM = useMutation({
    mutationFn: async (id: number) => api.post(`/domains/${id}/ssl/issue`),
    onSuccess: () => {
      toast.success(t('ssl.issued'))
      invalidate()
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const renewM = useMutation({
    mutationFn: async (id: number) => api.post(`/domains/${id}/ssl/renew`),
    onSuccess: () => {
      toast.success(t('ssl.renewed'))
      invalidate()
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const revokeM = useMutation({
    mutationFn: async (id: number) => api.post(`/domains/${id}/ssl/revoke`),
    onSuccess: () => {
      toast.success(t('ssl.revoked'))
      invalidate()
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const settingsM = useMutation({
    mutationFn: async (vars: { id: number; auto_renew?: boolean; force_https?: boolean }) =>
      api.patch(`/domains/${vars.id}/ssl/settings`, {
        ...(vars.auto_renew !== undefined ? { auto_renew: vars.auto_renew } : {}),
        ...(vars.force_https !== undefined ? { force_https: vars.force_https } : {}),
      }),
    onSuccess: () => {
      toast.success(t('ssl.settings_saved'))
      invalidate()
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const manualM = useMutation({
    mutationFn: async (vars: { id: number; certificate: string; private_key: string }) =>
      api.post(`/domains/${vars.id}/ssl/manual`, {
        certificate: vars.certificate,
        private_key: vars.private_key,
      }),
    onSuccess: () => {
      toast.success(t('ssl.manual_uploaded'))
      setManualDomainId(null)
      setManualCert('')
      setManualKey('')
      invalidate()
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const daysUntil = (iso: string | null | undefined): number | null => {
    if (!iso) return null
    const ms = new Date(iso).getTime() - Date.now()
    return Math.ceil(ms / 86400000)
  }

  return (
    <div className="space-y-6">
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
        </div>
      </div>

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
                <th className="text-right px-4 py-2">{t('common.actions')}</th>
              </tr>
            </thead>
            <tbody>
              {domainsQ.isLoading || sslQ.isLoading ? (
                <tr>
                  <td colSpan={6} className="px-4 py-8 text-center text-gray-500">
                    {t('common.loading')}
                  </td>
                </tr>
              ) : (
                domains.map((d) => {
                  const c = certByDomain.get(d.id)
                  const hasSsl = Boolean(c?.status === 'active' || d.ssl_enabled)
                  const forceHttps = c?.domain?.force_https ?? d.force_https ?? true
                  const autoRenew = c?.auto_renew ?? false
                  const days = daysUntil(c?.expires_at)
                  const expiringSoon = days !== null && days >= 0 && days <= 30

                  return (
                    <tr key={d.id} className="border-t border-gray-100 dark:border-gray-800">
                      <td className="px-4 py-3 font-medium">{d.name}</td>
                      <td className="px-4 py-3 text-gray-600 dark:text-gray-400">
                        {c ? providerLabel(c.provider, t) : '—'}
                      </td>
                      <td className="px-4 py-3">
                        <span
                          className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${statusBadgeClass(c?.status)}`}
                        >
                          {c ? statusLabel(c.status, t) : t('ssl.no_cert')}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-gray-500">
                        {c?.expires_at ? (
                          <span className={expiringSoon ? 'text-amber-600 dark:text-amber-400' : ''}>
                            {new Date(c.expires_at).toLocaleString()}
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
                        <div className="space-y-2">
                          <label
                            className={`flex items-center gap-2 text-xs ${hasSsl ? 'cursor-pointer' : 'opacity-50'}`}
                            title={t('ssl.force_https_hint')}
                          >
                            <input
                              type="checkbox"
                              className="rounded border-gray-300"
                              checked={forceHttps}
                              disabled={!hasSsl || settingsM.isPending}
                              onChange={(e) =>
                                settingsM.mutate({ id: d.id, force_https: e.target.checked })
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
                              disabled={!c || settingsM.isPending}
                              onChange={(e) =>
                                settingsM.mutate({ id: d.id, auto_renew: e.target.checked })
                              }
                            />
                            <span>{t('ssl.auto_renew')}</span>
                          </label>
                        </div>
                      </td>
                      <td className="px-4 py-3 text-right">
                        <div className="flex flex-wrap justify-end gap-1">
                          <button
                            type="button"
                            className="btn-secondary text-xs py-1 px-2"
                            disabled={issueM.isPending}
                            onClick={() => issueM.mutate(d.id)}
                          >
                            {t('ssl.issue')}
                          </button>
                          <button
                            type="button"
                            className="btn-secondary text-xs py-1 px-2 inline-flex items-center gap-1"
                            disabled={!c || renewM.isPending}
                            onClick={() => renewM.mutate(d.id)}
                          >
                            <RefreshCw className="h-3 w-3" />
                            {t('ssl.renew')}
                          </button>
                          <button
                            type="button"
                            className="btn-secondary text-xs py-1 px-2 text-red-600 inline-flex items-center gap-1"
                            disabled={!c || revokeM.isPending}
                            onClick={() => {
                              if (window.confirm(t('ssl.confirm_revoke'))) revokeM.mutate(d.id)
                            }}
                          >
                            <ShieldOff className="h-3 w-3" />
                            {t('ssl.revoke')}
                          </button>
                          <button
                            type="button"
                            className="btn-secondary text-xs py-1 px-2"
                            onClick={() => setManualDomainId(d.id)}
                          >
                            {t('ssl.manual_upload')}
                          </button>
                        </div>
                      </td>
                    </tr>
                  )
                })
              )}
            </tbody>
          </table>
        </div>
        {!domainsQ.isLoading && domains.length === 0 && (
          <p className="p-6 text-center text-gray-500">{t('common.no_data')}</p>
        )}
      </div>

      {manualDomainId !== null && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="card w-full max-w-3xl bg-white p-6 dark:bg-gray-900">
            <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
              {t('ssl.manual_upload')}
            </h3>
            <div className="space-y-3">
              <div>
                <label className="label">{t('ssl.cert_pem')}</label>
                <textarea
                  className="input min-h-[140px] w-full font-mono text-xs"
                  value={manualCert}
                  onChange={(e) => setManualCert(e.target.value)}
                  placeholder="-----BEGIN CERTIFICATE-----"
                />
              </div>
              <div>
                <label className="label">{t('ssl.private_key_pem')}</label>
                <textarea
                  className="input min-h-[140px] w-full font-mono text-xs"
                  value={manualKey}
                  onChange={(e) => setManualKey(e.target.value)}
                  placeholder="-----BEGIN PRIVATE KEY-----"
                />
              </div>
            </div>
            <div className="mt-4 flex justify-end gap-2">
              <button
                type="button"
                className="btn-secondary"
                onClick={() => {
                  setManualDomainId(null)
                  setManualCert('')
                  setManualKey('')
                }}
              >
                {t('common.cancel')}
              </button>
              <button
                type="button"
                className="btn-primary"
                disabled={manualM.isPending || !manualCert.trim() || !manualKey.trim()}
                onClick={() => {
                  if (manualDomainId === null) return
                  manualM.mutate({
                    id: manualDomainId,
                    certificate: manualCert.trim(),
                    private_key: manualKey.trim(),
                  })
                }}
              >
                {t('common.save')}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
