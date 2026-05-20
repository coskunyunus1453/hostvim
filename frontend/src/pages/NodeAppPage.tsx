import { useEffect, useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '../services/api'
import { Cpu, Play, Square, RotateCcw, Wand2, Package, Hammer } from 'lucide-react'
import toast from 'react-hot-toast'
import { useDomainsList } from '../hooks/useDomains'

type NodeDetect = {
  has_package_json?: boolean
  profile?: string
  work_dir?: string
  start_script?: string
  suggested_port?: number
  scripts?: string[]
  package_name?: string
}

type NodeConfig = {
  enabled?: boolean
  profile?: string
  app_profile?: string
  work_dir?: string
  start_script?: string
  listen_port?: number
  auto_start?: boolean
  env_file?: string
  status?: {
    running?: boolean
    status?: string
    pm2_name?: string
  }
}

export default function NodeAppPage() {
  const { t } = useTranslation()
  const qc = useQueryClient()
  const domainsQ = useDomainsList()
  const [domainId, setDomainId] = useState<number | ''>('')
  const [lastOut, setLastOut] = useState('')

  const domain = useMemo(
    () => (domainsQ.data ?? []).find((d) => d.id === domainId),
    [domainsQ.data, domainId],
  )

  const configQ = useQuery({
    queryKey: ['node-app', domainId],
    enabled: domainId !== '',
    queryFn: async () => {
      const { data } = await api.get<NodeConfig>(`/domains/${domainId}/node-app`)
      return data
    },
  })

  const [form, setForm] = useState({
    enabled: false,
    profile: 'node',
    work_dir: '.',
    start_script: 'start',
    listen_port: 3000,
    auto_start: true,
    env_file: '.env',
  })

  useEffect(() => {
    const c = configQ.data
    if (!c) return
    setForm({
      enabled: Boolean(c.enabled),
      profile: c.profile || c.app_profile || 'node',
      work_dir: c.work_dir || '.',
      start_script: c.start_script || 'start',
      listen_port: c.listen_port || 3000,
      auto_start: c.auto_start ?? true,
      env_file: c.env_file || '.env',
    })
  }, [configQ.data])

  const detectM = useMutation({
    mutationFn: async () => {
      const { data } = await api.post<{ detect?: NodeDetect }>(`/domains/${domainId}/node-app/detect`, {
        work_dir: form.work_dir,
      })
      return data.detect
    },
    onSuccess: (det) => {
      if (!det?.has_package_json) {
        toast.error(t('node_apps.no_package_json'))
        return
      }
      setForm((f) => ({
        ...f,
        profile: det.profile || f.profile,
        start_script: det.start_script || f.start_script,
        listen_port: det.suggested_port || f.listen_port,
      }))
      toast.success(t('node_apps.detected', { name: det.package_name || 'package.json' }))
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const saveM = useMutation({
    mutationFn: async () => {
      const { data } = await api.put(`/domains/${domainId}/node-app`, {
        ...form,
        app_profile: form.profile,
      })
      return data
    },
    onSuccess: () => {
      toast.success(t('node_apps.saved'))
      qc.invalidateQueries({ queryKey: ['node-app', domainId] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const autoM = useMutation({
    mutationFn: async () => {
      const { data } = await api.post(`/domains/${domainId}/node-app/auto-configure`, {
        app_profile: form.profile,
      })
      return data
    },
    onSuccess: () => {
      toast.success(t('node_apps.auto_configured'))
      qc.invalidateQueries({ queryKey: ['node-app', domainId] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string; output?: string } } }
      if (ax.response?.data?.output) setLastOut(ax.response.data.output)
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const actionM = useMutation({
    mutationFn: async (action: 'start' | 'stop' | 'restart' | 'install' | 'build') => {
      const path =
        action === 'install'
          ? `/domains/${domainId}/node-app/install`
          : action === 'build'
            ? `/domains/${domainId}/node-app/build`
            : `/domains/${domainId}/node-app/${action}`
      const body = action === 'install' ? { use_ci: true } : {}
      const { data } = await api.post(path, body)
      return data as { output?: string; message?: string }
    },
    onSuccess: (data) => {
      setLastOut(data.output ?? data.message ?? '')
      toast.success(t('node_apps.action_ok'))
      qc.invalidateQueries({ queryKey: ['node-app', domainId] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string; output?: string } } }
      if (ax.response?.data?.output) setLastOut(ax.response.data.output)
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const running = configQ.data?.status?.running

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Cpu className="h-8 w-8 text-emerald-500" />
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{t('node_apps.title')}</h1>
          <p className="text-gray-500 dark:text-gray-400 text-sm">{t('node_apps.subtitle')}</p>
        </div>
      </div>

      <div className="card p-4 space-y-4">
        <div className="flex flex-wrap gap-4 items-end">
          <div>
            <label className="label">{t('node_apps.domain')}</label>
            <select
              className="input min-w-[240px]"
              value={domainId}
              onChange={(e) => setDomainId(e.target.value ? Number(e.target.value) : '')}
            >
              <option value="">{t('common.select')}</option>
              {(domainsQ.data ?? []).map((d) => (
                <option key={d.id} value={d.id}>
                  {d.name}
                </option>
              ))}
            </select>
          </div>
          {domain && (
            <div className="text-sm text-gray-500 dark:text-gray-400">
              {running ? (
                <span className="text-emerald-600 dark:text-emerald-400">{t('node_apps.status_running')}</span>
              ) : (
                <span>{t('node_apps.status_stopped')}</span>
              )}
              {configQ.data?.status?.pm2_name ? ` · ${configQ.data.status.pm2_name}` : null}
            </div>
          )}
        </div>

        {domainId !== '' && (
          <>
            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
              <label className="flex items-center gap-2 text-sm">
                <input
                  type="checkbox"
                  checked={form.enabled}
                  onChange={(e) => setForm((f) => ({ ...f, enabled: e.target.checked }))}
                />
                {t('node_apps.enabled')}
              </label>
              <div>
                <label className="label">{t('node_apps.profile')}</label>
                <select
                  className="input w-full"
                  value={form.profile}
                  onChange={(e) => setForm((f) => ({ ...f, profile: e.target.value }))}
                >
                  {['node', 'nextjs', 'nuxt', 'strapi', 'n8n'].map((p) => (
                    <option key={p} value={p}>
                      {p}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="label">{t('node_apps.work_dir')}</label>
                <input
                  className="input w-full"
                  value={form.work_dir}
                  onChange={(e) => setForm((f) => ({ ...f, work_dir: e.target.value }))}
                />
              </div>
              <div>
                <label className="label">{t('node_apps.start_script')}</label>
                <input
                  className="input w-full"
                  value={form.start_script}
                  onChange={(e) => setForm((f) => ({ ...f, start_script: e.target.value }))}
                />
              </div>
              <div>
                <label className="label">{t('node_apps.listen_port')}</label>
                <input
                  type="number"
                  className="input w-full"
                  value={form.listen_port}
                  onChange={(e) => setForm((f) => ({ ...f, listen_port: Number(e.target.value) }))}
                />
              </div>
              <div>
                <label className="label">{t('node_apps.env_file')}</label>
                <input
                  className="input w-full"
                  value={form.env_file}
                  onChange={(e) => setForm((f) => ({ ...f, env_file: e.target.value }))}
                />
              </div>
              <label className="flex items-center gap-2 text-sm md:col-span-2">
                <input
                  type="checkbox"
                  checked={form.auto_start}
                  onChange={(e) => setForm((f) => ({ ...f, auto_start: e.target.checked }))}
                />
                {t('node_apps.auto_start')}
              </label>
            </div>

            <div className="flex flex-wrap gap-2">
              <button type="button" className="btn-secondary" onClick={() => detectM.mutate()} disabled={detectM.isPending}>
                <Wand2 className="h-4 w-4 inline mr-1" />
                {t('node_apps.detect')}
              </button>
              <button type="button" className="btn-primary" onClick={() => saveM.mutate()} disabled={saveM.isPending}>
                {t('common.save')}
              </button>
              <button type="button" className="btn-secondary" onClick={() => autoM.mutate()} disabled={autoM.isPending}>
                {t('node_apps.auto_configure')}
              </button>
              <button
                type="button"
                className="btn-secondary"
                onClick={() => actionM.mutate('install')}
                disabled={actionM.isPending}
              >
                <Package className="h-4 w-4 inline mr-1" />
                npm ci
              </button>
              <button
                type="button"
                className="btn-secondary"
                onClick={() => actionM.mutate('build')}
                disabled={actionM.isPending}
              >
                <Hammer className="h-4 w-4 inline mr-1" />
                npm run build
              </button>
              <button
                type="button"
                className="btn-primary"
                onClick={() => actionM.mutate('start')}
                disabled={actionM.isPending}
              >
                <Play className="h-4 w-4 inline mr-1" />
                {t('node_apps.start')}
              </button>
              <button
                type="button"
                className="btn-secondary"
                onClick={() => actionM.mutate('restart')}
                disabled={actionM.isPending}
              >
                <RotateCcw className="h-4 w-4 inline mr-1" />
                {t('node_apps.restart')}
              </button>
              <button
                type="button"
                className="btn-danger"
                onClick={() => actionM.mutate('stop')}
                disabled={actionM.isPending}
              >
                <Square className="h-4 w-4 inline mr-1" />
                {t('node_apps.stop')}
              </button>
            </div>
          </>
        )}
      </div>

      {lastOut && (
        <pre className="card p-4 text-xs overflow-auto max-h-96 whitespace-pre-wrap font-mono text-gray-800 dark:text-gray-200">
          {lastOut}
        </pre>
      )}
    </div>
  )
}
