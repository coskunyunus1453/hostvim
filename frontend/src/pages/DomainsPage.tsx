import { useEffect, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import api from '../services/api'
import toast from 'react-hot-toast'
import clsx from 'clsx'
import {
  BarChart2,
  ExternalLink,
  FileText,
  FolderOpen,
  Globe,
  Loader2,
  Layers,
  Plus,
  Search,
  ServerCog,
  Settings,
  Shield,
  ShieldCheck,
  Trash2,
} from 'lucide-react'
import {
  Bar,
  BarChart,
  CartesianGrid,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import DomainDeleteConfirmModal from '../components/domains/DomainDeleteConfirmModal'
import DomainQuickSettingsModal, {
  type DomainQuickRow,
} from '../components/domains/DomainQuickSettingsModal'
import { safeDomainUrl } from '../lib/urlSafety'
import { tokenHasAbility } from '../lib/abilities'
import { useAuthStore } from '../store/authStore'

const PHP_OPTIONS = ['7.4', '8.0', '8.1', '8.2', '8.3', '8.4'] as const

const DOMAIN_HOST_RE =
  /^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+$/i

function isValidDomainHostname(name: string): boolean {
  const n = name.trim().toLowerCase()
  return n.length > 0 && n.length <= 255 && DOMAIN_HOST_RE.test(n)
}

type DomainRow = {
  id: number
  name: string
  php_version: string
  server_type: string
  status: string
  ssl_enabled?: boolean
  site_subdomains?: SiteSubdomainRow[]
  site_domain_aliases?: SiteDomainAliasRow[]
}

type SiteDomainAliasRow = {
  id: number
  hostname: string
}

type SiteSubdomainRow = {
  id: number
  hostname: string
  path_segment: string
  document_root?: string
  php_version?: string
  server_type?: string
  ssl_enabled?: boolean
}

function buildSubdomainHostname(parent: string, prefix: string): string {
  const p = prefix.trim().toLowerCase()
  const par = parent.trim().toLowerCase()
  if (!p || !par) return ''
  if (p.endsWith(`.${par}`)) return p
  return `${p}.${par}`
}

type DomainLogEntry = {
  type: string
  path: string
  exists: boolean
  content: string
  error?: string
}

type DomainHealthRow = {
  domain_id: number
  name: string
  score: number
  grade: 'excellent' | 'good' | 'warning' | 'critical'
  reasons: string[]
}

type SiteTraffic = {
  source?: string
  log_path?: string
  lines_analyzed?: number
  requests_total?: number
  bytes_total?: number
  status_2xx?: number
  status_3xx?: number
  status_4xx?: number
  status_5xx?: number
  hourly_requests?: { hour: string; count: number }[]
  requests_per_minute?: number
  window_start?: string
  window_end?: string
}

function formatTrafficBytes(n?: number): string {
  if (n == null || !Number.isFinite(n) || n < 0) return '—'
  if (n < 1024) return `${n} B`
  if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`
  if (n < 1024 * 1024 * 1024) return `${(n / (1024 * 1024)).toFixed(1)} MB`
  return `${(n / (1024 * 1024 * 1024)).toFixed(2)} GB`
}

type Busy = {
  php?: boolean
  server?: boolean
  ssl?: boolean
  status?: boolean
}

type SslProgress = {
  pct: number
}

export default function DomainsPage() {
  const { t } = useTranslation()
  const qc = useQueryClient()
  const [search, setSearch] = useState('')
  const [showAdd, setShowAdd] = useState(false)
  const [addMode, setAddMode] = useState<'domain' | 'subdomain' | 'alias'>('domain')
  const [subParentId, setSubParentId] = useState<number | ''>('')
  const [subPrefix, setSubPrefix] = useState('')
  const [subPhpVersion, setSubPhpVersion] = useState('8.2')
  const [aliasParentId, setAliasParentId] = useState<number | ''>('')
  const [aliasHostname, setAliasHostname] = useState('')
  const [issueLeOnCreate, setIssueLeOnCreate] = useState(true)
  const [deleteTarget, setDeleteTarget] = useState<DomainRow | null>(null)
  const [quickTarget, setQuickTarget] = useState<DomainQuickRow | null>(null)
  const [busy, setBusy] = useState<Record<number, Busy>>({})
  const [sslProgress, setSslProgress] = useState<Record<number, SslProgress>>({})
  const [logTarget, setLogTarget] = useState<DomainRow | null>(null)
  const [trafficTarget, setTrafficTarget] = useState<DomainRow | null>(null)
  const [logLines, setLogLines] = useState(200)
  const [page, setPage] = useState(1)
  const [deletingSubId, setDeletingSubId] = useState<number | null>(null)
  const [busySubSsl, setBusySubSsl] = useState<Record<number, boolean>>({})
  const sslTimers = useRef<Record<number, number>>({})
  const abilities = useAuthStore((s) => s.user?.abilities)
  const canWrite = tokenHasAbility(abilities, 'domains:write')
  const canSsl = tokenHasAbility(abilities, 'ssl:write')

  const domainsQ = useQuery({
    queryKey: ['domains', 'paginated', page],
    queryFn: async () =>
      (await api.get('/domains', { params: { page, per_page: 50 } })).data,
  })
  const switchableServersQ = useQuery({
    queryKey: ['domains', 'switchable-server-types'],
    queryFn: async () =>
      (await api.get('/domains/switchable-server-types')).data as { server_types: string[] },
    staleTime: 120_000,
  })
  const switchableServerTypes = switchableServersQ.data?.server_types ?? [
    'nginx',
    'apache',
    'openlitespeed',
  ]
  const serverTypeSelectable = (type: string, current: string) =>
    switchableServerTypes.includes(type) || type === current
  const healthSitesQ = useQuery({
    queryKey: ['monitoring-health-sites', 50],
    queryFn: async () =>
      (await api.get('/monitoring/health/sites', { params: { limit: 50 } })).data as {
        items: DomainHealthRow[]
      },
    staleTime: 20_000,
    refetchInterval: 30_000,
  })

  const setBusyFlag = (domainId: number, key: keyof Busy, value: boolean) => {
    setBusy((prev) => ({
      ...prev,
      [domainId]: { ...(prev[domainId] ?? {}), [key]: value },
    }))
  }

  const startSslProgress = (domainId: number) => {
    setSslProgress((prev) => ({ ...prev, [domainId]: { pct: 8 } }))
    if (sslTimers.current[domainId]) {
      window.clearInterval(sslTimers.current[domainId])
    }
    sslTimers.current[domainId] = window.setInterval(() => {
      setSslProgress((prev) => {
        const cur = prev[domainId]?.pct ?? 8
        const next = Math.min(92, cur + Math.max(1, Math.round((100 - cur) / 18)))
        return { ...prev, [domainId]: { pct: next } }
      })
    }, 700)
  }

  const finishSslProgress = (domainId: number, ok: boolean) => {
    if (sslTimers.current[domainId]) {
      window.clearInterval(sslTimers.current[domainId])
      delete sslTimers.current[domainId]
    }
    setSslProgress((prev) => ({ ...prev, [domainId]: { pct: ok ? 100 : 0 } }))
    window.setTimeout(() => {
      setSslProgress((prev) => {
        const next = { ...prev }
        delete next[domainId]
        return next
      })
    }, ok ? 1200 : 500)
  }

  useEffect(() => {
    return () => {
      Object.values(sslTimers.current).forEach((id) => window.clearInterval(id))
      sslTimers.current = {}
    }
  }, [])

  type CreateDomainResponse = {
    message?: string
    ssl?: {
      ok: boolean
      message?: string
      diagnostics?: { key: string; ok: boolean; message: string }[]
    } | null
  }

  const openAddModal = (mode: 'domain' | 'subdomain' | 'alias' = 'domain', parentId?: number) => {
    setAddMode(mode)
    setSubParentId(parentId ?? '')
    setSubPrefix('')
    setSubPhpVersion('8.2')
    setAliasParentId(parentId ?? '')
    setAliasHostname('')
    if (mode === 'subdomain' && parentId) {
      const parent = list.find((d) => d.id === parentId)
      if (parent) setSubPhpVersion(parent.php_version || '8.2')
    }
    setShowAdd(true)
  }

  const createSubM = useMutation({
    mutationFn: async (payload: { parentId: number; prefix: string; php_version: string }) => {
      const { data } = await api.post(`/domains/${payload.parentId}/subdomains`, {
        prefix: payload.prefix,
        php_version: payload.php_version,
      })
      return data
    },
    onSuccess: () => {
      toast.success(t('domains.subdomain_created'))
      qc.invalidateQueries({ queryKey: ['domains'] })
      setShowAdd(false)
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const deleteSubM = useMutation({
    mutationFn: async (vars: { parentId: number; path_segment: string; subId: number }) =>
      api.delete(`/domains/${vars.parentId}/subdomains`, { data: { path_segment: vars.path_segment } }),
    onMutate: (vars) => setDeletingSubId(vars.subId),
    onSuccess: () => {
      toast.success(t('domains.subdomain_deleted'))
      qc.invalidateQueries({ queryKey: ['domains'] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
    onSettled: () => setDeletingSubId(null),
  })

  const createAliasM = useMutation({
    mutationFn: async (payload: { parentId: number; hostname: string }) =>
      api.post(`/domains/${payload.parentId}/aliases`, { hostname: payload.hostname }),
    onSuccess: () => {
      toast.success(t('domains.alias_created'))
      qc.invalidateQueries({ queryKey: ['domains'] })
      setShowAdd(false)
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const deleteAliasM = useMutation({
    mutationFn: async (vars: { parentId: number; hostname: string }) =>
      api.delete(`/domains/${vars.parentId}/aliases`, { data: { hostname: vars.hostname } }),
    onSuccess: () => {
      toast.success(t('domains.alias_deleted'))
      qc.invalidateQueries({ queryKey: ['domains'] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const createM = useMutation({
    mutationFn: async (payload: {
      name: string
      php_version: string
      server_type: string
      issue_lets_encrypt: boolean
      lets_encrypt_email?: string
    }) => {
      const { data } = await api.post<CreateDomainResponse>('/domains', payload)
      return data
    },
    onSuccess: (data) => {
      toast.success(t('domains.created'))
      if (data?.ssl) {
        if (data.ssl.ok) {
          toast.success(data.ssl.message?.trim() || t('domains.ssl_create_success'))
        } else {
          const detail = data.ssl.message?.trim() || t('domains.ssl_create_unknown')
          toast.error(`${t('domains.ssl_create_failed_prefix')}: ${detail}`, { duration: 8000 })
        }
      }
      qc.invalidateQueries({ queryKey: ['domains'] })
      setShowAdd(false)
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const phpM = useMutation({
    mutationFn: async (vars: { id: number; php_version: string }) =>
      api.post(`/domains/${vars.id}/php`, { php_version: vars.php_version }),
    onMutate: (vars) => setBusyFlag(vars.id, 'php', true),
    onSuccess: (_, vars) => {
      toast.success(t('domains.php_switched'))
      qc.invalidateQueries({ queryKey: ['domains'] })
      setBusyFlag(vars.id, 'php', false)
    },
    onError: (err: unknown, vars) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
      setBusyFlag(vars.id, 'php', false)
    },
  })

  const serverM = useMutation({
    mutationFn: async (vars: { id: number; server_type: string }) =>
      api.post(`/domains/${vars.id}/server`, { server_type: vars.server_type }),
    onMutate: (vars) => setBusyFlag(vars.id, 'server', true),
    onSuccess: (_, vars) => {
      toast.success(t('domains.server_switched'))
      qc.invalidateQueries({ queryKey: ['domains'] })
      setBusyFlag(vars.id, 'server', false)
    },
    onError: (err: unknown, vars) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
      setBusyFlag(vars.id, 'server', false)
    },
  })

  const statusM = useMutation({
    mutationFn: async (vars: { id: number; status: 'active' | 'suspended' }) =>
      api.post(`/domains/${vars.id}/status`, { status: vars.status }),
    onMutate: (vars) => setBusyFlag(vars.id, 'status', true),
    onSuccess: (_, vars) => {
      toast.success(t('domains.status_updated'))
      qc.invalidateQueries({ queryKey: ['domains'] })
      setBusyFlag(vars.id, 'status', false)
    },
    onError: (err: unknown, vars) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
      setBusyFlag(vars.id, 'status', false)
    },
  })

  const sslIssueM = useMutation({
    mutationFn: async (vars: { id: number; subdomain_id?: number }) =>
      api.post(
        `/domains/${vars.id}/ssl/issue`,
        vars.subdomain_id ? { subdomain_id: vars.subdomain_id } : {},
      ),
    onMutate: (vars) => {
      if (vars.subdomain_id) {
        setBusySubSsl((prev) => ({ ...prev, [vars.subdomain_id!]: true }))
      } else {
        setBusyFlag(vars.id, 'ssl', true)
        startSslProgress(vars.id)
      }
    },
    onSuccess: (_, vars) => {
      toast.success(t('ssl.issued'))
      qc.invalidateQueries({ queryKey: ['domains'] })
      if (vars.subdomain_id) {
        setBusySubSsl((prev) => ({ ...prev, [vars.subdomain_id!]: false }))
      } else {
        setBusyFlag(vars.id, 'ssl', false)
        finishSslProgress(vars.id, true)
      }
    },
    onError: (err: unknown, vars) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
      if (vars.subdomain_id) {
        setBusySubSsl((prev) => ({ ...prev, [vars.subdomain_id!]: false }))
      } else {
        setBusyFlag(vars.id, 'ssl', false)
        finishSslProgress(vars.id, false)
      }
    },
  })

  const list: DomainRow[] = domainsQ.data?.data ?? []
  const total = (domainsQ.data?.total as number | undefined) ?? list.length
  const lastPage = (domainsQ.data?.last_page as number | undefined) ?? 1
  const searchQ = search.toLowerCase()
  const filtered = list.filter((d) => {
    if (d.name.toLowerCase().includes(searchQ)) return true
    return (d.site_subdomains ?? []).some((s) => s.hostname.toLowerCase().includes(searchQ))
      || (d.site_domain_aliases ?? []).some((a) => a.hostname.toLowerCase().includes(searchQ))
  })
  const subParentDomain = list.find((d) => d.id === subParentId)
  const subPreview =
    subParentDomain && subPrefix.trim()
      ? buildSubdomainHostname(subParentDomain.name, subPrefix)
      : ''
  const healthByDomain = new Map<number, DomainHealthRow>(
    (healthSitesQ.data?.items ?? []).map((it) => [it.domain_id, it]),
  )
  const logsQ = useQuery({
    queryKey: ['domain-logs', logTarget?.id ?? 0, logLines],
    enabled: !!logTarget?.id,
    queryFn: async () => {
      const { data } = await api.get<{ logs: DomainLogEntry[] }>(
        `/domains/${logTarget?.id}/logs?lines=${logLines}`,
      )
      return data
    },
  })

  const trafficQ = useQuery({
    queryKey: ['domain-traffic', trafficTarget?.id ?? 0],
    enabled: !!trafficTarget?.id,
    queryFn: async () => {
      const { data } = await api.get<{ domain: string; traffic: SiteTraffic }>(
        `/domains/${trafficTarget?.id}/traffic`,
      )
      return data
    },
  })

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{t('domains.title')}</h1>
          <p className="mt-1 text-gray-500 dark:text-gray-400">
            {total} {t('nav.domains').toLowerCase()}
          </p>
        </div>
        <button
          type="button"
          className="btn-primary flex items-center gap-2"
          disabled={!canWrite}
          onClick={() => openAddModal('domain')}
        >
          <Plus className="h-4 w-4" />
          {t('domains.add')}
        </button>
      </div>

      <DomainDeleteConfirmModal
        open={!!deleteTarget}
        domain={deleteTarget}
        onClose={() => setDeleteTarget(null)}
        onDeleted={() => setDeleteTarget(null)}
      />

      <DomainQuickSettingsModal
        open={!!quickTarget}
        domain={quickTarget}
        onClose={() => setQuickTarget(null)}
      />

      {trafficTarget && (
        <div className="fixed inset-0 z-[56] flex items-center justify-center bg-black/50 p-4">
          <div className="card max-h-[92vh] w-full max-w-4xl overflow-y-auto bg-white p-6 dark:bg-gray-900">
            <div className="mb-6 flex flex-wrap items-start justify-between gap-3">
              <div>
                <h2 className="text-lg font-semibold text-gray-900 dark:text-white">{t('domains.traffic_title')}</h2>
                <p className="font-mono text-xs text-gray-500">{trafficTarget.name}</p>
                <p className="mt-1 max-w-xl text-xs text-gray-500 dark:text-gray-400">{t('domains.traffic_hint')}</p>
              </div>
              <div className="flex items-center gap-2">
                <button type="button" className="btn-secondary text-sm" onClick={() => void trafficQ.refetch()}>
                  {t('domains.logs_refresh')}
                </button>
                <button type="button" className="btn-secondary text-sm" onClick={() => setTrafficTarget(null)}>
                  {t('common.cancel')}
                </button>
              </div>
            </div>

            {trafficQ.isError && (
              <p className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-200">
                {(trafficQ.error as { response?: { data?: { message?: string } } })?.response?.data?.message ??
                  t('domains.traffic_load_error')}
              </p>
            )}

            {trafficQ.isLoading && <p className="py-6 text-sm text-gray-500">{t('common.loading')}</p>}

            {!trafficQ.isLoading && trafficQ.data && (
              <>
                {(!trafficQ.data.traffic?.lines_analyzed || trafficQ.data.traffic.lines_analyzed === 0) && (
                  <p className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200">
                    {t('domains.traffic_no_log')}
                  </p>
                )}

                {!!trafficQ.data.traffic?.lines_analyzed && trafficQ.data.traffic.lines_analyzed > 0 && (
                  <>
                    <div className="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                      <div className="rounded-xl border border-gray-200 bg-gradient-to-br from-primary-50/80 to-white p-4 dark:border-gray-700 dark:from-primary-950/30 dark:to-gray-900">
                        <p className="text-[11px] font-medium uppercase tracking-wide text-gray-500">{t('domains.traffic_requests')}</p>
                        <p className="mt-1 text-2xl font-bold tabular-nums text-gray-900 dark:text-white">
                          {trafficQ.data.traffic.requests_total ?? 0}
                        </p>
                      </div>
                      <div className="rounded-xl border border-gray-200 bg-gradient-to-br from-violet-50/80 to-white p-4 dark:border-gray-700 dark:from-violet-950/25 dark:to-gray-900">
                        <p className="text-[11px] font-medium uppercase tracking-wide text-gray-500">{t('domains.traffic_bytes')}</p>
                        <p className="mt-1 text-2xl font-bold tabular-nums text-gray-900 dark:text-white">
                          {formatTrafficBytes(trafficQ.data.traffic.bytes_total)}
                        </p>
                      </div>
                      <div className="rounded-xl border border-gray-200 bg-gradient-to-br from-emerald-50/80 to-white p-4 dark:border-gray-700 dark:from-emerald-950/25 dark:to-gray-900">
                        <p className="text-[11px] font-medium uppercase tracking-wide text-gray-500">{t('domains.traffic_rpm')}</p>
                        <p className="mt-1 text-2xl font-bold tabular-nums text-gray-900 dark:text-white">
                          {(trafficQ.data.traffic.requests_per_minute ?? 0).toFixed(1)}
                        </p>
                      </div>
                      <div className="rounded-xl border border-gray-200 bg-gray-50/90 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                        <p className="text-[11px] font-medium uppercase tracking-wide text-gray-500">{t('domains.traffic_source')}</p>
                        <p className="mt-1 font-mono text-sm text-gray-800 dark:text-gray-200">
                          {trafficQ.data.traffic.source ?? '—'}
                        </p>
                        <p className="mt-1 truncate font-mono text-[10px] text-gray-500" title={trafficQ.data.traffic.log_path}>
                          {trafficQ.data.traffic.log_path ?? ''}
                        </p>
                      </div>
                    </div>

                    <div className="mb-6 flex flex-wrap gap-2">
                      {[
                        { k: '2xx', v: trafficQ.data.traffic.status_2xx ?? 0, c: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200', label: t('domains.traffic_status_2xx') },
                        { k: '3xx', v: trafficQ.data.traffic.status_3xx ?? 0, c: 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200', label: t('domains.traffic_status_3xx') },
                        { k: '4xx', v: trafficQ.data.traffic.status_4xx ?? 0, c: 'bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-200', label: t('domains.traffic_status_4xx') },
                        { k: '5xx', v: trafficQ.data.traffic.status_5xx ?? 0, c: 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200', label: t('domains.traffic_status_5xx') },
                      ].map((x) => (
                        <span key={x.k} className={`inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold ${x.c}`}>
                          {x.label}: {x.v}
                        </span>
                      ))}
                    </div>

                    {(trafficQ.data.traffic.hourly_requests?.length ?? 0) > 0 && (
                      <div className="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                        <h3 className="mb-3 text-sm font-semibold text-gray-900 dark:text-white">{t('domains.traffic_hourly')}</h3>
                        <div className="h-[240px]">
                          <ResponsiveContainer width="100%" height="100%">
                            <BarChart
                              data={(trafficQ.data.traffic.hourly_requests ?? []).map((h) => ({
                                label: h.hour.slice(5, 16),
                                count: h.count,
                              }))}
                              margin={{ top: 8, right: 8, left: 0, bottom: 0 }}
                            >
                              <CartesianGrid strokeDasharray="3 3" className="stroke-gray-200 dark:stroke-gray-700" />
                              <XAxis dataKey="label" tick={{ fontSize: 10 }} />
                              <YAxis width={36} tick={{ fontSize: 10 }} />
                              <Tooltip
                                contentStyle={{ borderRadius: 12 }}
                                formatter={(v: number) => [v, t('domains.traffic_requests')]}
                              />
                              <Bar dataKey="count" fill="#6366f1" radius={[6, 6, 0, 0]} name={t('domains.traffic_requests')} />
                            </BarChart>
                          </ResponsiveContainer>
                        </div>
                      </div>
                    )}
                  </>
                )}
              </>
            )}
          </div>
        </div>
      )}

      {logTarget && (
        <div className="fixed inset-0 z-[55] flex items-center justify-center bg-black/50 p-4">
          <div className="card max-h-[90vh] w-full max-w-6xl overflow-y-auto bg-white p-5 dark:bg-gray-900">
            <div className="mb-4 flex items-center justify-between gap-2">
              <div>
                <h2 className="text-lg font-semibold text-gray-900 dark:text-white">{t('domains.logs_title')}</h2>
                <p className="font-mono text-xs text-gray-500">{logTarget.name}</p>
              </div>
              <div className="flex items-center gap-2">
                <select
                  className="input h-9 text-sm"
                  value={logLines}
                  onChange={(e) => setLogLines(Number(e.target.value))}
                >
                  <option value={100}>100</option>
                  <option value={200}>200</option>
                  <option value={500}>500</option>
                </select>
                <button type="button" className="btn-secondary" onClick={() => void logsQ.refetch()}>
                  {t('domains.logs_refresh')}
                </button>
                <button type="button" className="btn-secondary" onClick={() => setLogTarget(null)}>
                  {t('common.cancel')}
                </button>
              </div>
            </div>

            {logsQ.isError && (
              <p className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-200">
                {(logsQ.error as { response?: { data?: { message?: string } } })?.response?.data?.message ??
                  t('domains.logs_load_error')}
              </p>
            )}

            {logsQ.isLoading && <p className="py-4 text-sm text-gray-500">{t('common.loading')}</p>}
            {!logsQ.isLoading && (logsQ.data?.logs ?? []).length === 0 && (
              <p className="py-4 text-sm text-gray-500">{t('domains.logs_empty')}</p>
            )}

            <div className="grid gap-4 lg:grid-cols-2">
              {(logsQ.data?.logs ?? []).map((entry) => (
                <div key={entry.type} className="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                  <div className="flex items-center justify-between bg-gray-50 px-3 py-2 dark:bg-gray-800/70">
                    <div>
                      <p className="text-xs font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-300">
                        {entry.type}
                      </p>
                      <p className="text-[11px] text-gray-500">{entry.path}</p>
                    </div>
                    {!entry.exists && <span className="text-xs text-amber-600">{t('domains.logs_not_found')}</span>}
                  </div>
                  <pre className="max-h-[360px] overflow-auto bg-black p-3 text-xs text-green-200 whitespace-pre-wrap">
                    {entry.error
                      ? `${t('domains.logs_read_error')}: ${entry.error}`
                      : entry.content?.trim() || t('domains.logs_no_content')}
                  </pre>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {showAdd && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="card max-w-md w-full space-y-4 p-6 bg-white dark:bg-gray-900">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
              {addMode === 'domain'
                ? t('domains.new_title')
                : addMode === 'subdomain'
                  ? t('domains.subdomain_new_title')
                  : t('domains.alias_new_title')}
            </h2>

            <div className="flex gap-1 rounded-lg border border-gray-200 p-1 dark:border-gray-700">
              <button
                type="button"
                className={clsx(
                  'flex-1 rounded-md px-2 py-2 text-xs font-medium transition-colors sm:text-sm',
                  addMode === 'domain'
                    ? 'bg-primary-600 text-white'
                    : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800',
                )}
                onClick={() => setAddMode('domain')}
              >
                {t('domains.add_mode_domain')}
              </button>
              <button
                type="button"
                className={clsx(
                  'flex-1 rounded-md px-2 py-2 text-xs font-medium transition-colors sm:text-sm',
                  addMode === 'subdomain'
                    ? 'bg-primary-600 text-white'
                    : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800',
                  list.length === 0 && 'cursor-not-allowed opacity-50',
                )}
                disabled={list.length === 0}
                onClick={() => list.length > 0 && setAddMode('subdomain')}
              >
                {t('domains.add_mode_subdomain')}
              </button>
              <button
                type="button"
                className={clsx(
                  'flex-1 rounded-md px-2 py-2 text-xs font-medium transition-colors sm:text-sm',
                  addMode === 'alias'
                    ? 'bg-primary-600 text-white'
                    : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800',
                  list.length === 0 && 'cursor-not-allowed opacity-50',
                )}
                disabled={list.length === 0}
                onClick={() => list.length > 0 && setAddMode('alias')}
              >
                {t('domains.add_mode_alias')}
              </button>
            </div>

            {addMode === 'domain' ? (
            <form
              className="space-y-3"
              onSubmit={(ev) => {
                ev.preventDefault()
                const fd = new FormData(ev.currentTarget)
                const name = String(fd.get('name') || '').trim()
                if (!isValidDomainHostname(name)) {
                  toast.error(t('domains.invalid_hostname'))
                  return
                }
                const emailRaw = String(fd.get('lets_encrypt_email') || '').trim()
                createM.mutate({
                  name,
                  php_version: String(fd.get('php_version') || '8.2'),
                  server_type: String(fd.get('server_type') || 'nginx'),
                  issue_lets_encrypt: issueLeOnCreate,
                  lets_encrypt_email: emailRaw !== '' ? emailRaw : undefined,
                })
              }}
            >
              <div>
                <label className="label">{t('domains.name')}</label>
                <input name="name" className="input w-full" required placeholder="ornek.local" />
              </div>
              <div>
                <label className="label">{t('domains.php_version')}</label>
                <select name="php_version" className="input w-full" defaultValue="8.2">
                  {PHP_OPTIONS.map((v) => (
                    <option key={v} value={v}>
                      PHP {v}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="label">{t('domains.server_type')}</label>
                <select name="server_type" className="input w-full" defaultValue={switchableServerTypes[0] ?? 'nginx'}>
                  <option value="nginx" disabled={!switchableServerTypes.includes('nginx')}>
                    nginx
                  </option>
                  <option value="apache" disabled={!switchableServerTypes.includes('apache')}>
                    Apache
                  </option>
                  <option
                    value="openlitespeed"
                    disabled={!switchableServerTypes.includes('openlitespeed')}
                  >
                    {t('domains.server_openlitespeed')}
                  </option>
                </select>
              </div>
              <label className="flex cursor-pointer items-start gap-2 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                <input
                  type="checkbox"
                  className="mt-0.5"
                  checked={issueLeOnCreate}
                  onChange={(e) => setIssueLeOnCreate(e.target.checked)}
                />
                <span>
                  <span className="block text-sm font-medium text-gray-900 dark:text-white">
                    {t('domains.issue_lets_encrypt')}
                  </span>
                  <span className="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">
                    {t('domains.issue_lets_encrypt_hint')}
                  </span>
                </span>
              </label>
              {issueLeOnCreate && (
                <div>
                  <label className="label">{t('domains.lets_encrypt_email_optional')}</label>
                  <input
                    name="lets_encrypt_email"
                    type="email"
                    className="input w-full"
                    placeholder={t('domains.lets_encrypt_email_placeholder')}
                    autoComplete="email"
                  />
                </div>
              )}
              <div className="flex justify-end gap-2 pt-2">
                <button type="button" className="btn-secondary" onClick={() => setShowAdd(false)}>
                  {t('common.cancel')}
                </button>
                <button type="submit" className="btn-primary" disabled={createM.isPending}>
                  {createM.isPending
                    ? issueLeOnCreate
                      ? t('domains.creating_with_ssl')
                      : t('domains.creating')
                    : t('common.create')}
                </button>
              </div>
            </form>
            ) : addMode === 'subdomain' ? (
            <form
              className="space-y-3"
              onSubmit={(ev) => {
                ev.preventDefault()
                if (subParentId === '' || !subPrefix.trim()) return
                createSubM.mutate({
                  parentId: subParentId,
                  prefix: subPrefix.trim(),
                  php_version: subPhpVersion,
                })
              }}
            >
              <p className="text-xs text-gray-500 dark:text-gray-400">{t('domains.subdomain_hint')}</p>
              <div>
                <label className="label">{t('domains.subdomain_parent')}</label>
                <select
                  className="input w-full"
                  required
                  value={subParentId}
                  onChange={(e) => {
                    const id = e.target.value ? Number(e.target.value) : ''
                    setSubParentId(id)
                    if (id !== '') {
                      const parent = list.find((d) => d.id === id)
                      if (parent) setSubPhpVersion(parent.php_version || '8.2')
                    }
                  }}
                >
                  <option value="">{t('common.select')}</option>
                  {list.map((d) => (
                    <option key={d.id} value={d.id}>{d.name}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="label">{t('domains.subdomain_prefix')}</label>
                <div className="flex items-center gap-2">
                  <input
                    className="input w-full font-mono"
                    required
                    value={subPrefix}
                    onChange={(e) => setSubPrefix(e.target.value)}
                    placeholder="blog"
                    autoComplete="off"
                  />
                  {subParentDomain && (
                    <span className="shrink-0 text-sm text-gray-500">.{subParentDomain.name}</span>
                  )}
                </div>
                {subPreview && (
                  <p className="mt-1 text-xs text-primary-600 dark:text-primary-400">
                    {t('domains.subdomain_preview')}: <span className="font-mono">{subPreview}</span>
                  </p>
                )}
              </div>
              <div>
                <label className="label">{t('domains.php_version')}</label>
                <select
                  className="input w-full"
                  value={subPhpVersion}
                  onChange={(e) => setSubPhpVersion(e.target.value)}
                >
                  {PHP_OPTIONS.map((v) => (
                    <option key={v} value={v}>PHP {v}</option>
                  ))}
                </select>
              </div>
              <div className="flex justify-end gap-2 pt-2">
                <button type="button" className="btn-secondary" onClick={() => setShowAdd(false)}>
                  {t('common.cancel')}
                </button>
                <button
                  type="submit"
                  className="btn-primary"
                  disabled={createSubM.isPending || subParentId === '' || !subPrefix.trim()}
                >
                  {createSubM.isPending ? t('domains.subdomain_creating') : t('domains.subdomain_add')}
                </button>
              </div>
            </form>
            ) : (
            <form
              className="space-y-3"
              onSubmit={(ev) => {
                ev.preventDefault()
                if (aliasParentId === '' || !aliasHostname.trim()) return
                createAliasM.mutate({
                  parentId: aliasParentId,
                  hostname: aliasHostname.trim().toLowerCase(),
                })
              }}
            >
              <p className="text-xs text-gray-500 dark:text-gray-400">{t('domains.alias_hint')}</p>
              <div>
                <label className="label">{t('domains.alias_parent')}</label>
                <select
                  className="input w-full"
                  required
                  value={aliasParentId}
                  onChange={(e) => setAliasParentId(e.target.value ? Number(e.target.value) : '')}
                >
                  <option value="">{t('common.select')}</option>
                  {list.map((d) => (
                    <option key={d.id} value={d.id}>{d.name}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="label">{t('domains.alias_hostname')}</label>
                <input
                  className="input w-full font-mono"
                  required
                  value={aliasHostname}
                  onChange={(e) => setAliasHostname(e.target.value)}
                  placeholder="www.ornek.com"
                  autoComplete="off"
                />
              </div>
              <div className="flex justify-end gap-2 pt-2">
                <button type="button" className="btn-secondary" onClick={() => setShowAdd(false)}>
                  {t('common.cancel')}
                </button>
                <button
                  type="submit"
                  className="btn-primary"
                  disabled={createAliasM.isPending || aliasParentId === '' || !aliasHostname.trim()}
                >
                  {createAliasM.isPending ? t('domains.alias_creating') : t('domains.alias_add')}
                </button>
              </div>
            </form>
            )}
          </div>
        </div>
      )}

      <div className="card">
        <div className="p-4 border-b border-gray-200 dark:border-panel-border">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder={t('common.search')}
              className="input pl-10"
            />
          </div>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="border-b border-gray-200 dark:border-panel-border">
                <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                  {t('domains.name')}
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                  {t('domains.php_version')}
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                  {t('domains.ssl_status')}
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                  {t('domains.server_type')}
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                  {t('common.status')}
                </th>
                <th className="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                  {t('common.actions')}
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200 dark:divide-panel-border">
              {domainsQ.isLoading && (
                <tr>
                  <td colSpan={6} className="px-6 py-8 text-center text-gray-500">
                    {t('common.loading')}
                  </td>
                </tr>
              )}

              {!domainsQ.isLoading &&
                filtered.flatMap((domain) => {
                  const b = busy[domain.id] ?? {}
                  const sslEnabled = !!domain.ssl_enabled
                  const canToggle = domain.status === 'active' || domain.status === 'suspended'
                  const health = healthByDomain.get(domain.id)
                  const score = Math.max(0, Math.min(100, health?.score ?? 0))
                  const grade = health?.grade ?? 'critical'
                  const ringClass =
                    grade === 'excellent'
                      ? 'text-emerald-500'
                      : grade === 'good'
                        ? 'text-sky-500'
                        : grade === 'warning'
                          ? 'text-amber-500'
                          : 'text-rose-500'
                  const healthHint =
                    health && health.reasons.length > 0
                      ? t('domains.health_tooltip_with_reasons', {
                          score,
                          reasons: health.reasons.join(' | '),
                        })
                      : t('domains.health_tooltip', { score })

                  const statusBadge = clsx(
                    'px-2.5 py-1 text-xs font-medium rounded-full',
                    domain.status === 'active' &&
                      'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400',
                    domain.status === 'suspended' &&
                      'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300',
                    domain.status === 'pending' &&
                      'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400',
                  )

                  const nextStatus = domain.status === 'active' ? 'suspended' : 'active'
                  const nextStatusLabel =
                    nextStatus === 'suspended' ? t('domains.suspended') : t('common.active')

                  const parentRow = (
                    <tr
                      key={domain.id}
                      className="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/50"
                    >
                      <td className="px-6 py-4">
                        <Link
                          to={`/files?domain=${domain.id}`}
                          className="flex items-center gap-3"
                          title={healthHint}
                        >
                          <Globe className="h-5 w-5 text-primary-500" />
                          <div className="flex min-w-0 items-center gap-2">
                            <span className="truncate font-medium text-gray-900 dark:text-white">
                              {domain.name}
                            </span>
                            <span
                              className={clsx(
                                'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-semibold',
                                grade === 'excellent' &&
                                  'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-700/40 dark:bg-emerald-900/20 dark:text-emerald-300',
                                grade === 'good' &&
                                  'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-700/40 dark:bg-sky-900/20 dark:text-sky-300',
                                grade === 'warning' &&
                                  'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-700/40 dark:bg-amber-900/20 dark:text-amber-300',
                                grade === 'critical' &&
                                  'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-700/40 dark:bg-rose-900/20 dark:text-rose-300',
                              )}
                              title={healthHint}
                            >
                              <span
                                className={clsx(
                                  'h-4 w-4 rounded-full',
                                  ringClass,
                                )}
                                style={{
                                  background: `conic-gradient(currentColor ${Math.round((score / 100) * 360)}deg, rgba(156, 163, 175, 0.25) 0deg)`,
                                }}
                                aria-hidden
                              />
                              {score}
                            </span>
                          </div>
                        </Link>
                      </td>

                      <td className="px-6 py-4">
                        <select
                          className="input w-[120px]"
                          value={domain.php_version}
                          disabled={!!b.php || !canWrite}
                          onChange={(e) => {
                            const next = e.target.value
                            if (next === domain.php_version) return
                            if (
                              !window.confirm(
                                t('domains.confirm_php_change', { php: next }),
                              )
                            ) {
                              return
                            }
                            phpM.mutate({ id: domain.id, php_version: next })
                          }}
                        >
                          {PHP_OPTIONS.map((v) => (
                            <option key={v} value={v}>
                              PHP {v}
                            </option>
                          ))}
                        </select>
                      </td>

                      <td className="px-6 py-4">
                        {sslEnabled ? (
                          <div className="flex items-center gap-1.5 text-green-600 dark:text-green-400">
                            <ShieldCheck className="h-4 w-4" />
                            <span className="text-sm">{t('domains.ssl_active')}</span>
                          </div>
                        ) : (
                          <div className="space-y-2">
                            <div className="flex items-center gap-2">
                              <div className="flex items-center gap-1.5 text-gray-400">
                              <Shield className="h-4 w-4" />
                              <span className="text-sm">{t('domains.ssl_none')}</span>
                              </div>
                              <button
                                type="button"
                                className="btn-secondary px-2.5 py-1.5 text-xs disabled:opacity-70"
                                disabled={!!b.ssl || !canSsl}
                                onClick={() => {
                                  if (!window.confirm(t('domains.confirm_ssl_issue'))) {
                                    return
                                  }
                                  sslIssueM.mutate({ id: domain.id })
                                }}
                              >
                                {b.ssl ? <Loader2 className="h-4 w-4 animate-spin" /> : t('domains.ssl_add_letsencrypt')}
                              </button>
                            </div>
                            {b.ssl && (
                              <div className="w-52 max-w-full">
                                <div className="h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                  <div
                                    className="h-1.5 rounded-full bg-primary-500 transition-all duration-700"
                                    style={{ width: `${sslProgress[domain.id]?.pct ?? 8}%` }}
                                  />
                                </div>
                                <p className="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                  {t('domains.ssl_loading_hint')}
                                </p>
                              </div>
                            )}
                          </div>
                        )}
                      </td>

                      <td className="px-6 py-4">
                        <div className="flex items-center gap-1">
                          <select
                            className="input min-w-[130px] max-w-[180px] flex-1"
                            value={domain.server_type}
                            disabled={!!b.server || !canWrite}
                            onChange={(e) => {
                              const next = e.target.value
                              if (next === domain.server_type) return
                              const nextLabel =
                                next === 'apache'
                                  ? 'Apache'
                                  : next === 'openlitespeed'
                                    ? t('domains.server_openlitespeed')
                                    : 'Nginx'
                              if (
                                !window.confirm(
                                  t('domains.confirm_server_change', { server: nextLabel }),
                                )
                              ) {
                                return
                              }
                              serverM.mutate({ id: domain.id, server_type: next })
                            }}
                          >
                            <option
                              value="nginx"
                              disabled={!serverTypeSelectable('nginx', domain.server_type)}
                            >
                              nginx
                            </option>
                            <option
                              value="apache"
                              disabled={!serverTypeSelectable('apache', domain.server_type)}
                            >
                              Apache
                            </option>
                            <option
                              value="openlitespeed"
                              disabled={!serverTypeSelectable('openlitespeed', domain.server_type)}
                            >
                              {t('domains.server_openlitespeed')}
                            </option>
                          </select>
                          <button
                            type="button"
                            title={t('domains.webserver_configure')}
                            className="shrink-0 rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700"
                            onClick={() => setQuickTarget(domain)}
                          >
                            <ServerCog className="h-4 w-4" aria-hidden />
                            <span className="sr-only">{t('domains.webserver_configure')}</span>
                          </button>
                        </div>
                      </td>

                      <td className="px-6 py-4">
                        {canToggle && canWrite ? (
                          <button
                            type="button"
                            className={statusBadge}
                            disabled={!!b.status}
                            onClick={() => {
                              if (
                                !window.confirm(
                                  t('domains.confirm_status_change', { status: nextStatusLabel }),
                                )
                              ) {
                                return
                              }
                              statusM.mutate({
                                id: domain.id,
                                status: nextStatus,
                              })
                            }}
                          >
                            {domain.status === 'active'
                              ? t('common.active')
                              : domain.status === 'suspended'
                                ? t('domains.suspended')
                                : domain.status}
                          </button>
                        ) : (
                          <span className={statusBadge}>
                            {domain.status === 'pending' ? t('common.pending') : domain.status}
                          </span>
                        )}
                      </td>

                      <td className="px-6 py-4 text-right">
                        <div className="inline-flex items-center justify-end gap-1">
                          <button
                            type="button"
                            title={t('domains.subdomain_add')}
                            className="p-1.5 rounded-lg hover:bg-primary-50 dark:hover:bg-primary-950/40 text-primary-600 dark:text-primary-400"
                            onClick={() => openAddModal('subdomain', domain.id)}
                          >
                            <Layers className="h-4 w-4" />
                          </button>
                          <button
                            type="button"
                            title={t('domains.alias_add')}
                            className="p-1.5 rounded-lg hover:bg-teal-50 dark:hover:bg-teal-950/40 text-teal-600 dark:text-teal-400"
                            onClick={() => openAddModal('alias', domain.id)}
                          >
                            <Globe className="h-4 w-4" />
                          </button>
                          <button
                            type="button"
                            title={t('domains.quick_settings')}
                            className="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500"
                            onClick={() => setQuickTarget(domain)}
                          >
                            <Settings className="h-4 w-4" />
                          </button>
                          <button
                            type="button"
                            title={t('domains.open_site')}
                            className="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500"
                            onClick={() => {
                              const url = safeDomainUrl(domain.name)
                              if (!url) {
                                toast.error(t('domains.invalid_domain_url'))
                                return
                              }
                              window.open(url, '_blank', 'noopener,noreferrer')
                            }}
                          >
                            <ExternalLink className="h-4 w-4" />
                          </button>
                          <button
                            type="button"
                            title={t('domains.delete_site')}
                            className="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/40 text-red-600 dark:text-red-400"
                            disabled={!canWrite}
                            onClick={() => setDeleteTarget(domain)}
                          >
                            <Trash2 className="h-4 w-4" />
                          </button>
                          <button
                            type="button"
                            title={t('domains.logs_button')}
                            className="p-1.5 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400"
                            onClick={() => setLogTarget(domain)}
                          >
                            <FileText className="h-4 w-4" />
                          </button>
                          <button
                            type="button"
                            title={t('domains.traffic_button')}
                            className="p-1.5 rounded-lg hover:bg-fuchsia-50 dark:hover:bg-fuchsia-950/40 text-fuchsia-600 dark:text-fuchsia-400"
                            onClick={() => setTrafficTarget(domain)}
                          >
                            <BarChart2 className="h-4 w-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  )

                  const subRows = (domain.site_subdomains ?? []).map((sub) => (
                    <tr
                      key={`sub-${sub.id}`}
                      className="bg-gray-50/60 transition-colors hover:bg-gray-100/80 dark:bg-gray-800/30 dark:hover:bg-gray-800/50"
                    >
                      <td className="px-6 py-3">
                        <div className="flex items-center gap-3 pl-8">
                          <Layers className="h-4 w-4 shrink-0 text-indigo-400" />
                          <div className="min-w-0">
                            <span className="truncate font-medium text-gray-800 dark:text-gray-200 font-mono text-sm">
                              {sub.hostname}
                            </span>
                            <p className="text-[11px] text-gray-500">{t('domains.subdomain_of', { domain: domain.name })}</p>
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-3 text-sm text-gray-500">
                        {sub.php_version ?? domain.php_version}
                      </td>
                      <td className="px-6 py-3 text-sm text-gray-400">
                        {sub.ssl_enabled ? (
                          <span className="inline-flex items-center gap-1 text-green-600 dark:text-green-400">
                            <ShieldCheck className="h-3.5 w-3.5" />
                            {t('domains.ssl_active')}
                          </span>
                        ) : (
                          <div className="flex flex-wrap items-center gap-2">
                            <span className="text-gray-400">{t('domains.ssl_none')}</span>
                            {canSsl && (
                              <button
                                type="button"
                                className="btn-secondary px-2 py-1 text-[11px] disabled:opacity-70"
                                disabled={!!busySubSsl[sub.id]}
                                onClick={() => {
                                  if (!window.confirm(t('domains.confirm_ssl_issue'))) return
                                  sslIssueM.mutate({ id: domain.id, subdomain_id: sub.id })
                                }}
                              >
                                {busySubSsl[sub.id] ? (
                                  <Loader2 className="h-3.5 w-3.5 animate-spin" />
                                ) : (
                                  t('domains.ssl_add_letsencrypt')
                                )}
                              </button>
                            )}
                          </div>
                        )}
                      </td>
                      <td className="px-6 py-3 text-sm text-gray-500">
                        {sub.server_type ?? domain.server_type}
                      </td>
                      <td className="px-6 py-3">
                        <span className="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                          {t('domains.subdomain_badge')}
                        </span>
                      </td>
                      <td className="px-6 py-3 text-right">
                        <div className="inline-flex items-center justify-end gap-1">
                          <Link
                            to={`/files?domain=${domain.id}&subdomain_id=${sub.id}`}
                            title={t('domains.subdomain_files')}
                            className="p-1.5 rounded-lg hover:bg-amber-50 dark:hover:bg-amber-950/40 text-amber-600 dark:text-amber-400"
                          >
                            <FolderOpen className="h-4 w-4" />
                          </Link>
                          <Link
                            to={`/ssl?focus=${encodeURIComponent(sub.hostname)}`}
                            title={t('domains.subdomain_ssl')}
                            className="p-1.5 rounded-lg hover:bg-green-50 dark:hover:bg-green-950/40 text-green-600 dark:text-green-400"
                          >
                            <Shield className="h-4 w-4" />
                          </Link>
                          <button
                            type="button"
                            title={t('domains.quick_settings')}
                            className="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500"
                            onClick={() =>
                              setQuickTarget({
                                id: domain.id,
                                subdomain_id: sub.id,
                                name: sub.hostname,
                                php_version: sub.php_version ?? domain.php_version,
                                server_type: sub.server_type ?? domain.server_type,
                                status: domain.status,
                                ssl_enabled: sub.ssl_enabled,
                              })
                            }
                          >
                            <Settings className="h-4 w-4" />
                          </button>
                          <button
                            type="button"
                            title={t('domains.open_site')}
                            className="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500"
                            onClick={() => {
                              const url = safeDomainUrl(sub.hostname)
                              if (!url) {
                                toast.error(t('domains.invalid_domain_url'))
                                return
                              }
                              window.open(url, '_blank', 'noopener,noreferrer')
                            }}
                          >
                            <ExternalLink className="h-4 w-4" />
                          </button>
                          <button
                            type="button"
                            title={t('domains.subdomain_delete')}
                            className="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/40 text-red-600 dark:text-red-400"
                            disabled={deletingSubId === sub.id || !canWrite}
                            onClick={() => {
                              if (!window.confirm(t('domains.subdomain_delete_confirm', { hostname: sub.hostname }))) {
                                return
                              }
                              deleteSubM.mutate({ parentId: domain.id, path_segment: sub.path_segment, subId: sub.id })
                            }}
                          >
                            <Trash2 className="h-4 w-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))

                  const aliasRows = (domain.site_domain_aliases ?? []).map((alias) => (
                    <tr
                      key={`alias-${alias.id}`}
                      className="bg-teal-50/40 transition-colors hover:bg-teal-50/70 dark:bg-teal-950/10 dark:hover:bg-teal-950/20"
                    >
                      <td className="px-6 py-3">
                        <div className="flex items-center gap-3 pl-8">
                          <Globe className="h-4 w-4 shrink-0 text-teal-500" />
                          <div className="min-w-0">
                            <span className="truncate font-medium text-gray-800 dark:text-gray-200 font-mono text-sm">
                              {alias.hostname}
                            </span>
                            <p className="text-[11px] text-gray-500">{t('domains.alias_of', { domain: domain.name })}</p>
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-3 text-sm text-gray-500">{domain.php_version}</td>
                      <td className="px-6 py-3 text-sm text-gray-400">—</td>
                      <td className="px-6 py-3 text-sm text-gray-500">{domain.server_type}</td>
                      <td className="px-6 py-3">
                        <span className="rounded-full bg-teal-50 px-2 py-0.5 text-xs font-medium text-teal-700 dark:bg-teal-900/30 dark:text-teal-300">
                          {t('domains.alias_badge')}
                        </span>
                      </td>
                      <td className="px-6 py-3 text-right">
                        <div className="inline-flex items-center justify-end gap-1">
                          <button
                            type="button"
                            title={t('domains.open_site')}
                            className="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500"
                            onClick={() => {
                              const url = safeDomainUrl(alias.hostname)
                              if (!url) {
                                toast.error(t('domains.invalid_domain_url'))
                                return
                              }
                              window.open(url, '_blank', 'noopener,noreferrer')
                            }}
                          >
                            <ExternalLink className="h-4 w-4" />
                          </button>
                          <button
                            type="button"
                            title={t('domains.alias_delete')}
                            className="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/40 text-red-600 dark:text-red-400"
                            disabled={deleteAliasM.isPending}
                            onClick={() => {
                              if (!window.confirm(t('domains.alias_delete_confirm', { hostname: alias.hostname }))) {
                                return
                              }
                              deleteAliasM.mutate({ parentId: domain.id, hostname: alias.hostname })
                            }}
                          >
                            <Trash2 className="h-4 w-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))

                  return [parentRow, ...subRows, ...aliasRows]
                })}
            </tbody>
          </table>
        </div>

        {domainsQ.isError && (
          <div className="border-t border-gray-200 px-6 py-8 text-center dark:border-panel-border">
            <p className="text-sm text-red-600 dark:text-red-400">
              {(domainsQ.error as { response?: { data?: { message?: string } } })?.response?.data?.message ??
                t('domains.list_load_error')}
            </p>
            <button type="button" className="btn-secondary mt-3 text-sm" onClick={() => void domainsQ.refetch()}>
              {t('domains.refresh')}
            </button>
          </div>
        )}

        {!domainsQ.isLoading && !domainsQ.isError && filtered.length === 0 && (
          <div className="py-12 text-center text-gray-500 dark:text-gray-400">{t('common.no_data')}</div>
        )}

        {!domainsQ.isLoading && !domainsQ.isError && lastPage > 1 && (
          <div className="flex items-center justify-between border-t border-gray-200 px-6 py-4 dark:border-panel-border">
            <p className="text-sm text-gray-500">
              {t('domains.page_of', { page, last: lastPage, total })}
            </p>
            <div className="flex gap-2">
              <button
                type="button"
                className="btn-secondary text-sm"
                disabled={page <= 1}
                onClick={() => setPage((p) => Math.max(1, p - 1))}
              >
                {t('domains.prev_page')}
              </button>
              <button
                type="button"
                className="btn-secondary text-sm"
                disabled={page >= lastPage}
                onClick={() => setPage((p) => Math.min(lastPage, p + 1))}
              >
                {t('domains.next_page')}
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
