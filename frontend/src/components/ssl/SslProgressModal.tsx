import { useEffect, useMemo } from 'react'
import { useTranslation } from 'react-i18next'
import { CheckCircle2, Loader2, XCircle, X } from 'lucide-react'
import type { HostingTarget } from '../../hooks/useHostingTargets'

export type SslJobAction = 'issue' | 'renew' | 'revoke' | 'manual'

export type SslJobState = {
  target: HostingTarget
  action: SslJobAction
  startedAt: number
  progress: number
  stepIndex: number
  logs: string[]
  status: 'running' | 'success' | 'error'
  errorMessage?: string
}

const ACTION_STEP_KEYS: Record<SslJobAction, string[]> = {
  issue: [
    'ssl.progress_preflight',
    'ssl.progress_site',
    'ssl.progress_acme',
    'ssl.progress_cert',
    'ssl.progress_vhost',
  ],
  renew: [
    'ssl.progress_renew_start',
    'ssl.progress_acme',
    'ssl.progress_cert',
    'ssl.progress_vhost',
  ],
  revoke: [
    'ssl.progress_revoke_start',
    'ssl.progress_revoke_engine',
    'ssl.progress_revoke_done',
  ],
  manual: ['ssl.progress_manual_upload', 'ssl.progress_vhost'],
}

const ACTION_DURATION_MS: Record<SslJobAction, number> = {
  issue: 120_000,
  renew: 90_000,
  revoke: 45_000,
  manual: 30_000,
}

export function estimateSslProgress(action: SslJobAction, elapsedMs: number): number {
  const cap = ACTION_DURATION_MS[action]
  return Math.min(92, Math.max(8, Math.round((elapsedMs / cap) * 92)))
}

export function sslStepIndex(action: SslJobAction, progress: number): number {
  const steps = ACTION_STEP_KEYS[action]
  const idx = Math.floor((progress / 100) * steps.length)
  return Math.min(steps.length - 1, Math.max(0, idx))
}

type Props = {
  job: SslJobState | null
  onClose: () => void
  onTick: (progress: number, stepIndex: number) => void
}

export default function SslProgressModal({ job, onClose, onTick }: Props) {
  const { t } = useTranslation()

  const elapsedSec = job ? Math.floor((Date.now() - job.startedAt) / 1000) : 0

  useEffect(() => {
    if (!job || job.status !== 'running') return
    const id = window.setInterval(() => {
      const elapsed = Date.now() - job.startedAt
      const progress = estimateSslProgress(job.action, elapsed)
      onTick(progress, sslStepIndex(job.action, progress))
    }, 400)
    return () => window.clearInterval(id)
  }, [job, onTick])

  const steps = useMemo(() => {
    if (!job) return []
    return ACTION_STEP_KEYS[job.action].map((key, i) => ({
      key,
      done: job.status === 'success' || i < job.stepIndex,
      active: job.status === 'running' && i === job.stepIndex,
    }))
  }, [job])

  if (!job) return null

  const canClose = job.status !== 'running'
  const progress =
    job.status === 'success' ? 100 : job.status === 'error' ? job.progress : job.progress

  return (
    <div className="fixed inset-0 z-[80] flex items-center justify-center bg-black/50 p-4">
      <div
        className="w-full max-w-lg rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-900"
        role="dialog"
        aria-modal="true"
        aria-labelledby="ssl-progress-title"
      >
        <div className="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-800">
          <div>
            <h2 id="ssl-progress-title" className="text-lg font-semibold text-gray-900 dark:text-white">
              {t(`ssl.progress_title_${job.action}`)}
            </h2>
            <p className="mt-0.5 font-mono text-sm text-gray-500">{job.target.hostname}</p>
          </div>
          {canClose && (
            <button
              type="button"
              onClick={onClose}
              className="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800"
              aria-label={t('common.close')}
            >
              <X className="h-5 w-5" />
            </button>
          )}
        </div>

        <div className="space-y-4 px-5 py-4">
          <div>
            <div className="mb-1 flex justify-between text-xs text-gray-500">
              <span>
                {job.status === 'running' && (
                  <span className="inline-flex items-center gap-1.5 text-primary-600 dark:text-primary-400">
                    <Loader2 className="h-3.5 w-3.5 animate-spin" />
                    {t('ssl.progress_running')}
                  </span>
                )}
                {job.status === 'success' && (
                  <span className="inline-flex items-center gap-1.5 text-green-600">
                    <CheckCircle2 className="h-3.5 w-3.5" />
                    {t('ssl.progress_done')}
                  </span>
                )}
                {job.status === 'error' && (
                  <span className="inline-flex items-center gap-1.5 text-red-600">
                    <XCircle className="h-3.5 w-3.5" />
                    {t('ssl.progress_failed')}
                  </span>
                )}
              </span>
              <span>{t('ssl.progress_elapsed', { sec: elapsedSec })}</span>
            </div>
            <div className="h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
              <div
                className={`h-full transition-all duration-500 ease-out ${
                  job.status === 'error'
                    ? 'bg-red-500'
                    : job.status === 'success'
                      ? 'bg-green-500'
                      : 'bg-primary-500'
                }`}
                style={{ width: `${progress}%` }}
              />
            </div>
            <p className="mt-2 text-sm text-gray-700 dark:text-gray-300">
              {t(ACTION_STEP_KEYS[job.action][job.stepIndex] ?? 'ssl.progress_running')}
            </p>
          </div>

          <ul className="space-y-1.5 text-sm">
            {steps.map((step) => (
              <li
                key={step.key}
                className={`flex items-center gap-2 rounded-lg px-2 py-1 ${
                  step.active ? 'bg-primary-50 text-primary-800 dark:bg-primary-900/20 dark:text-primary-200' : ''
                } ${step.done ? 'text-green-700 dark:text-green-400' : 'text-gray-500'}`}
              >
                {step.done ? (
                  <CheckCircle2 className="h-4 w-4 shrink-0" />
                ) : step.active ? (
                  <Loader2 className="h-4 w-4 shrink-0 animate-spin" />
                ) : (
                  <span className="inline-block h-4 w-4 shrink-0 rounded-full border border-gray-300 dark:border-gray-600" />
                )}
                <span>{t(step.key)}</span>
              </li>
            ))}
          </ul>

          {job.logs.length > 0 && (
            <div className="max-h-36 overflow-y-auto rounded-lg bg-gray-50 p-3 font-mono text-xs text-gray-700 dark:bg-gray-950 dark:text-gray-300">
              {job.logs.map((line, i) => (
                <div key={`${i}-${line.slice(0, 24)}`}>{line}</div>
              ))}
            </div>
          )}

          {job.errorMessage && (
            <p className="rounded-lg bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950/40 dark:text-red-200">
              {job.errorMessage}
            </p>
          )}

          {job.status === 'running' && (
            <p className="text-xs text-gray-500">{t('ssl.progress_wait_hint')}</p>
          )}
        </div>

        {canClose && (
          <div className="border-t border-gray-100 px-5 py-3 dark:border-gray-800">
            <button type="button" className="btn-primary w-full" onClick={onClose}>
              {t('common.close')}
            </button>
          </div>
        )}
      </div>
    </div>
  )
}
