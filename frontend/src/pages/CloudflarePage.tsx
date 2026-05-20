import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Cloud, Link2, Unlink, RefreshCw, Upload, Download, Trash2 } from 'lucide-react'
import toast from 'react-hot-toast'
import { Link } from 'react-router-dom'
import api from '../services/api'
import { useDomainsList } from '../hooks/useDomains'
import { useActivePluginSlugs } from '../hooks/useActivePlugins'

type CfStatus = {
  connected: boolean
  plugin_active?: boolean
  email?: string | null
}

type CfDnsRecord = {
  id: string
  type: string
  name: string
  content: string
  proxied?: boolean
  ttl?: number
}

type DomainCf = {
  linked: boolean
  zone_id?: string
  zone_name?: string
  ssl_mode?: string
  dns_records?: CfDnsRecord[]
}

export default function CloudflarePage() {
  const { t } = useTranslation()
  const qc = useQueryClient()
  const domainsQ = useDomainsList()
  const activePlugins = useActivePluginSlugs()
  const pluginActive = activePlugins.includes('integration-cloudflare')

  const [apiToken, setApiToken] = useState('')
  const [domainId, setDomainId] = useState<number | ''>('')
  const [sslMode, setSslMode] = useState('full')

  const statusQ = useQuery({
    queryKey: ['cloudflare-status'],
    queryFn: async () => (await api.get('/plugins/cloudflare/status')).data as CfStatus,
    enabled: pluginActive,
  })

  const domainCfQ = useQuery({
    queryKey: ['cloudflare-domain', domainId],
    enabled: pluginActive && domainId !== '' && Boolean(statusQ.data?.connected),
    queryFn: async () => (await api.get(`/domains/${domainId}/cloudflare`)).data as DomainCf,
  })

  const connected = Boolean(statusQ.data?.connected)
  const linked = Boolean(domainCfQ.data?.linked)

  useEffect(() => {
    if (domainCfQ.data?.ssl_mode) setSslMode(domainCfQ.data.ssl_mode)
  }, [domainCfQ.data?.ssl_mode])

  const connectM = useMutation({
    mutationFn: async () => api.post('/plugins/cloudflare/connect', { api_token: apiToken }),
    onSuccess: () => {
      toast.success(t('cloudflare.connected'))
      setApiToken('')
      qc.invalidateQueries({ queryKey: ['cloudflare-status'] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const disconnectM = useMutation({
    mutationFn: async () => api.delete('/plugins/cloudflare/disconnect'),
    onSuccess: () => {
      toast.success(t('cloudflare.disconnected'))
      qc.invalidateQueries({ queryKey: ['cloudflare-status'] })
      qc.invalidateQueries({ queryKey: ['cloudflare-domain'] })
    },
  })

  const linkM = useMutation({
    mutationFn: async () => api.post(`/domains/${domainId}/cloudflare/link`, {}),
    onSuccess: () => {
      toast.success(t('cloudflare.domain_linked'))
      qc.invalidateQueries({ queryKey: ['cloudflare-domain', domainId] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const unlinkM = useMutation({
    mutationFn: async () => api.delete(`/domains/${domainId}/cloudflare/unlink`),
    onSuccess: () => {
      toast.success(t('cloudflare.domain_unlinked'))
      qc.invalidateQueries({ queryKey: ['cloudflare-domain', domainId] })
    },
  })

  const sslM = useMutation({
    mutationFn: async () => api.post(`/domains/${domainId}/cloudflare/ssl`, { mode: sslMode }),
    onSuccess: () => toast.success(t('cloudflare.ssl_updated')),
  })

  const pushM = useMutation({
    mutationFn: async () => api.post(`/domains/${domainId}/cloudflare/dns/push`),
    onSuccess: () => {
      toast.success(t('cloudflare.dns_pushed'))
      qc.invalidateQueries({ queryKey: ['cloudflare-domain', domainId] })
    },
  })

  const pullM = useMutation({
    mutationFn: async () => api.post(`/domains/${domainId}/cloudflare/dns/pull`),
    onSuccess: () => toast.success(t('cloudflare.dns_pulled')),
  })

  const purgeM = useMutation({
    mutationFn: async () => api.post(`/domains/${domainId}/cloudflare/purge`),
    onSuccess: () => toast.success(t('cloudflare.cache_purged')),
  })

  const proxiedM = useMutation({
    mutationFn: async ({ id, proxied }: { id: string; proxied: boolean }) =>
      api.post(`/domains/${domainId}/cloudflare/dns/proxied`, { record_id: id, proxied }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['cloudflare-domain', domainId] }),
  })

  if (!pluginActive) {
    return (
      <div className="max-w-xl card p-6 space-y-3">
        <h1 className="text-xl font-bold text-gray-900 dark:text-white">{t('cloudflare.title')}</h1>
        <p className="text-sm text-gray-600 dark:text-gray-300">{t('cloudflare.plugin_required')}</p>
        <Link to="/plugins" className="btn-primary inline-block">
          {t('cloudflare.go_plugins')}
        </Link>
      </div>
    )
  }

  const records = domainCfQ.data?.dns_records ?? []

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Cloud className="h-8 w-8 text-orange-500" />
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{t('cloudflare.title')}</h1>
          <p className="text-gray-500 dark:text-gray-400 text-sm">{t('cloudflare.subtitle')}</p>
        </div>
      </div>

      <div className="card p-4 space-y-4">
        <h2 className="font-semibold text-gray-900 dark:text-white">{t('cloudflare.connection')}</h2>
        {connected ? (
          <div className="flex flex-wrap items-center gap-3">
            <span className="text-sm text-emerald-600 dark:text-emerald-400">{t('cloudflare.connected_label')}</span>
            {statusQ.data?.email && <span className="text-sm text-gray-500">{statusQ.data.email}</span>}
            <button type="button" className="btn-danger text-sm" onClick={() => disconnectM.mutate()} disabled={disconnectM.isPending}>
              {t('cloudflare.disconnect')}
            </button>
          </div>
        ) : (
          <div className="flex flex-wrap gap-3 items-end">
            <div className="flex-1 min-w-[280px]">
              <label className="label">{t('cloudflare.api_token')}</label>
              <input
                type="password"
                className="input w-full"
                value={apiToken}
                onChange={(e) => setApiToken(e.target.value)}
                placeholder={t('cloudflare.api_token_hint')}
              />
              <p className="text-xs text-gray-500 mt-1">{t('cloudflare.token_scopes')}</p>
            </div>
            <button type="button" className="btn-primary" disabled={!apiToken.trim() || connectM.isPending} onClick={() => connectM.mutate()}>
              {t('cloudflare.connect')}
            </button>
          </div>
        )}
      </div>

      {connected && (
        <div className="card p-4 space-y-4">
          <div className="flex flex-wrap gap-4 items-end">
            <div>
              <label className="label">{t('cloudflare.domain')}</label>
              <select className="input min-w-[240px]" value={domainId} onChange={(e) => setDomainId(e.target.value ? Number(e.target.value) : '')}>
                <option value="">{t('common.select')}</option>
                {(domainsQ.data ?? []).map((d) => (
                  <option key={d.id} value={d.id}>{d.name}</option>
                ))}
              </select>
            </div>
            {domainId !== '' && (
              linked ? (
                <div className="flex flex-wrap gap-2 items-center">
                  <span className="text-sm text-gray-600 dark:text-gray-300">
                    {t('cloudflare.zone')}: {domainCfQ.data?.zone_name}
                  </span>
                  <button type="button" className="btn-secondary text-sm" onClick={() => unlinkM.mutate()} disabled={unlinkM.isPending}>
                    <Unlink className="h-4 w-4 inline mr-1" />
                    {t('cloudflare.unlink')}
                  </button>
                </div>
              ) : (
                <button type="button" className="btn-primary text-sm" onClick={() => linkM.mutate()} disabled={linkM.isPending}>
                  <Link2 className="h-4 w-4 inline mr-1" />
                  {t('cloudflare.auto_link')}
                </button>
              )
            )}
          </div>

          {linked && (
            <>
              <div className="flex flex-wrap gap-3 items-end border-t border-gray-100 dark:border-gray-800 pt-4">
                <div>
                  <label className="label">{t('cloudflare.ssl_mode')}</label>
                  <select className="input" value={sslMode} onChange={(e) => setSslMode(e.target.value)}>
                    {['off', 'flexible', 'full', 'strict'].map((m) => (
                      <option key={m} value={m}>{m}</option>
                    ))}
                  </select>
                </div>
                <button type="button" className="btn-secondary text-sm" onClick={() => sslM.mutate()} disabled={sslM.isPending}>
                  {t('cloudflare.apply_ssl')}
                </button>
                <button type="button" className="btn-secondary text-sm" onClick={() => pushM.mutate()} disabled={pushM.isPending}>
                  <Upload className="h-4 w-4 inline mr-1" />
                  {t('cloudflare.dns_push')}
                </button>
                <button type="button" className="btn-secondary text-sm" onClick={() => pullM.mutate()} disabled={pullM.isPending}>
                  <Download className="h-4 w-4 inline mr-1" />
                  {t('cloudflare.dns_pull')}
                </button>
                <button type="button" className="btn-secondary text-sm" onClick={() => purgeM.mutate()} disabled={purgeM.isPending}>
                  <Trash2 className="h-4 w-4 inline mr-1" />
                  {t('cloudflare.purge_cache')}
                </button>
                <button type="button" className="btn-secondary text-sm" onClick={() => domainCfQ.refetch()}>
                  <RefreshCw className="h-4 w-4 inline mr-1" />
                  {t('common.refresh')}
                </button>
              </div>

              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="text-left text-gray-500 border-b border-gray-100 dark:border-gray-800">
                      <th className="py-2 pr-3">{t('cloudflare.col_type')}</th>
                      <th className="py-2 pr-3">{t('cloudflare.col_name')}</th>
                      <th className="py-2 pr-3">{t('cloudflare.col_content')}</th>
                      <th className="py-2">{t('cloudflare.col_proxy')}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {records.map((r) => (
                      <tr key={r.id} className="border-b border-gray-50 dark:border-gray-900">
                        <td className="py-2 pr-3 font-mono">{r.type}</td>
                        <td className="py-2 pr-3 font-mono text-xs">{r.name}</td>
                        <td className="py-2 pr-3 font-mono text-xs break-all max-w-md">{r.content}</td>
                        <td className="py-2">
                          {['A', 'AAAA', 'CNAME'].includes(r.type) ? (
                            <button
                              type="button"
                              className={`text-xs px-2 py-1 rounded ${r.proxied ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-200' : 'bg-gray-100 text-gray-600 dark:bg-gray-800'}`}
                              onClick={() => proxiedM.mutate({ id: r.id, proxied: !r.proxied })}
                            >
                              {r.proxied ? t('cloudflare.proxied_on') : t('cloudflare.proxied_off')}
                            </button>
                          ) : (
                            '—'
                          )}
                        </td>
                      </tr>
                    ))}
                    {records.length === 0 && (
                      <tr><td colSpan={4} className="py-4 text-gray-500">{t('cloudflare.no_records')}</td></tr>
                    )}
                  </tbody>
                </table>
              </div>
            </>
          )}
        </div>
      )}
    </div>
  )
}
