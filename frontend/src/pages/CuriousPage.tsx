import { useCallback, useEffect, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import api, { apiBaseUrl } from '../services/api'
import { useAuthStore } from '../store/authStore'
import SpeedGauge from '../components/curious/SpeedGauge'
import clsx from 'clsx'
import toast from 'react-hot-toast'
import {
  Gauge,
  Search,
  Play,
  Loader2,
  CheckCircle2,
  AlertTriangle,
  Globe,
  Link2,
} from 'lucide-react'

type TabId = 'speed' | 'seo'

type SeoReport = {
  url: string
  score: number
  summary: string
  response_ms: number
  meta: {
    title?: string
    description?: string
    h1?: string[]
    word_count?: number
    html_size_kb?: number
  }
  links: { internal: number; external: number; nofollow: number }
  categories: Array<{
    id: string
    title: string
    items: Array<{ id: string; status: string; label: string; detail: string }>
  }>
}

type SpeedPhase = 'idle' | 'ping' | 'download' | 'upload' | 'server' | 'done'

type SpeedHistoryRow = {
  id: number
  client_ip: string
  created_at: string
  panel: {
    ping_ms: number | null
    download_mbps: number | null
    upload_mbps: number | null
  }
  server: {
    ping_ms: number | null
    download_mbps: number | null
    upload_mbps: number | null
    label: string | null
    from_cache: boolean
    error: string | null
  }
  delta: {
    ping_ms: number | null
    download_mbps: number | null
    upload_mbps: number | null
  }
}

function fmtDelta(v: number | null | undefined, decimals = 2): string {
  if (v == null || Number.isNaN(v)) return '—'
  const sign = v > 0 ? '+' : ''

  return `${sign}${v.toFixed(decimals)}`
}

function fmtMbps(v: number | null | undefined): string {
  if (v == null) return '—'

  return `${Number(v).toFixed(1)}`
}

function fmtMs(v: number | null | undefined): string {
  if (v == null) return '—'

  return `${Math.round(v)}`
}

function sleep(ms: number) {
  return new Promise((r) => setTimeout(r, ms))
}

/** ~2 MB test; yavaş bağlantılarda zaman aşımı olmasın */
const SPEED_TEST_TIMEOUT_MS = 120_000

export default function CuriousPage() {
  const { t } = useTranslation()
  const token = useAuthStore((s) => s.token)
  const [tab, setTab] = useState<TabId>('speed')

  const [speedPhase, setSpeedPhase] = useState<SpeedPhase>('idle')
  const [pingMs, setPingMs] = useState<number | null>(null)
  const [downloadMbps, setDownloadMbps] = useState<number | null>(null)
  const [uploadMbps, setUploadMbps] = useState<number | null>(null)
  const [serverPingMs, setServerPingMs] = useState<number | null>(null)
  const [serverDownloadMbps, setServerDownloadMbps] = useState<number | null>(null)
  const [serverUploadMbps, setServerUploadMbps] = useState<number | null>(null)
  const [deltaPingMs, setDeltaPingMs] = useState<number | null>(null)
  const [deltaDownloadMbps, setDeltaDownloadMbps] = useState<number | null>(null)
  const [deltaUploadMbps, setDeltaUploadMbps] = useState<number | null>(null)
  const [serverFromCache, setServerFromCache] = useState(false)
  const [serverError, setServerError] = useState<string | null>(null)
  const [historyIp, setHistoryIp] = useState('')
  const [speedHistory, setSpeedHistory] = useState<SpeedHistoryRow[]>([])
  const [historyLoadError, setHistoryLoadError] = useState(false)
  const [speedRunning, setSpeedRunning] = useState(false)

  const [seoUrl, setSeoUrl] = useState('')
  const [seoProgress, setSeoProgress] = useState(0)
  const [seoPhaseLabel, setSeoPhaseLabel] = useState('')
  const [seoRunning, setSeoRunning] = useState(false)
  const [seoReport, setSeoReport] = useState<SeoReport | null>(null)

  const cleanupRef = useRef(false)
  const seoTickRef = useRef<number | null>(null)

  const runCleanup = useCallback(async () => {
    try {
      await api.post('/curious/speed/cleanup')
    } catch {
      /* ignore */
    }
  }, [])

  const loadSpeedHistory = useCallback(async () => {
    try {
      const { data } = await api.get('/curious/speed/history')
      setHistoryLoadError(false)
      setHistoryIp(String(data?.client_ip ?? ''))
      setSpeedHistory((data?.history ?? []) as SpeedHistoryRow[])
    } catch {
      setHistoryLoadError(true)
    }
  }, [])

  useEffect(() => {
    if (tab === 'speed') {
      void loadSpeedHistory()
    }
  }, [tab, loadSpeedHistory])

  useEffect(() => {
    return () => {
      if (seoTickRef.current !== null) {
        window.clearInterval(seoTickRef.current)
      }
      if (!cleanupRef.current) {
        void runCleanup()
      }
    }
  }, [runCleanup])

  const measurePing = async (): Promise<number> => {
    const samples: number[] = []
    for (let i = 0; i < 3; i++) {
      const t0 = performance.now()
      await api.get('/curious/speed/ping')
      samples.push(performance.now() - t0)
      if (i < 2) await sleep(80)
    }
    samples.sort((a, b) => a - b)

    return Math.round(samples[Math.floor(samples.length / 2)] ?? samples[0] ?? 0)
  }

  const measureDownload = async (tok: string, bytes: number): Promise<number> => {
    if (!tok || bytes <= 0) throw new Error('prepare failed')

    const url = `${apiBaseUrl}/curious/speed/download/${encodeURIComponent(tok)}`
    const t0 = performance.now()
    const res = await fetch(url, {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
      cache: 'no-store',
    })
    if (!res.ok) throw new Error('download failed')
    await res.blob()
    const sec = Math.max((performance.now() - t0) / 1000, 0.001)

    return (bytes * 8) / sec / 1_000_000
  }

  const measureUpload = async (expectedBytes: number): Promise<number> => {
    const size = Math.max(256_000, expectedBytes)
    const blob = new Blob([new Uint8Array(size)])
    const fd = new FormData()
    fd.append('payload', blob, 'speedtest.bin')
    const t0 = performance.now()
    await api.post('/curious/speed/upload', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
      timeout: SPEED_TEST_TIMEOUT_MS,
    })
    const sec = Math.max((performance.now() - t0) / 1000, 0.001)
    const mbps = (size * 8) / sec / 1_000_000
    // Sunucu tarafı süre yalnızca dosya disk'e yazıldıktan sonraki işlemdir; Mbps burada hesaplanır.
    if (!Number.isFinite(mbps) || mbps > 10_000) {
      throw new Error('upload measurement unreliable')
    }

    return mbps
  }

  const runSpeedTest = async () => {
    setSpeedRunning(true)
    setPingMs(null)
    setDownloadMbps(null)
    setUploadMbps(null)
    setServerPingMs(null)
    setServerDownloadMbps(null)
    setServerUploadMbps(null)
    setDeltaPingMs(null)
    setDeltaDownloadMbps(null)
    setDeltaUploadMbps(null)
    setServerFromCache(false)
    setServerError(null)
    setSpeedPhase('ping')
    try {
      const p = await measurePing()
      setPingMs(p)
      setSpeedPhase('download')
      const { data: prep } = await api.post('/curious/speed/download/prepare')
      const testBytes = Number(prep?.result?.bytes ?? 2_097_152)
      const dlTok = String(prep?.result?.token ?? '')
      const d = await measureDownload(dlTok, testBytes)
      setDownloadMbps(d)
      setSpeedPhase('upload')
      const u = await measureUpload(testBytes)
      setUploadMbps(u)
      cleanupRef.current = true
      await runCleanup()

      setSpeedPhase('server')
      const { data: complete } = await api.post(
        '/curious/speed/complete',
        {
          panel_ping_ms: p,
          panel_download_mbps: d,
          panel_upload_mbps: u,
        },
        { timeout: 180_000 },
      )
      const record = complete?.record as SpeedHistoryRow | undefined
      if (record) {
        setServerPingMs(record.server.ping_ms)
        setServerDownloadMbps(record.server.download_mbps)
        setServerUploadMbps(record.server.upload_mbps)
        setDeltaPingMs(record.delta.ping_ms)
        setDeltaDownloadMbps(record.delta.download_mbps)
        setDeltaUploadMbps(record.delta.upload_mbps)
        setServerFromCache(Boolean(record.server.from_cache))
        setServerError(record.server.error)
      }
      setHistoryIp(String(complete?.client_ip ?? historyIp))
      setSpeedHistory((complete?.history ?? []) as SpeedHistoryRow[])
      setSpeedPhase('done')
      toast.success(t('curious.speed.done'))
    } catch (err: unknown) {
      const ax = err as { response?: { status?: number; data?: { message?: string } } }
      const status = ax.response?.status ?? 0
      const msg = ax.response?.data?.message ?? String(err)
      toast.error(
        status === 429 ? t('curious.speed.rate_limited') : msg,
      )
      setSpeedPhase('idle')
      await runCleanup()
    } finally {
      setSpeedRunning(false)
    }
  }

  const runSeoAnalysis = async () => {
    const url = seoUrl.trim()
    if (!url) {
      toast.error(t('curious.seo.url_required'))
      return
    }
    setSeoRunning(true)
    setSeoReport(null)
    setSeoProgress(0)
    const phases = [
      t('curious.seo.phase_fetch'),
      t('curious.seo.phase_parse'),
      t('curious.seo.phase_meta'),
      t('curious.seo.phase_links'),
      t('curious.seo.phase_score'),
    ]
    let phaseIdx = 0
    seoTickRef.current = window.setInterval(() => {
      setSeoPhaseLabel(phases[Math.min(phaseIdx, phases.length - 1)] ?? '')
      phaseIdx += 1
      setSeoProgress((p) => Math.min(92, p + 8))
    }, 450)

    try {
      const { data } = await api.post('/curious/seo/analyze', { url })
      setSeoProgress(100)
      setSeoPhaseLabel(t('curious.seo.phase_done'))
      setSeoReport(data.report as SeoReport)
      toast.success(t('curious.seo.done_toast'))
    } catch (err: unknown) {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
      setSeoProgress(0)
      setSeoPhaseLabel('')
    } finally {
      if (seoTickRef.current !== null) {
        window.clearInterval(seoTickRef.current)
        seoTickRef.current = null
      }
      setSeoRunning(false)
    }
  }

  const tabs = [
    { id: 'speed' as const, icon: Gauge, label: t('curious.tabs.speed') },
    { id: 'seo' as const, icon: Search, label: t('curious.tabs.seo') },
  ]

  const scoreColor = (score: number) => {
    if (score >= 85) return 'text-emerald-600 dark:text-emerald-400'
    if (score >= 65) return 'text-sky-600 dark:text-sky-400'
    if (score >= 45) return 'text-amber-600 dark:text-amber-400'
    return 'text-red-600 dark:text-red-400'
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{t('curious.title')}</h1>
          <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">{t('curious.subtitle')}</p>
        </div>
      </div>

      <div className="rounded-2xl border border-gray-200 bg-white p-2 shadow-sm dark:border-gray-700 dark:bg-gray-900/60">
        <div className="flex gap-1 overflow-x-auto">
          {tabs.map(({ id, icon: Icon, label }) => (
            <button
              key={id}
              type="button"
              onClick={() => setTab(id)}
              className={clsx(
                'flex shrink-0 items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium transition-colors',
                tab === id
                  ? 'bg-primary-600 text-white shadow-md shadow-primary-600/20'
                  : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800',
              )}
            >
              <Icon className="h-4 w-4" />
              {label}
            </button>
          ))}
        </div>
      </div>

      {tab === 'speed' && (
        <div className="space-y-6">
          <div className="rounded-2xl border border-indigo-200/80 bg-gradient-to-br from-indigo-50/90 via-white to-violet-50/50 p-6 dark:border-indigo-900/50 dark:from-indigo-950/30 dark:via-gray-900 dark:to-violet-950/20">
            <p className="text-sm text-gray-600 dark:text-gray-400">{t('curious.speed.intro')}</p>
            <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">{t('curious.speed.disclaimer')}</p>
            <button
              type="button"
              className="btn-primary mt-4 inline-flex items-center gap-2"
              onClick={() => void runSpeedTest()}
              disabled={speedRunning}
            >
              {speedRunning ? <Loader2 className="h-4 w-4 animate-spin" /> : <Play className="h-4 w-4" />}
              {speedRunning ? t('curious.speed.running') : t('curious.speed.start')}
            </button>
            <p className="mt-3 text-xs text-gray-500 dark:text-gray-400">{t('curious.speed.cleanup_hint')}</p>
          </div>

          <h3 className="text-sm font-semibold text-gray-800 dark:text-gray-200">
            {t('curious.speed.panel_section')}
          </h3>
          <div className="grid gap-6 sm:grid-cols-3">
            <div className="card flex justify-center p-6">
              <SpeedGauge
                label={t('curious.speed.ping')}
                value={pingMs}
                unit="ms"
                max={200}
                active={speedPhase === 'ping'}
                color="sky"
              />
            </div>
            <div className="card flex justify-center p-6">
              <SpeedGauge
                label={t('curious.speed.download')}
                value={downloadMbps}
                unit="Mbps"
                max={500}
                active={speedPhase === 'download'}
                color="indigo"
              />
            </div>
            <div className="card flex justify-center p-6">
              <SpeedGauge
                label={t('curious.speed.upload')}
                value={uploadMbps}
                unit="Mbps"
                max={500}
                active={speedPhase === 'upload'}
                color="emerald"
              />
            </div>
          </div>

          {(speedPhase === 'server' || serverDownloadMbps != null) && (
            <>
              <h3 className="text-sm font-semibold text-gray-800 dark:text-gray-200">
                {t('curious.speed.server_section')}
                {speedPhase === 'server' && (
                  <span className="ml-2 font-normal text-gray-500">{t('curious.speed.phase_server')}</span>
                )}
              </h3>
              <div className="grid gap-6 sm:grid-cols-3">
                <div className="card flex justify-center p-6">
                  <SpeedGauge
                    label={t('curious.speed.ping')}
                    value={serverPingMs}
                    unit="ms"
                    max={200}
                    active={speedPhase === 'server'}
                    color="amber"
                  />
                </div>
                <div className="card flex justify-center p-6">
                  <SpeedGauge
                    label={t('curious.speed.download')}
                    value={serverDownloadMbps}
                    unit="Mbps"
                    max={2000}
                    active={speedPhase === 'server'}
                    color="indigo"
                  />
                </div>
                <div className="card flex justify-center p-6">
                  <SpeedGauge
                    label={t('curious.speed.upload')}
                    value={serverUploadMbps}
                    unit="Mbps"
                    max={2000}
                    active={speedPhase === 'server'}
                    color="emerald"
                  />
                </div>
              </div>
            </>
          )}

          {speedPhase === 'done' && (
            <>
              <div className="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50/80 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800/50 dark:bg-emerald-950/30 dark:text-emerald-200">
                <CheckCircle2 className="h-5 w-5 shrink-0" />
                {t('curious.speed.done')}
              </div>
              {serverFromCache && (
                <p className="text-xs text-amber-700 dark:text-amber-300">{t('curious.speed.server_cached')}</p>
              )}
              {serverError && (
                <p className="text-xs text-amber-700 dark:text-amber-300">
                  {t('curious.speed.server_error', { msg: serverError })}
                </p>
              )}
              <div className="card p-4">
                <h3 className="text-sm font-semibold text-gray-800 dark:text-gray-200">
                  {t('curious.speed.delta_section')}
                </h3>
                <dl className="mt-3 grid gap-2 text-sm sm:grid-cols-3">
                  <div>
                    <dt className="text-gray-500">{t('curious.speed.ping')}</dt>
                    <dd className="font-mono font-medium text-gray-900 dark:text-white">
                      {fmtDelta(deltaPingMs, 1)} ms
                    </dd>
                  </div>
                  <div>
                    <dt className="text-gray-500">{t('curious.speed.download')}</dt>
                    <dd className="font-mono font-medium text-gray-900 dark:text-white">
                      {fmtDelta(deltaDownloadMbps)} Mbps
                    </dd>
                  </div>
                  <div>
                    <dt className="text-gray-500">{t('curious.speed.upload')}</dt>
                    <dd className="font-mono font-medium text-gray-900 dark:text-white">
                      {fmtDelta(deltaUploadMbps)} Mbps
                    </dd>
                  </div>
                </dl>
              </div>
            </>
          )}

          <div className="card overflow-hidden p-0">
            <div className="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
              <h3 className="text-sm font-semibold text-gray-900 dark:text-white">
                {t('curious.speed.history_title', { ip: historyIp || '—' })}
              </h3>
            </div>
            {historyLoadError && (
              <div className="border-b border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/40 dark:bg-red-950/20 dark:text-red-200">
                <p>{t('curious.speed.history_load_error')}</p>
                <button type="button" className="btn-secondary mt-2 text-xs" onClick={() => void loadSpeedHistory()}>
                  {t('domains.refresh')}
                </button>
              </div>
            )}
            {speedHistory.length === 0 && !historyLoadError ? (
              <p className="px-4 py-6 text-sm text-gray-500">{t('curious.speed.history_empty')}</p>
            ) : (
              <div className="overflow-x-auto">
                <table className="min-w-full text-left text-xs">
                  <thead className="bg-gray-50 text-gray-600 dark:bg-gray-800/80 dark:text-gray-400">
                    <tr>
                      <th className="px-3 py-2 font-medium">{t('curious.speed.history_date')}</th>
                      <th className="px-3 py-2 font-medium" colSpan={3}>
                        {t('curious.speed.history_panel')}
                      </th>
                      <th className="px-3 py-2 font-medium" colSpan={3}>
                        {t('curious.speed.history_server')}
                      </th>
                      <th className="px-3 py-2 font-medium" colSpan={3}>
                        {t('curious.speed.history_delta')}
                      </th>
                    </tr>
                    <tr className="border-t border-gray-200 dark:border-gray-700">
                      <th className="px-3 py-1" />
                      <th className="px-3 py-1">{t('curious.speed.col_ping')}</th>
                      <th className="px-3 py-1">{t('curious.speed.col_download')}</th>
                      <th className="px-3 py-1">{t('curious.speed.col_upload')}</th>
                      <th className="px-3 py-1">{t('curious.speed.col_ping')}</th>
                      <th className="px-3 py-1">{t('curious.speed.col_download')}</th>
                      <th className="px-3 py-1">{t('curious.speed.col_upload')}</th>
                      <th className="px-3 py-1">{t('curious.speed.col_ping')}</th>
                      <th className="px-3 py-1">{t('curious.speed.col_download')}</th>
                      <th className="px-3 py-1">{t('curious.speed.col_upload')}</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                    {speedHistory.map((row) => (
                      <tr key={row.id} className="text-gray-800 dark:text-gray-200">
                        <td className="whitespace-nowrap px-3 py-2 font-mono">
                          {row.created_at
                            ? new Date(row.created_at).toLocaleString()
                            : '—'}
                        </td>
                        <td className="px-3 py-2 font-mono">{fmtMs(row.panel.ping_ms)}</td>
                        <td className="px-3 py-2 font-mono">{fmtMbps(row.panel.download_mbps)}</td>
                        <td className="px-3 py-2 font-mono">{fmtMbps(row.panel.upload_mbps)}</td>
                        <td className="px-3 py-2 font-mono">
                          {row.server.error ? '—' : fmtMs(row.server.ping_ms)}
                        </td>
                        <td className="px-3 py-2 font-mono">
                          {row.server.error ? '—' : fmtMbps(row.server.download_mbps)}
                        </td>
                        <td className="px-3 py-2 font-mono">
                          {row.server.error ? '—' : fmtMbps(row.server.upload_mbps)}
                        </td>
                        <td className="px-3 py-2 font-mono">{fmtDelta(row.delta.ping_ms, 1)}</td>
                        <td className="px-3 py-2 font-mono">{fmtDelta(row.delta.download_mbps)}</td>
                        <td className="px-3 py-2 font-mono">{fmtDelta(row.delta.upload_mbps)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>
      )}

      {tab === 'seo' && (
        <div className="space-y-6">
          <div className="card p-6">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">{t('curious.seo.heading')}</h2>
            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">{t('curious.seo.intro')}</p>
            <div className="mt-4 flex flex-wrap gap-3">
              <div className="min-w-[min(100%,20rem)] flex-1">
                <label className="label">{t('curious.seo.url_label')}</label>
                <input
                  className="input w-full font-mono text-sm"
                  value={seoUrl}
                  onChange={(e) => setSeoUrl(e.target.value)}
                  placeholder={t('curious.seo.url_placeholder')}
                  disabled={seoRunning}
                  onKeyDown={(e) => {
                    if (e.key === 'Enter' && !seoRunning) {
                      e.preventDefault()
                      void runSeoAnalysis()
                    }
                  }}
                />
              </div>
              <div className="flex items-end">
                <button
                  type="button"
                  className="btn-primary inline-flex items-center gap-2"
                  onClick={() => void runSeoAnalysis()}
                  disabled={seoRunning}
                >
                  {seoRunning ? <Loader2 className="h-4 w-4 animate-spin" /> : <Search className="h-4 w-4" />}
                  {t('curious.seo.start')}
                </button>
              </div>
            </div>

            {(seoRunning || seoProgress > 0) && (
              <div className="mt-6">
                <div className="mb-2 flex justify-between text-xs text-gray-500">
                  <span>{seoPhaseLabel || t('curious.seo.phase_fetch')}</span>
                  <span>{seoProgress}%</span>
                </div>
                <div className="h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                  <div
                    className="h-full rounded-full bg-gradient-to-r from-violet-500 to-indigo-600 transition-all duration-300"
                    style={{ width: `${seoProgress}%` }}
                  />
                </div>
              </div>
            )}
          </div>

          {seoReport && (
            <div className="space-y-4">
              <div className="grid gap-4 lg:grid-cols-3">
                <div className="card p-6 lg:col-span-1 flex flex-col items-center justify-center">
                  <p className="text-xs uppercase tracking-wide text-gray-500">{t('curious.seo.score')}</p>
                  <p className={clsx('mt-2 text-5xl font-bold tabular-nums', scoreColor(seoReport.score))}>
                    {seoReport.score}
                  </p>
                  <p className="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">{seoReport.summary}</p>
                  <p className="mt-3 font-mono text-xs text-gray-500 break-all">{seoReport.url}</p>
                </div>
                <div className="card p-6 lg:col-span-2 space-y-3">
                  <h3 className="text-sm font-semibold text-gray-900 dark:text-white">{t('curious.seo.overview')}</h3>
                  <dl className="grid gap-2 text-sm sm:grid-cols-2">
                    <div>
                      <dt className="text-gray-500">{t('curious.seo.meta_title')}</dt>
                      <dd className="font-medium text-gray-900 dark:text-white">{seoReport.meta.title || '—'}</dd>
                    </div>
                    <div>
                      <dt className="text-gray-500">{t('curious.seo.response')}</dt>
                      <dd>{seoReport.response_ms} ms</dd>
                    </div>
                    <div className="sm:col-span-2">
                      <dt className="text-gray-500">{t('curious.seo.meta_desc')}</dt>
                      <dd className="text-gray-800 dark:text-gray-200">{seoReport.meta.description || '—'}</dd>
                    </div>
                    <div>
                      <dt className="text-gray-500">{t('curious.seo.h1_label')}</dt>
                      <dd>{(seoReport.meta.h1 ?? []).join(' · ') || '—'}</dd>
                    </div>
                    <div>
                      <dt className="text-gray-500">{t('curious.seo.words')}</dt>
                      <dd>{seoReport.meta.word_count ?? 0}</dd>
                    </div>
                  </dl>
                  <div className="flex flex-wrap gap-4 pt-2 text-xs text-gray-600 dark:text-gray-400">
                    <span className="inline-flex items-center gap-1">
                      <Link2 className="h-3.5 w-3.5" />
                      {t('curious.seo.links_internal', { n: seoReport.links.internal })}
                    </span>
                    <span className="inline-flex items-center gap-1">
                      <Globe className="h-3.5 w-3.5" />
                      {t('curious.seo.links_external', { n: seoReport.links.external })}
                    </span>
                  </div>
                </div>
              </div>

              {seoReport.categories.map((cat) => (
                <div key={cat.id} className="card p-5">
                  <h3 className="text-base font-semibold text-gray-900 dark:text-white">{cat.title}</h3>
                  <ul className="mt-3 space-y-2">
                    {cat.items.map((item) => (
                      <li
                        key={item.id}
                        className="flex flex-wrap items-start gap-2 rounded-lg border border-gray-100 bg-gray-50/80 px-3 py-2 dark:border-gray-800 dark:bg-gray-900/40"
                      >
                        {item.status === 'pass' ? (
                          <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                        ) : (
                          <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0 text-amber-600" />
                        )}
                        <div className="min-w-0 flex-1">
                          <p className="text-sm font-medium text-gray-900 dark:text-white">{item.label}</p>
                          <p className="text-xs text-gray-500 dark:text-gray-400">{item.detail}</p>
                        </div>
                      </li>
                    ))}
                  </ul>
                </div>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  )
}
