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

type SpeedPhase = 'idle' | 'ping' | 'download' | 'upload' | 'done'

function sleep(ms: number) {
  return new Promise((r) => setTimeout(r, ms))
}

export default function CuriousPage() {
  const { t } = useTranslation()
  const token = useAuthStore((s) => s.token)
  const [tab, setTab] = useState<TabId>('speed')

  const [speedPhase, setSpeedPhase] = useState<SpeedPhase>('idle')
  const [pingMs, setPingMs] = useState<number | null>(null)
  const [downloadMbps, setDownloadMbps] = useState<number | null>(null)
  const [uploadMbps, setUploadMbps] = useState<number | null>(null)
  const [speedRunning, setSpeedRunning] = useState(false)

  const [seoUrl, setSeoUrl] = useState('')
  const [seoProgress, setSeoProgress] = useState(0)
  const [seoPhaseLabel, setSeoPhaseLabel] = useState('')
  const [seoRunning, setSeoRunning] = useState(false)
  const [seoReport, setSeoReport] = useState<SeoReport | null>(null)

  const cleanupRef = useRef(false)

  const runCleanup = useCallback(async () => {
    try {
      await api.post('/curious/speed/cleanup')
    } catch {
      /* ignore */
    }
  }, [])

  useEffect(() => {
    return () => {
      if (!cleanupRef.current) {
        void runCleanup()
      }
    }
  }, [runCleanup])

  const measurePing = async (): Promise<number> => {
    const samples: number[] = []
    for (let i = 0; i < 5; i++) {
      const t0 = performance.now()
      await api.get('/curious/speed/ping')
      samples.push(performance.now() - t0)
      await sleep(80)
    }
    samples.sort((a, b) => a - b)

    return Math.round(samples[Math.floor(samples.length / 2)] ?? samples[0] ?? 0)
  }

  const measureDownload = async (): Promise<number> => {
    const { data } = await api.post('/curious/speed/download/prepare')
    const tok = String(data?.result?.token ?? '')
    const bytes = Number(data?.result?.bytes ?? 0)
    if (!tok || bytes <= 0) throw new Error('prepare failed')

    const url = `${apiBaseUrl}/curious/speed/download/${encodeURIComponent(tok)}`
    const t0 = performance.now()
    const res = await fetch(url, {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
      cache: 'no-store',
    })
    if (!res.ok) throw new Error('download failed')
    await res.blob()
    const sec = (performance.now() - t0) / 1000

    return (bytes * 8) / sec / 1_000_000
  }

  const measureUpload = async (): Promise<number> => {
    const size = Math.min(2_097_152, 1_048_576)
    const blob = new Blob([new Uint8Array(size)])
    const fd = new FormData()
    fd.append('payload', blob, 'speedtest.bin')
    const t0 = performance.now()
    const { data } = await api.post('/curious/speed/upload', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    const sec = (performance.now() - t0) / 1000
    const serverMbps = Number(data?.result?.mbps ?? 0)
    if (serverMbps > 0) return serverMbps

    const bytes = Number(data?.result?.bytes ?? size)

    return (bytes * 8) / sec / 1_000_000
  }

  const runSpeedTest = async () => {
    setSpeedRunning(true)
    setPingMs(null)
    setDownloadMbps(null)
    setUploadMbps(null)
    setSpeedPhase('ping')
    try {
      const p = await measurePing()
      setPingMs(p)
      setSpeedPhase('download')
      const d = await measureDownload()
      setDownloadMbps(d)
      setSpeedPhase('upload')
      const u = await measureUpload()
      setUploadMbps(u)
      setSpeedPhase('done')
      cleanupRef.current = true
      await runCleanup()
      toast.success(t('curious.speed.done'))
    } catch (err: unknown) {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
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
    const tick = window.setInterval(() => {
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
      window.clearInterval(tick)
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

          {speedPhase === 'done' && (
            <div className="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50/80 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800/50 dark:bg-emerald-950/30 dark:text-emerald-200">
              <CheckCircle2 className="h-5 w-5 shrink-0" />
              {t('curious.speed.done')}
            </div>
          )}
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
                  placeholder="https://ornek.com"
                  disabled={seoRunning}
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
                      <dt className="text-gray-500">H1</dt>
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
