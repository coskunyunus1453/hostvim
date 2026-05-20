import { useEffect, useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Navigate } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useAuthStore } from '../store/authStore'
import { isServerAdminUI } from '../lib/authRoles'
import api from '../services/api'
import {
  Server,
  Globe,
  Network,
  Clock,
  RefreshCw,
  Save,
  Plus,
  Trash2,
  Info,
} from 'lucide-react'
import toast from 'react-hot-toast'
import clsx from 'clsx'

type ServerSettings = {
  hostname?: string
  timezone?: string
  local_time?: string
  ntp_synchronized?: boolean
  primary_ip?: string
  timezones?: string[]
}

type IPRow = {
  address: string
  family: string
  scope?: string
  primary: boolean
  managed: boolean
  label?: string
}

type NetIface = {
  name: string
  mac?: string
  state?: string
  mtu?: number
  addresses: IPRow[]
}

export default function AdminServerSettingsPage() {
  const { t } = useTranslation()
  const qc = useQueryClient()
  const user = useAuthStore((s) => s.user)
  const canServer = isServerAdminUI(user)

  const [tab, setTab] = useState<'general' | 'network'>('general')
  const [hostname, setHostname] = useState('')
  const [timezone, setTimezone] = useState('')
  const [tzFilter, setTzFilter] = useState('')
  const [addIface, setAddIface] = useState('')
  const [addAddress, setAddAddress] = useState('')
  const [addLabel, setAddLabel] = useState('')

  const settingsQ = useQuery({
    queryKey: ['admin-server-settings'],
    queryFn: async () =>
      (await api.get('/system/server-settings')).data as {
        settings: ServerSettings
        interfaces: NetIface[]
        panel_timezone: string
      },
    enabled: !!canServer,
    refetchInterval: 30_000,
  })

  const settings = settingsQ.data?.settings
  const interfaces = settingsQ.data?.interfaces ?? []
  const panelTz = settingsQ.data?.panel_timezone ?? 'UTC'

  useEffect(() => {
    if (settings) {
      setHostname(settings.hostname ?? '')
      setTimezone(settings.timezone ?? 'UTC')
    }
  }, [settings])

  useEffect(() => {
    if (interfaces.length > 0 && !addIface) {
      setAddIface(interfaces[0].name)
    }
  }, [interfaces, addIface])

  const timezoneOptions = useMemo(() => {
    const all = settings?.timezones ?? ['UTC', 'Europe/Istanbul']
    const q = tzFilter.trim().toLowerCase()
    if (!q) return all.slice(0, 80)
    return all.filter((z) => z.toLowerCase().includes(q)).slice(0, 80)
  }, [settings?.timezones, tzFilter])

  const invalidate = () => qc.invalidateQueries({ queryKey: ['admin-server-settings'] })

  const initialHostname = settings?.hostname ?? ''
  const initialTimezone = settings?.timezone ?? 'UTC'

  const saveM = useMutation({
    mutationFn: async () => {
      const payload: { hostname?: string; timezone?: string } = {}
      const h = hostname.trim()
      const tz = timezone.trim()
      if (h && h !== initialHostname) payload.hostname = h
      if (tz && tz !== initialTimezone) payload.timezone = tz
      if (Object.keys(payload).length === 0) {
        throw new Error(t('server_settings.nothing_to_update'))
      }
      return api.patch('/system/server-settings', payload)
    },
    onSuccess: () => {
      toast.success(t('server_settings.saved'))
      invalidate()
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      const msg = ax.response?.data?.message ?? (err instanceof Error ? err.message : String(err))
      toast.error(msg)
    },
  })

  const refreshM = useMutation({
    mutationFn: async () => api.post('/system/network/refresh'),
    onSuccess: () => {
      toast.success(t('server_settings.network_refreshed'))
      invalidate()
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const addIpM = useMutation({
    mutationFn: async () =>
      api.post('/system/network/addresses', {
        interface: addIface,
        address: addAddress.trim(),
        label: addLabel.trim() || undefined,
      }),
    onSuccess: () => {
      toast.success(t('server_settings.ip_added'))
      setAddAddress('')
      setAddLabel('')
      invalidate()
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const removeIpM = useMutation({
    mutationFn: async (vars: { iface: string; address: string }) =>
      api.delete('/system/network/addresses', { data: vars }),
    onSuccess: () => {
      toast.success(t('server_settings.ip_removed'))
      invalidate()
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  if (!canServer) {
    return <Navigate to="/dashboard" replace />
  }

  const localTimeLabel = settings?.local_time
    ? new Date(settings.local_time).toLocaleString()
    : '—'

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div className="flex items-start gap-4">
          <div className="rounded-2xl bg-gradient-to-br from-sky-500 to-indigo-600 p-3 text-white shadow-lg shadow-sky-500/20">
            <Server className="h-8 w-8" />
          </div>
          <div>
            <h1 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
              {t('server_settings.title')}
            </h1>
            <p className="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{t('server_settings.subtitle')}</p>
            {settings?.primary_ip && (
              <p className="mt-2 font-mono text-xs text-primary-600 dark:text-primary-400">
                {t('server_settings.primary_ip')}: {settings.primary_ip}
              </p>
            )}
          </div>
        </div>
        <button
          type="button"
          className="btn-secondary inline-flex items-center gap-2 text-sm"
          onClick={() => {
            void settingsQ.refetch()
          }}
          disabled={settingsQ.isFetching}
        >
          <RefreshCw className={clsx('h-4 w-4', settingsQ.isFetching && 'animate-spin')} />
          {t('common.refresh')}
        </button>
      </div>

      <div className="flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-800 pb-1">
        {(['general', 'network'] as const).map((key) => (
          <button
            key={key}
            type="button"
            className={clsx(
              'rounded-t-lg px-4 py-2 text-sm font-medium transition',
              tab === key
                ? 'border-b-2 border-primary-500 text-primary-600 dark:text-primary-400'
                : 'text-gray-500 hover:text-gray-800 dark:hover:text-gray-200',
            )}
            onClick={() => setTab(key)}
          >
            {key === 'general' ? t('server_settings.tab_general') : t('server_settings.tab_network')}
          </button>
        ))}
      </div>

      {tab === 'general' && (
        <div className="grid gap-6 lg:grid-cols-2">
          <div className="card space-y-4 p-6">
            <h2 className="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white">
              <Globe className="h-5 w-5 text-primary-500" />
              {t('server_settings.hostname_section')}
            </h2>
            <p className="text-sm text-gray-500 dark:text-gray-400">{t('server_settings.hostname_hint')}</p>
            <div>
              <label className="label">{t('server_settings.hostname')}</label>
              <input
                className="input w-full font-mono"
                value={hostname}
                onChange={(e) => setHostname(e.target.value)}
                placeholder="server.example.com"
              />
            </div>
          </div>

          <div className="card space-y-4 p-6">
            <h2 className="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white">
              <Clock className="h-5 w-5 text-primary-500" />
              {t('server_settings.time_section')}
            </h2>
            <div className="grid gap-3 sm:grid-cols-2 text-sm">
              <div className="rounded-lg bg-gray-50 p-3 dark:bg-gray-800/60">
                <p className="text-gray-500">{t('server_settings.server_time')}</p>
                <p className="mt-1 font-medium text-gray-900 dark:text-white">{localTimeLabel}</p>
              </div>
              <div className="rounded-lg bg-gray-50 p-3 dark:bg-gray-800/60">
                <p className="text-gray-500">{t('server_settings.ntp_status')}</p>
                <p className="mt-1 font-medium">
                  {settings?.ntp_synchronized ? (
                    <span className="text-emerald-600 dark:text-emerald-400">{t('server_settings.ntp_on')}</span>
                  ) : (
                    <span className="text-amber-600 dark:text-amber-400">{t('server_settings.ntp_off')}</span>
                  )}
                </p>
              </div>
              <div className="rounded-lg bg-gray-50 p-3 dark:bg-gray-800/60 sm:col-span-2">
                <p className="text-gray-500">{t('server_settings.panel_timezone')}</p>
                <p className="mt-1 font-mono text-sm text-gray-700 dark:text-gray-300">{panelTz}</p>
              </div>
            </div>
            <div>
              <label className="label">{t('server_settings.timezone')}</label>
              <input
                className="input mb-2 w-full text-sm"
                value={tzFilter}
                onChange={(e) => setTzFilter(e.target.value)}
                placeholder={t('server_settings.timezone_search')}
              />
              <select
                className="input w-full font-mono text-sm"
                value={timezone}
                onChange={(e) => setTimezone(e.target.value)}
              >
                {timezoneOptions.map((z) => (
                  <option key={z} value={z}>
                    {z}
                  </option>
                ))}
              </select>
              <p className="mt-1 text-xs text-gray-500">{t('server_settings.timezone_hint')}</p>
            </div>
            <button
              type="button"
              className="btn-primary inline-flex items-center gap-2"
              disabled={saveM.isPending || !hostname.trim() || !timezone.trim()}
              onClick={() => saveM.mutate()}
            >
              <Save className="h-4 w-4" />
              {t('common.save')}
            </button>
          </div>
        </div>
      )}

      {tab === 'network' && (
        <div className="space-y-6">
          <div className="card flex gap-3 p-4 text-sm text-gray-600 dark:text-gray-300">
            <Info className="mt-0.5 h-5 w-5 shrink-0 text-primary-500" />
            <p>{t('server_settings.network_info')}</p>
          </div>

          <div className="card p-6">
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
              <h2 className="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white">
                <Network className="h-5 w-5 text-primary-500" />
                {t('server_settings.interfaces')}
              </h2>
              <button
                type="button"
                className="btn-secondary inline-flex items-center gap-2 text-sm"
                disabled={refreshM.isPending}
                onClick={() => refreshM.mutate()}
              >
                <RefreshCw className={clsx('h-4 w-4', refreshM.isPending && 'animate-spin')} />
                {t('server_settings.refresh_network')}
              </button>
            </div>

            {settingsQ.isLoading ? (
              <p className="py-8 text-center text-gray-500">{t('common.loading')}</p>
            ) : interfaces.length === 0 ? (
              <p className="py-8 text-center text-gray-500">{t('common.no_data')}</p>
            ) : (
              <div className="space-y-4">
                {interfaces.map((iface) => (
                  <div
                    key={iface.name}
                    className="rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden"
                  >
                    <div className="flex flex-wrap items-center justify-between gap-2 bg-gray-50 px-4 py-3 dark:bg-gray-800/50">
                      <div>
                        <span className="font-mono font-semibold text-gray-900 dark:text-white">{iface.name}</span>
                        {iface.mac && (
                          <span className="ml-2 text-xs text-gray-500">MAC {iface.mac}</span>
                        )}
                      </div>
                      <span className="text-xs text-gray-500">MTU {iface.mtu ?? '—'}</span>
                    </div>
                    <div className="divide-y divide-gray-100 dark:divide-gray-800">
                      {iface.addresses.map((addr) => (
                        <div
                          key={`${iface.name}-${addr.address}`}
                          className="flex flex-wrap items-center justify-between gap-2 px-4 py-3"
                        >
                          <div>
                            <span className="font-mono text-sm text-gray-900 dark:text-white">{addr.address}</span>
                            <div className="mt-1 flex flex-wrap gap-2">
                              {addr.primary && (
                                <span className="rounded-full bg-primary-100 px-2 py-0.5 text-xs text-primary-800 dark:bg-primary-900/40 dark:text-primary-300">
                                  {t('server_settings.primary_badge')}
                                </span>
                              )}
                              {addr.managed && (
                                <span className="rounded-full bg-sky-100 px-2 py-0.5 text-xs text-sky-800 dark:bg-sky-900/40 dark:text-sky-300">
                                  {t('server_settings.managed_badge')}
                                </span>
                              )}
                              {addr.label && (
                                <span className="text-xs text-gray-500">{addr.label}</span>
                              )}
                            </div>
                          </div>
                          {addr.managed && !addr.primary && (
                            <button
                              type="button"
                              className="inline-flex items-center gap-1 rounded-lg border border-red-200 px-2 py-1 text-xs text-red-600 hover:bg-red-50 dark:border-red-900/40 dark:text-red-400 dark:hover:bg-red-900/20"
                              disabled={removeIpM.isPending}
                              onClick={() => {
                                if (
                                  window.confirm(
                                    t('server_settings.confirm_remove_ip', { address: addr.address }),
                                  )
                                ) {
                                  removeIpM.mutate({
                                    iface: iface.name,
                                    address: addr.address,
                                  })
                                }
                              }}
                            >
                              <Trash2 className="h-3 w-3" />
                              {t('common.delete')}
                            </button>
                          )}
                        </div>
                      ))}
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          <div className="card p-6">
            <h3 className="mb-4 flex items-center gap-2 font-semibold text-gray-900 dark:text-white">
              <Plus className="h-5 w-5 text-primary-500" />
              {t('server_settings.add_ip')}
            </h3>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <div>
                <label className="label">{t('server_settings.interface')}</label>
                <select className="input w-full font-mono" value={addIface} onChange={(e) => setAddIface(e.target.value)}>
                  {interfaces.map((i) => (
                    <option key={i.name} value={i.name}>
                      {i.name}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="label">{t('server_settings.ip_address')}</label>
                <input
                  className="input w-full font-mono"
                  value={addAddress}
                  onChange={(e) => setAddAddress(e.target.value)}
                  placeholder="203.0.113.10/32"
                />
              </div>
              <div>
                <label className="label">{t('server_settings.ip_label')}</label>
                <input
                  className="input w-full"
                  value={addLabel}
                  onChange={(e) => setAddLabel(e.target.value)}
                  placeholder={t('server_settings.ip_label_placeholder')}
                />
              </div>
              <div className="flex items-end">
                <button
                  type="button"
                  className="btn-primary w-full inline-flex items-center justify-center gap-2"
                  disabled={addIpM.isPending || !addIface || !addAddress.trim()}
                  onClick={() => addIpM.mutate()}
                >
                  <Plus className="h-4 w-4" />
                  {t('server_settings.add_ip_btn')}
                </button>
              </div>
            </div>
            <p className="mt-3 text-xs text-gray-500">{t('server_settings.add_ip_hint')}</p>
          </div>
        </div>
      )}
    </div>
  )
}
