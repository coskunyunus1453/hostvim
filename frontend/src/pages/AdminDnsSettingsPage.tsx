import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Navigate } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Globe, Save, Loader2 } from 'lucide-react'
import toast from 'react-hot-toast'
import { useAuthStore } from '../store/authStore'
import api from '../services/api'

type DnsSettings = {
  persisted: boolean
  ns1: string
  ns2: string
  server_ip: string
  bind_enabled: boolean
  bootstrap_defaults: boolean
  detected_server_ip: string
}

export default function AdminDnsSettingsPage() {
  const { t } = useTranslation()
  const qc = useQueryClient()
  const user = useAuthStore((s) => s.user)
  const isAdmin = user?.roles?.some((r) => r.name === 'admin')

  const q = useQuery({
    queryKey: ['admin-dns-settings'],
    queryFn: async () => (await api.get<DnsSettings>('/admin/settings/dns')).data,
    enabled: !!isAdmin,
  })

  const [ns1, setNs1] = useState('')
  const [ns2, setNs2] = useState('')
  const [serverIp, setServerIp] = useState('')
  const [bindEnabled, setBindEnabled] = useState(true)
  const [bootstrapDefaults, setBootstrapDefaults] = useState(true)

  useEffect(() => {
    if (!q.data) return
    setNs1(q.data.ns1 || '')
    setNs2(q.data.ns2 || '')
    setServerIp(q.data.server_ip || q.data.detected_server_ip || '')
    setBindEnabled(q.data.bind_enabled)
    setBootstrapDefaults(q.data.bootstrap_defaults)
  }, [q.data])

  const saveM = useMutation({
    mutationFn: async () =>
      api.put('/admin/settings/dns', {
        ns1,
        ns2,
        server_ip: serverIp,
        bind_enabled: bindEnabled,
        bootstrap_defaults: bootstrapDefaults,
      }),
    onSuccess: () => {
      toast.success(t('dns.settings_saved'))
      qc.invalidateQueries({ queryKey: ['admin-dns-settings'] })
      qc.invalidateQueries({ queryKey: ['dns'] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  if (!isAdmin) return <Navigate to="/" replace />

  return (
    <div className="space-y-6 max-w-2xl">
      <div className="flex items-center gap-3">
        <Globe className="h-8 w-8 text-secondary-500" />
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{t('dns.admin_settings_title')}</h1>
          <p className="text-sm text-gray-500 dark:text-gray-400">{t('dns.admin_settings_subtitle')}</p>
        </div>
      </div>

      <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/40">
        <p className="text-sm text-amber-900 dark:text-amber-100">{t('dns.admin_settings_once')}</p>
      </div>

      {q.isLoading ? (
        <div className="flex items-center gap-2 text-gray-500">
          <Loader2 className="h-5 w-5 animate-spin" />
          {t('common.loading')}
        </div>
      ) : (
        <form
          className="card p-6 space-y-4"
          onSubmit={(e) => {
            e.preventDefault()
            saveM.mutate()
          }}
        >
          <div>
            <label className="label">{t('dns.ns1')}</label>
            <input
              className="input w-full font-mono"
              value={ns1}
              onChange={(e) => setNs1(e.target.value)}
              placeholder="ns1.ornek.com"
              required
            />
          </div>
          <div>
            <label className="label">{t('dns.ns2')}</label>
            <input
              className="input w-full font-mono"
              value={ns2}
              onChange={(e) => setNs2(e.target.value)}
              placeholder="ns2.ornek.com"
              required
            />
          </div>
          <div>
            <label className="label">{t('dns.server_ip')}</label>
            <input
              className="input w-full font-mono"
              value={serverIp}
              onChange={(e) => setServerIp(e.target.value)}
              required
            />
            {q.data?.detected_server_ip && q.data.detected_server_ip !== serverIp && (
              <button
                type="button"
                className="mt-1 text-xs text-secondary-600 hover:underline"
                onClick={() => setServerIp(q.data?.detected_server_ip ?? '')}
              >
                {t('dns.use_detected_ip', { ip: q.data.detected_server_ip })}
              </button>
            )}
          </div>
          <label className="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              checked={bindEnabled}
              onChange={(e) => setBindEnabled(e.target.checked)}
            />
            <span className="text-sm">{t('dns.bind_enabled')}</span>
          </label>
          <label className="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              checked={bootstrapDefaults}
              onChange={(e) => setBootstrapDefaults(e.target.checked)}
            />
            <span className="text-sm">{t('dns.bootstrap_on_create')}</span>
          </label>
          <p className="text-sm text-gray-500 dark:text-gray-400">{t('dns.glue_registrar_hint')}</p>
          <button type="submit" className="btn-primary flex items-center gap-2" disabled={saveM.isPending}>
            {saveM.isPending ? <Loader2 className="h-4 w-4 animate-spin" /> : <Save className="h-4 w-4" />}
            {t('common.save')}
          </button>
        </form>
      )}
    </div>
  )
}
