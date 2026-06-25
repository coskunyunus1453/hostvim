import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '../services/api'
import { Cpu, Play, Square, RotateCcw, Wand2, Package, Hammer, ShieldCheck } from 'lucide-react'
import toast from 'react-hot-toast'
import {
  useHostingTargets,
  targetSelectValue,
  parseTargetSelectValue,
  useAutoHostingTargetSelection,
} from '../hooks/useHostingTargets'

type WorkDirCandidate = {
  rel: string
  label: string
  package_name?: string
  has_start?: boolean
}

type NodeDetect = {
  has_package_json?: boolean
  profile?: string
  work_dir?: string
  start_script?: string
  suggested_port?: number
  scripts?: string[]
  package_name?: string
  work_dir_candidates?: WorkDirCandidate[]
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
  const targetsQ = useHostingTargets()
  const [domainId, setDomainId] = useState<number | ''>('')
  const [subdomainId, setSubdomainId] = useState<number | undefined>(undefined)
  const [lastOut, setLastOut] = useState('')
  const actionTargetRef = useRef('')

  const targets = targetsQ.data ?? []

  useAutoHostingTargetSelection(targets, domainId, subdomainId, setDomainId, setSubdomainId)

  const currentTarget = useMemo(
    () =>
      targets.find((t) =>
        t.domain_id === domainId && (subdomainId ? t.subdomain_id === subdomainId : t.subdomain_id === null),
      ),
    [targets, domainId, subdomainId],
  )
  const selectedTargetValue = useMemo(() => {
    if (domainId === '') return ''
    return subdomainId ? `s:${domainId}:${subdomainId}` : `d:${domainId}`
  }, [domainId, subdomainId])
  const selectedTargetKey = useMemo(
    () => (domainId === '' ? '' : subdomainId ? `s:${domainId}:${subdomainId}` : `d:${domainId}`),
    [domainId, subdomainId],
  )

  const nodeReqConfig = useMemo(
    () => (subdomainId ? { params: { subdomain_id: subdomainId } } : undefined),
    [subdomainId],
  )

  const configQ = useQuery({
    queryKey: ['node-app', domainId, subdomainId],
    enabled: domainId !== '',
    queryFn: async () => {
      const { data } = await api.get<NodeConfig>(`/domains/${domainId}/node-app`, nodeReqConfig)
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
  const [workDirCandidates, setWorkDirCandidates] = useState<WorkDirCandidate[]>([])
  const [npmScripts, setNpmScripts] = useState<string[]>(['start'])
  const detectTargetRef = useRef('')

  useEffect(() => {
    if (selectedTargetKey === '') return
    setWorkDirCandidates([])
    setNpmScripts(['start'])
    setLastOut('')
    detectTargetRef.current = selectedTargetKey
    actionTargetRef.current = selectedTargetKey
  }, [selectedTargetKey])

  useEffect(() => {
    const c = configQ.data
    if (!c || selectedTargetKey === '') return
    setForm({
      enabled: Boolean(c.enabled),
      profile: c.profile || c.app_profile || 'node',
      work_dir: c.work_dir || '.',
      start_script: c.start_script || 'start',
      listen_port: c.listen_port || 3000,
      auto_start: c.auto_start ?? true,
      env_file: c.env_file || '.env',
    })
  }, [configQ.data, selectedTargetKey])

  const applyDetect = useCallback(
    (det?: NodeDetect, opts?: { mergeSaved?: boolean }) => {
      if (!det) return
      if (det.work_dir_candidates?.length) {
        setWorkDirCandidates(det.work_dir_candidates)
      }
      if (!det.has_package_json) {
        toast.error(t('node_apps.no_package_json'))
        return
      }
      if (det.scripts?.length) {
        setNpmScripts(det.scripts)
      }
      const saved = configQ.data
      const mergeSaved = opts?.mergeSaved ?? false
      setForm((f) => ({
        ...f,
        work_dir: mergeSaved && saved?.work_dir ? saved.work_dir : (det.work_dir || f.work_dir),
        profile: mergeSaved && (saved?.profile || saved?.app_profile)
          ? (saved.profile || saved.app_profile || f.profile)
          : (det.profile || f.profile),
        start_script: mergeSaved && saved?.start_script ? saved.start_script : (det.start_script || f.start_script),
        listen_port:
          mergeSaved && saved?.listen_port
            ? saved.listen_port
            : (det.suggested_port || f.listen_port),
      }))
      if (!mergeSaved) {
        toast.success(t('node_apps.detected', { name: det.package_name || 'package.json' }))
      }
    },
    [configQ.data, t],
  )

  const detectM = useMutation({
    mutationFn: async ({ workDir, targetKey }: { workDir?: string; targetKey: string }) => {
      const { data } = await api.post<{ detect?: NodeDetect }>(
        `/domains/${domainId}/node-app/detect`,
        { work_dir: workDir ?? form.work_dir },
        nodeReqConfig,
      )
      return { detect: data.detect, targetKey }
    },
    onSuccess: ({ detect, targetKey }) => {
      if (targetKey !== detectTargetRef.current) return
      applyDetect(detect, { mergeSaved: Boolean(configQ.data?.enabled || configQ.data?.listen_port) })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const saveM = useMutation({
    mutationFn: async ({ targetKey }: { targetKey: string }) => {
      const { data } = await api.put(
        `/domains/${domainId}/node-app`,
        { ...form, app_profile: form.profile },
        nodeReqConfig,
      )
      return { data, targetKey }
    },
    onSuccess: ({ targetKey }) => {
      if (targetKey !== actionTargetRef.current) return
      toast.success(t('node_apps.saved'))
      qc.invalidateQueries({ queryKey: ['node-app', domainId, subdomainId] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const autoM = useMutation({
    mutationFn: async ({ targetKey }: { targetKey: string }) => {
      const { data } = await api.post(
        `/domains/${domainId}/node-app/auto-configure`,
        { app_profile: form.profile },
        nodeReqConfig,
      )
      return { data, targetKey }
    },
    onSuccess: ({ data, targetKey }) => {
      if (targetKey !== actionTargetRef.current) return
      toast.success(t('node_apps.auto_configured'))
      qc.invalidateQueries({ queryKey: ['node-app', domainId, subdomainId] })
      const cfg = data as NodeConfig
      if (cfg?.work_dir) {
        setForm((f) => ({
          ...f,
          enabled: Boolean(cfg.enabled),
          profile: cfg.profile || cfg.app_profile || f.profile,
          work_dir: cfg.work_dir || f.work_dir,
          start_script: cfg.start_script || f.start_script,
          listen_port: cfg.listen_port || f.listen_port,
          auto_start: cfg.auto_start ?? f.auto_start,
          env_file: cfg.env_file || f.env_file,
        }))
      }
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string; output?: string } } }
      if (ax.response?.data?.output) setLastOut(ax.response.data.output)
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const healM = useMutation({
    mutationFn: async ({ targetKey }: { targetKey: string }) => {
      const { data } = await api.post<{
        steps?: string[]
        healthy?: boolean
        message?: string
        config?: NodeConfig
        output?: string
      }>(`/domains/${domainId}/node-app/heal`, {}, nodeReqConfig)
      return { data, targetKey }
    },
    onSuccess: ({ data, targetKey }) => {
      if (targetKey !== actionTargetRef.current) return
      if (data.steps?.length) setLastOut(data.steps.join('\n'))
      if (data.config) {
        const cfg = data.config
        setForm((f) => ({
          ...f,
          enabled: Boolean(cfg.enabled),
          profile: cfg.profile || cfg.app_profile || f.profile,
          work_dir: cfg.work_dir || f.work_dir,
          start_script: cfg.start_script || f.start_script,
          listen_port: cfg.listen_port || f.listen_port,
          auto_start: cfg.auto_start ?? f.auto_start,
          env_file: cfg.env_file || f.env_file,
        }))
      }
      toast.success(data.healthy ? t('node_apps.heal_ok') : t('node_apps.heal_partial'))
      qc.invalidateQueries({ queryKey: ['node-app', domainId, subdomainId] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string; steps?: string[] } } }
      if (ax.response?.data?.steps?.length) setLastOut(ax.response.data.steps.join('\n'))
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const actionM = useMutation({
    mutationFn: async ({
      action,
      targetKey,
    }: {
      action: 'start' | 'stop' | 'restart' | 'install' | 'build'
      targetKey: string
    }) => {
      const path =
        action === 'install'
          ? `/domains/${domainId}/node-app/install`
          : action === 'build'
            ? `/domains/${domainId}/node-app/build`
            : `/domains/${domainId}/node-app/${action}`
      const body = action === 'install' ? { use_ci: true } : {}
      const { data } = await api.post(path, body, nodeReqConfig)
      return { data: data as { output?: string; message?: string }, targetKey }
    },
    onSuccess: ({ data, targetKey }) => {
      if (targetKey !== actionTargetRef.current) return
      setLastOut(data.output ?? data.message ?? '')
      toast.success(t('node_apps.action_ok'))
      qc.invalidateQueries({ queryKey: ['node-app', domainId, subdomainId] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string; output?: string } } }
      if (ax.response?.data?.output) setLastOut(ax.response.data.output)
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  useEffect(() => {
    if (domainId === '' || selectedTargetKey === '') {
      setWorkDirCandidates([])
      return
    }
    detectTargetRef.current = selectedTargetKey
    detectM.mutate({ workDir: '.', targetKey: selectedTargetKey })
    // eslint-disable-next-line react-hooks/exhaustive-deps -- hedef değişince otomatik algıla
  }, [domainId, subdomainId, selectedTargetKey])

  const running = configQ.data?.status?.running

  const workDirOptions = useMemo(() => {
    const fromApi = workDirCandidates
    if (fromApi.length > 0) return fromApi
    return [{ rel: '.', label: t('node_apps.work_dir_root') }]
  }, [workDirCandidates, t])

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
              value={selectedTargetValue}
              onChange={(e) => {
                if (!e.target.value) {
                  setDomainId('')
                  setSubdomainId(undefined)
                  return
                }
                const p = parseTargetSelectValue(e.target.value)
                setDomainId(Number(p.domainId))
                setSubdomainId(p.subdomainId ? Number(p.subdomainId) : undefined)
              }}
            >
              <option value="">{t('common.select')}</option>
              {targets.map((ht) => (
                <option key={ht.key} value={targetSelectValue(ht)}>
                  {ht.kind === 'subdomain' ? `↳ ${ht.hostname}` : ht.hostname}
                </option>
              ))}
            </select>
          </div>
          {currentTarget && (
            <div className="text-sm text-gray-500 dark:text-gray-400">
              <span className="font-medium text-gray-700 dark:text-gray-300">{currentTarget.hostname}</span>
              {' · '}
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
                <select
                  className="input w-full font-mono text-sm"
                  value={form.work_dir}
                  onChange={(e) => {
                    const rel = e.target.value
                    setForm((f) => ({ ...f, work_dir: rel }))
                    detectM.mutate({ workDir: rel, targetKey: selectedTargetKey })
                  }}
                >
                  {workDirOptions.map((c) => (
                    <option key={c.rel} value={c.rel}>
                      {c.label}
                      {c.package_name ? ` — ${c.package_name}` : ''}
                      {c.has_start ? '' : ` (${t('node_apps.no_start_script')})`}
                    </option>
                  ))}
                </select>
                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{t('node_apps.work_dir_hint')}</p>
              </div>
              <div>
                <label className="label">{t('node_apps.start_script')}</label>
                <select
                  className="input w-full font-mono text-sm"
                  value={form.start_script}
                  onChange={(e) => setForm((f) => ({ ...f, start_script: e.target.value }))}
                >
                  {npmScripts.map((s) => (
                    <option key={s} value={s}>
                      npm run {s}
                    </option>
                  ))}
                </select>
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
              {form.enabled && (
                <p className="text-xs text-gray-500 dark:text-gray-400 md:col-span-2">{t('node_apps.watchdog_hint')}</p>
              )}
            </div>

            <div className="flex flex-wrap gap-2">
              <button type="button" className="btn-secondary" onClick={() => detectM.mutate({ workDir: form.work_dir, targetKey: selectedTargetKey })} disabled={detectM.isPending}>
                <Wand2 className="h-4 w-4 inline mr-1" />
                {t('node_apps.detect')}
              </button>
              <button type="button" className="btn-primary" onClick={() => saveM.mutate({ targetKey: selectedTargetKey })} disabled={saveM.isPending}>
                {t('common.save')}
              </button>
              <button type="button" className="btn-secondary" onClick={() => autoM.mutate({ targetKey: selectedTargetKey })} disabled={autoM.isPending}>
                {t('node_apps.auto_configure')}
              </button>
              <button
                type="button"
                className="btn-secondary"
                onClick={() => healM.mutate({ targetKey: selectedTargetKey })}
                disabled={healM.isPending || !form.enabled}
              >
                <ShieldCheck className="h-4 w-4 inline mr-1" />
                {t('node_apps.heal')}
              </button>
              <button
                type="button"
                className="btn-secondary"
                onClick={() => actionM.mutate({ action: 'install', targetKey: selectedTargetKey })}
                disabled={actionM.isPending}
              >
                <Package className="h-4 w-4 inline mr-1" />
                npm ci
              </button>
              <button
                type="button"
                className="btn-secondary"
                onClick={() => actionM.mutate({ action: 'build', targetKey: selectedTargetKey })}
                disabled={actionM.isPending}
              >
                <Hammer className="h-4 w-4 inline mr-1" />
                npm run build
              </button>
              <button
                type="button"
                className="btn-primary"
                onClick={() => actionM.mutate({ action: 'start', targetKey: selectedTargetKey })}
                disabled={actionM.isPending || !form.enabled}
              >
                <Play className="h-4 w-4 inline mr-1" />
                {t('node_apps.start')}
              </button>
              <button
                type="button"
                className="btn-secondary"
                onClick={() => actionM.mutate({ action: 'restart', targetKey: selectedTargetKey })}
                disabled={actionM.isPending || !form.enabled}
              >
                <RotateCcw className="h-4 w-4 inline mr-1" />
                {t('node_apps.restart')}
              </button>
              <button
                type="button"
                className="btn-danger"
                onClick={() => actionM.mutate({ action: 'stop', targetKey: selectedTargetKey })}
                disabled={actionM.isPending || !form.enabled}
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
