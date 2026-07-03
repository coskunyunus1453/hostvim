import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useMemo } from 'react'
import { useAuthStore } from '../store/authStore'
import api from '../services/api'
import type { DashboardData, ServiceInfo } from '../types'
import { isHostingSuperAdmin, isServerAdminUI } from '../lib/authRoles'
import { tokenHasAbility } from '../lib/abilities'
import { Globe, Database, Mail, HardDrive, Plus, Users, Power, RefreshCcw, RotateCw, Server, Receipt } from 'lucide-react'
import toast from 'react-hot-toast'
import ResourceChartsSection from '../components/dashboard/ResourceChartsSection'
import DashboardSkeleton from '../components/dashboard/DashboardSkeleton'
import PanelUpdateCard from '../components/panel/PanelUpdateCard'
import { pollWhenVisible } from '../lib/pollWhenVisible'

function fmtGb(nBytes?: number | null): string {
  if (nBytes == null || !Number.isFinite(nBytes)) return '—'
  return `${Math.round((nBytes / 1024 / 1024 / 1024) * 10) / 10} GB`
}

function fmtMb(mb?: number | null): string {
  if (mb == null || !Number.isFinite(mb)) return '—'
  if (mb >= 1024) return `${Math.round((mb / 1024) * 10) / 10} GB`
  return `${Math.round(mb)} MB`
}

function fmtNum(n?: number | null): string {
  if (n == null || !Number.isFinite(n)) return '—'
  return Math.round(n).toLocaleString()
}

export default function DashboardPage() {
  const { t } = useTranslation()
  const qc = useQueryClient()
  const user = useAuthStore((s) => s.user)
  const isSuper = isHostingSuperAdmin(user)
  const serverUI = isServerAdminUI(user)
  const canBilling = tokenHasAbility(user?.abilities, 'billing:read')

  const dashQ = useQuery({
    queryKey: ['dashboard'],
    queryFn: async () => (await api.get('/dashboard')).data.dashboard as DashboardData,
    refetchInterval: serverUI ? () => pollWhenVisible(20_000) : false,
    staleTime: 15_000,
    placeholderData: (prev) => prev,
  })

  const d = dashQ.data
  const dashLoading = dashQ.isLoading && d == null
  const sys = d?.system_stats
  const serviceShortcutsSupported = (sys?.os ?? '').toLowerCase().includes('linux')

  const servicesQ = useQuery({
    queryKey: ['dashboard-services'],
    queryFn: async () => (await api.get('/system/services')).data.services as ServiceInfo[],
    enabled: isSuper,
    refetchInterval: isSuper ? () => pollWhenVisible(30_000) : false,
    staleTime: 20_000,
  })

  const invoicesQ = useQuery({
    queryKey: ['my-invoices-summary'],
    queryFn: async () =>
      (await api.get('/billing/invoices')).data as { data: Array<{ status: string; total: string; currency: string }> },
    enabled: canBilling,
    staleTime: 60_000,
  })

  const unpaidSummary = useMemo(() => {
    const list = (invoicesQ.data?.data ?? []).filter((i) => i.status === 'unpaid' || i.status === 'overdue')
    const total = list.reduce((s, i) => s + parseFloat(i.total), 0)
    const currency = list[0]?.currency || 'TRY'
    return { count: list.length, total, currency }
  }, [invoicesQ.data])

  const parseApiErrorMessage = (err: unknown, fallback: string): string => {
    const ax = err as { response?: { data?: { message?: string; error?: string; details?: string } } }
    const message = ax.response?.data?.message || ax.response?.data?.error || ''
    const details = ax.response?.data?.details || ''
    const merged = [message, details].filter(Boolean).join(' - ').trim()
    return merged || fallback
  }

  const rebootM = useMutation({
    mutationFn: async () => api.post('/system/reboot'),
    onSuccess: () => {
      // Sunucu reboot isteği gönderildi; bağlantı kopabilir.
      // Sadece kullanıcıya bildirim veriyoruz.
      toast.success(t('dashboard.reboot_requested'))
    },
    onError: (err: unknown) => {
      toast.error(parseApiErrorMessage(err, t('dashboard.reboot_failed')))
    },
  })

  const restartServiceM = useMutation({
    mutationFn: async (name: string) => api.post(`/system/services/${encodeURIComponent(name)}`, { action: 'restart' }),
    onSuccess: (_, name) => {
      toast.success(t('dashboard.service_restart_success', { service: name }))
      void qc.invalidateQueries({ queryKey: ['dashboard-services'] })
      // Servisler restart sırasında kısa süre "error/unknown" dönebilir;
      // birkaç kez yeniden sorgulayarak kartı stabil hale getir.
      setTimeout(() => {
        void qc.invalidateQueries({ queryKey: ['dashboard-services'] })
      }, 2000)
      setTimeout(() => {
        void qc.invalidateQueries({ queryKey: ['dashboard-services'] })
      }, 6000)
    },
    onError: (err: unknown) => {
      toast.error(parseApiErrorMessage(err, t('dashboard.service_restart_failed')))
    },
  })

  const statCards = [
    {
      key: 'domains',
      label: t('dashboard.domains_count'),
      value: d?.domains_count ?? 0,
      icon: Globe,
      color: 'text-sky-600 dark:text-sky-400',
      ring: 'ring-sky-200 dark:ring-sky-900/60',
      bg: 'bg-sky-50/80 dark:bg-sky-950/30',
      to: '/domains',
      cta: t('dashboard.create_site'),
    },
    {
      key: 'databases',
      label: t('dashboard.databases_count'),
      value: d?.databases_count ?? 0,
      icon: Database,
      color: 'text-emerald-600 dark:text-emerald-400',
      ring: 'ring-emerald-200 dark:ring-emerald-900/60',
      bg: 'bg-emerald-50/80 dark:bg-emerald-950/30',
      to: '/databases',
      cta: t('dashboard.create_database'),
    },
    {
      key: 'emails',
      label: t('dashboard.email_count'),
      value: d?.email_accounts_count ?? 0,
      icon: Mail,
      color: 'text-violet-600 dark:text-violet-400',
      ring: 'ring-violet-200 dark:ring-violet-900/60',
      bg: 'bg-violet-50/80 dark:bg-violet-950/30',
      to: '/email',
      cta: t('dashboard.create_email'),
    },
  ]

  const adminExtras = isSuper && d
    ? [
        {
          key: 'users',
          label: t('nav.users'),
          value: d.total_users ?? 0,
          icon: Users,
          color: 'text-indigo-600 dark:text-indigo-400',
          ring: 'ring-indigo-200 dark:ring-indigo-900/60',
          bg: 'bg-indigo-50/80 dark:bg-indigo-950/30',
          to: '/users',
        },
        {
          key: 'total-domains',
          label: t('nav.domains'),
          value: d.total_domains ?? 0,
          icon: Globe,
          color: 'text-cyan-600 dark:text-cyan-400',
          ring: 'ring-cyan-200 dark:ring-cyan-900/60',
          bg: 'bg-cyan-50/80 dark:bg-cyan-950/30',
          to: '/domains',
        },
      ]
    : []

  const quickActions = [
    { label: t('dashboard.create_site'), icon: Globe, path: '/domains' },
    { label: t('dashboard.create_database'), icon: Database, path: '/databases' },
    { label: t('dashboard.create_email'), icon: Mail, path: '/email' },
  ]
  const pkg = user?.hosting_package
  const quota = d?.quota
  type LimitRow = { key: string; label: string; used: number; max: number | null; kind: 'disk' | 'ram' | 'count' }
  const limitRows: LimitRow[] = quota
    ? [
        { key: 'domains', label: t('nav.domains'), used: quota.domains.used, max: quota.domains.max, kind: 'count' },
        { key: 'databases', label: t('nav.databases'), used: quota.databases.used, max: quota.databases.max, kind: 'count' },
        { key: 'email', label: t('nav.email'), used: quota.email.used, max: quota.email.max, kind: 'count' },
        { key: 'subdomains', label: t('dashboard.limit_subdomains'), used: quota.subdomains.used, max: quota.subdomains.max, kind: 'count' },
        { key: 'ftp', label: t('dashboard.limit_ftp'), used: quota.ftp.used, max: quota.ftp.max, kind: 'count' },
      ]
    : pkg
      ? [
          { key: 'domains', label: t('nav.domains'), used: d?.domains_count ?? 0, max: pkg.max_domains, kind: 'count' },
          { key: 'databases', label: t('nav.databases'), used: d?.databases_count ?? 0, max: pkg.max_databases, kind: 'count' },
          { key: 'email', label: t('nav.email'), used: d?.email_accounts_count ?? 0, max: pkg.max_email_accounts, kind: 'count' },
        ]
      : []
  const cpuLimit = quota?.cpu_limit ?? null
  const ramLimit = quota?.memory_limit_mb ?? null
  // Müşteriye özel kaynak doluluğu (SSD/RAM/inode) — "Sistem Durumu" kartında gösterilir.
  const resourceRows: LimitRow[] = quota
    ? [
        { key: 'disk', label: t('dashboard.limit_disk'), used: quota.disk_used_mb, max: quota.disk_limit_mb, kind: 'disk' },
        { key: 'ram', label: t('dashboard.limit_ram'), used: quota.memory_used_mb ?? 0, max: ramLimit, kind: 'ram' },
        { key: 'inode', label: t('dashboard.limit_inode'), used: quota.inode_used ?? 0, max: quota.inode_limit ?? null, kind: 'count' },
      ]
    : []
  const nearLimit =
    limitRows.some((x) => x.max != null && x.max > 0 && x.used >= x.max) ||
    resourceRows.some((x) => x.max != null && x.max > 0 && x.used >= x.max)
  const showPackageCard = limitRows.length > 0 || cpuLimit != null || ramLimit != null
  const showCustomerResources = !serverUI && quota != null
  const custDiskPct =
    quota && quota.disk_limit_mb != null && quota.disk_limit_mb > 0
      ? Math.min(100, Math.round((quota.disk_used_mb / quota.disk_limit_mb) * 100))
      : null
  const servicePriority = ['nginx', 'apache2', 'openlitespeed']
  const serviceRows = ((servicesQ.data ?? []) as ServiceInfo[])
    .filter((svc) => servicePriority.includes(svc.name) || /^php[0-9.]+-fpm$/i.test(svc.name))
    .sort((a, b) => {
      const ai = servicePriority.indexOf(a.name)
      const bi = servicePriority.indexOf(b.name)
      if (ai >= 0 && bi >= 0) return ai - bi
      if (ai >= 0) return -1
      if (bi >= 0) return 1
      return a.name.localeCompare(b.name)
    })

  if (dashLoading) {
    return <DashboardSkeleton showCharts={serverUI} />
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
            {t('dashboard.overview')}
          </h1>
          <p className="text-gray-500 dark:text-gray-400 mt-1">
            {t('dashboard.welcome')}, {user?.name}
          </p>
        </div>
        {isSuper && (
          <div className="flex flex-wrap items-center gap-2">
            <button
              type="button"
              className="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-amber-300 text-amber-900 bg-amber-50 hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-100 text-xs sm:text-sm"
              onClick={() => {
                if (
                  window.confirm(
                    t('dashboard.reboot_confirm') ||
                      'Sunucuyu güvenli şekilde yeniden başlatmak istediğinizden emin misiniz? Aktif bağlantılar kesilecektir.',
                  )
                ) {
                  rebootM.mutate()
                }
              }}
              disabled={rebootM.isPending}
            >
              <Power className="h-4 w-4" />
              {t('dashboard.reboot')}
            </button>
            <button
              type="button"
              className="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-sky-300 text-sky-900 bg-sky-50 hover:bg-sky-100 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-100 text-xs sm:text-sm"
              onClick={() => document.getElementById('panel-update')?.scrollIntoView({ behavior: 'smooth' })}
            >
              <RefreshCcw className="h-4 w-4" />
              {t('dashboard.update_panel')}
            </button>
          </div>
        )}
      </div>

      {isSuper && (
        <div id="panel-update">
          <PanelUpdateCard compact />
        </div>
      )}

      {canBilling && unpaidSummary.count > 0 && (
        <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-900/40 dark:bg-amber-950/20">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div className="flex items-center gap-3">
              <Receipt className="h-6 w-6 text-amber-600 dark:text-amber-400 shrink-0" />
              <div>
                <p className="text-sm font-semibold text-amber-900 dark:text-amber-100">{t('dashboard.billing_unpaid_title')}</p>
                <p className="text-xs text-amber-800 dark:text-amber-200">
                  {t('dashboard.billing_unpaid_count', { count: unpaidSummary.count })}
                  {' · '}
                  {t('dashboard.billing_outstanding', {
                    amount: unpaidSummary.total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + unpaidSummary.currency,
                  })}
                </p>
              </div>
            </div>
            <Link to="/invoices" className="btn-primary text-xs">
              {t('dashboard.billing_pay_cta')}
            </Link>
          </div>
        </div>
      )}

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="card p-5 sm:col-span-2 lg:col-span-2">
          <div className="flex items-start justify-between gap-4">
            <div>
              <p className="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {t('dashboard.disk_usage')}
              </p>
              <p className="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                {showCustomerResources
                  ? `${fmtMb(quota?.disk_used_mb)} / ${quota?.disk_limit_mb == null ? t('dashboard.unlimited') : fmtMb(quota.disk_limit_mb)}`
                  : dashQ.isFetching && !sys
                    ? '…'
                    : `${fmtGb(sys?.disk_used)} / ${fmtGb(sys?.disk_total)}`}
              </p>
              <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {showCustomerResources
                  ? custDiskPct == null
                    ? t('dashboard.unlimited')
                    : `%${custDiskPct} ${t('dashboard.used')}`
                  : sys?.disk_percent != null
                    ? `%${Math.round(sys.disk_percent)} dolu`
                    : '—'}
              </p>
            </div>
            <div className="rounded-xl bg-orange-50 p-2.5 ring-1 ring-orange-200 dark:bg-orange-950/30 dark:ring-orange-900/60">
              <HardDrive className="h-5 w-5 text-orange-600 dark:text-orange-400" />
            </div>
          </div>
          <div className="mt-4 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
            <div
              className="h-2 rounded-full bg-gradient-to-r from-orange-500 to-rose-500 transition-all"
              style={{ width: `${Math.min(100, Math.max(0, showCustomerResources ? (custDiskPct ?? 0) : (sys?.disk_percent ?? 0)))}%` }}
            />
          </div>
        </div>

        {[...statCards, ...adminExtras].map((stat) => (
          <div key={stat.key} className="card p-4">
            <div className="flex items-center justify-between gap-3">
              <div className={`rounded-xl p-2 ring-1 ${stat.bg} ${stat.ring}`}>
                <stat.icon className={`h-4 w-4 ${stat.color}`} />
              </div>
              <p className="text-2xl font-bold leading-none text-gray-900 dark:text-white">
                {dashQ.isFetching && d == null ? '…' : stat.value}
              </p>
            </div>
            <p className="mt-2 text-sm font-medium text-gray-700 dark:text-gray-200">{stat.label}</p>
            <div className="mt-2 min-h-[20px]">
              {Number(stat.value) === 0 && 'cta' in stat && stat.cta ? (
                <Link
                  to={stat.to}
                  className="inline-flex items-center gap-1 text-xs font-medium text-primary-600 hover:underline dark:text-primary-400"
                >
                  <Plus className="h-3.5 w-3.5" />
                  {String(stat.cta)}
                </Link>
              ) : stat.to ? (
                <Link
                  to={stat.to}
                  className="text-xs font-medium text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
                >
                  Detayları gör
                </Link>
              ) : null}
            </div>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 card p-6">
          <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            {t('dashboard.system_status')}
          </h3>
          {!serverUI ? (
            showCustomerResources ? (
              <div className="space-y-4">
                {quota?.package_name && (
                  <div className="flex items-center justify-between gap-2">
                    <p className="text-sm text-gray-600 dark:text-gray-300">{t('dashboard.package_resources_hint')}</p>
                    <span className="rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-700 dark:bg-primary-900/20 dark:text-primary-300">
                      {quota.package_name}
                    </span>
                  </div>
                )}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  {resourceRows.map((r) => {
                    const unlimited = r.max == null || r.max <= 0
                    const pct = unlimited ? 0 : Math.min(100, Math.round((r.used / (r.max as number)) * 100))
                    const fmt = r.kind === 'count' ? fmtNum : fmtMb
                    const valueText = unlimited
                      ? `${fmt(r.used)} / ${t('dashboard.unlimited')}`
                      : `${fmt(r.used)} / ${fmt(r.max)}`
                    const barColor = pct >= 90 ? 'bg-rose-500' : pct >= 70 ? 'bg-amber-500' : 'bg-emerald-500'
                    return (
                      <div key={r.key} className="rounded-xl border border-gray-200 p-3 dark:border-gray-700">
                        <div className="flex items-center justify-between text-sm">
                          <span className="font-medium text-gray-700 dark:text-gray-200">{r.label}</span>
                          <span className="text-gray-900 dark:text-white">{valueText}</span>
                        </div>
                        <div className="mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                          <div
                            className={`h-2 rounded-full transition-all ${unlimited ? 'bg-gray-300 dark:bg-gray-600' : barColor}`}
                            style={{ width: unlimited ? '8%' : `${Math.max(2, pct)}%` }}
                          />
                        </div>
                        {!unlimited && <p className="mt-1 text-[11px] text-gray-500 dark:text-gray-400">%{pct} {t('dashboard.used')}</p>}
                      </div>
                    )
                  })}
                  <div className="rounded-xl border border-gray-200 p-3 dark:border-gray-700">
                    <div className="flex items-center justify-between text-sm">
                      <span className="font-medium text-gray-700 dark:text-gray-200">{t('dashboard.limit_cpu')}</span>
                      <span className="text-gray-900 dark:text-white">
                        {cpuLimit == null ? t('dashboard.unlimited') : `%${cpuLimit}`}
                      </span>
                    </div>
                    <p className="mt-2 text-[11px] text-gray-500 dark:text-gray-400">{t('dashboard.cpu_entitlement_hint')}</p>
                  </div>
                </div>
                {nearLimit && (
                  <Link
                    to="/invoices"
                    className="inline-flex rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-900 hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-100"
                  >
                    {t('dashboard.upgrade_cta')}
                  </Link>
                )}
              </div>
            ) : (
              <p className="text-sm text-gray-500 dark:text-gray-400">
                {t('dashboard.system_admin_only')}
              </p>
            )
          ) : !sys ? (
            <p className="text-sm text-gray-500 dark:text-gray-400">
              {t('dashboard.system_engine_hint')}
            </p>
          ) : (
            <div className="space-y-4">
              <ResourceChartsSection stats={sys} loading={dashQ.isFetching && sys == null} />
              {sys.uptime != null && (
                <p className="text-xs text-gray-500 dark:text-gray-400">
                  {t('dashboard.uptime_approx')}: {Math.floor(sys.uptime / 3600)}h
                </p>
              )}
            </div>
          )}
        </div>

        <div className="card p-6 space-y-5">
          {serverUI && (
            <div className="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
              <div className="flex items-center gap-2">
                <Server className="h-4 w-4 text-primary-500" />
                <h3 className="text-sm font-semibold text-gray-900 dark:text-white">{t('dashboard.service_shortcuts')}</h3>
              </div>
              <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{t('dashboard.service_shortcuts_hint')}</p>
              <div className="mt-3 space-y-2">
                {!serviceShortcutsSupported && (
                  <p className="text-xs text-amber-600 dark:text-amber-400">
                    Yerel macOS/XAMPP ortaminda servis kisayollari systemd gerektirdigi icin durumlar yanlis gorunebilir.
                  </p>
                )}
                {serviceRows.length === 0 && (
                  <p className="text-xs text-gray-500 dark:text-gray-400">{t('dashboard.service_shortcuts_empty')}</p>
                )}
                {serviceShortcutsSupported && serviceRows.map((svc) => (
                  <div
                    key={svc.name}
                    className="flex items-center justify-between rounded-lg border border-gray-200 px-2.5 py-2 dark:border-gray-700"
                  >
                    <div className="min-w-0">
                      <p className="truncate text-xs font-medium text-gray-900 dark:text-white">{svc.name}</p>
                      <p
                        className={`text-[11px] ${
                          svc.status === 'running'
                            ? 'text-emerald-600 dark:text-emerald-400'
                            : svc.status === 'stopped'
                              ? 'text-gray-500 dark:text-gray-400'
                              : 'text-amber-600 dark:text-amber-400'
                        }`}
                      >
                        {svc.status}
                      </p>
                    </div>
                    <button
                      type="button"
                      className="inline-flex items-center gap-1 rounded-md border border-primary-200 px-2 py-1 text-[11px] font-medium text-primary-700 hover:bg-primary-50 dark:border-primary-800 dark:text-primary-300 dark:hover:bg-primary-900/20 disabled:opacity-50"
                      onClick={() => restartServiceM.mutate(svc.name)}
                      disabled={restartServiceM.isPending}
                    >
                      <RotateCw className="h-3 w-3" />
                      {t('dashboard.restart')}
                    </button>
                  </div>
                ))}
              </div>
            </div>
          )}
          {showPackageCard && (
            <div className="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
              <div className="flex items-center justify-between gap-2">
                <h3 className="text-sm font-semibold text-gray-900 dark:text-white">{t('dashboard.package_limits')}</h3>
                {quota?.package_name && (
                  <span className="rounded-full bg-primary-50 px-2 py-0.5 text-[11px] font-medium text-primary-700 dark:bg-primary-900/20 dark:text-primary-300">
                    {quota.package_name}
                  </span>
                )}
              </div>
              <div className="mt-3 space-y-3">
                {limitRows.map((r) => {
                  const unlimited = r.max == null || r.max <= 0
                  const pct = unlimited ? 0 : Math.min(100, Math.round((r.used / (r.max as number)) * 100))
                  const valueText = unlimited
                    ? r.kind === 'disk'
                      ? `${fmtMb(r.used)} / ${t('dashboard.unlimited')}`
                      : `${r.used} / ${t('dashboard.unlimited')}`
                    : r.kind === 'disk'
                      ? `${fmtMb(r.used)} / ${fmtMb(r.max)}`
                      : `${r.used} / ${r.max}`
                  const barColor = pct >= 90 ? 'bg-rose-500' : pct >= 70 ? 'bg-amber-500' : 'bg-emerald-500'
                  return (
                    <div key={r.key}>
                      <div className="flex items-center justify-between text-xs">
                        <span className="text-gray-600 dark:text-gray-300">{r.label}</span>
                        <span className="font-medium text-gray-900 dark:text-white">{valueText}</span>
                      </div>
                      <div className="mt-1 h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                        <div
                          className={`h-1.5 rounded-full transition-all ${unlimited ? 'bg-gray-300 dark:bg-gray-600' : barColor}`}
                          style={{ width: unlimited ? '8%' : `${Math.max(2, pct)}%` }}
                        />
                      </div>
                    </div>
                  )
                })}
                {!showCustomerResources && (cpuLimit != null || ramLimit != null) && (
                  <div className="grid grid-cols-2 gap-2 pt-1">
                    <div className="rounded-lg bg-gray-50 px-2.5 py-2 dark:bg-gray-800/60">
                      <p className="text-[11px] text-gray-500 dark:text-gray-400">{t('dashboard.limit_cpu')}</p>
                      <p className="text-sm font-semibold text-gray-900 dark:text-white">
                        {cpuLimit == null ? t('dashboard.unlimited') : `%${cpuLimit}`}
                      </p>
                    </div>
                    <div className="rounded-lg bg-gray-50 px-2.5 py-2 dark:bg-gray-800/60">
                      <p className="text-[11px] text-gray-500 dark:text-gray-400">{t('dashboard.limit_ram')}</p>
                      <p className="text-sm font-semibold text-gray-900 dark:text-white">
                        {ramLimit == null ? t('dashboard.unlimited') : fmtMb(ramLimit)}
                      </p>
                    </div>
                  </div>
                )}
              </div>
              {nearLimit && (
                <Link
                  to="/invoices"
                  className="mt-3 inline-flex rounded-lg border border-amber-300 bg-amber-50 px-2.5 py-1.5 text-xs font-medium text-amber-900 hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-100"
                >
                  {nearLimit ? t('dashboard.upgrade_cta') : t('dashboard.buy_hosting_cta')}
                </Link>
              )}
            </div>
          )}
          <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            {t('dashboard.quick_actions')}
          </h3>
          <div className="space-y-3">
            {quickActions.map((action) => (
              <Link
                key={action.path}
                to={action.path}
                className="w-full flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors text-left"
              >
                <div className="p-2 rounded-lg bg-primary-50 dark:bg-primary-900/20">
                  <Plus className="h-4 w-4 text-primary-600 dark:text-primary-400" />
                </div>
                <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                  {action.label}
                </span>
              </Link>
            ))}
          </div>
        </div>
      </div>
    </div>
  )
}
