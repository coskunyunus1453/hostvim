import { useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { CheckCircle2, ChevronDown, Loader2, RefreshCw, X, XCircle } from 'lucide-react'

export type PanelUpdatePhase = 'running' | 'success' | 'failed'

export type PanelUpdateModalState = {
  phase: PanelUpdatePhase
  runId: number | null
  fromVersion: string
  toVersion: string
  title?: string
  changelog?: string
  progress?: number
  message?: string
  output?: string
  startedAt?: number
}

type Props = {
  state: PanelUpdateModalState
  onClose: () => void
}

function formatDuration(seconds: number): string {
  if (seconds < 60) return `${seconds} sn`
  const m = Math.floor(seconds / 60)
  const s = seconds % 60
  return `${m} dk ${s} sn`
}

export default function PanelUpdateResultModal({ state, onClose }: Props) {
  const { t } = useTranslation()
  const [showOutput, setShowOutput] = useState(false)

  const elapsed = useMemo(() => {
    if (!state.startedAt) return 0
    return Math.max(0, Math.round((Date.now() - state.startedAt) / 1000))
  }, [state.startedAt, state.phase, state.progress])

  const done = state.phase !== 'running'
  const progress = state.phase === 'success' ? 100 : Math.min(95, Math.max(5, state.progress ?? 5))
  const changelog = (state.changelog ?? '').trim()
  const output = (state.output ?? '').trim()

  return (
    <div className="fixed inset-0 z-[90] flex items-center justify-center bg-black/50 p-4">
      <div
        className="w-full max-w-2xl overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-900"
        role="dialog"
        aria-modal="true"
        aria-labelledby="panel-update-modal-title"
      >
        <div className="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-800">
          <div className="flex items-start gap-3">
            <span
              className={
                state.phase === 'success'
                  ? 'mt-0.5 text-green-600 dark:text-green-400'
                  : state.phase === 'failed'
                    ? 'mt-0.5 text-rose-600 dark:text-rose-400'
                    : 'mt-0.5 text-sky-600 dark:text-sky-400'
              }
            >
              {state.phase === 'success' ? (
                <CheckCircle2 className="h-6 w-6" />
              ) : state.phase === 'failed' ? (
                <XCircle className="h-6 w-6" />
              ) : (
                <Loader2 className="h-6 w-6 animate-spin" />
              )}
            </span>
            <div>
              <h2 id="panel-update-modal-title" className="text-lg font-semibold text-gray-900 dark:text-white">
                {state.phase === 'success'
                  ? t('panel_update.modal_success_title')
                  : state.phase === 'failed'
                    ? t('panel_update.modal_failed_title')
                    : t('panel_update.modal_running_title')}
              </h2>
              <p className="mt-0.5 font-mono text-sm text-gray-500 dark:text-gray-400">
                {state.fromVersion} → {state.toVersion}
              </p>
            </div>
          </div>
          {done && (
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

        <div className="max-h-[60vh] space-y-4 overflow-y-auto px-5 py-4">
          <div>
            <div className="mb-1 flex justify-between text-xs text-gray-500 dark:text-gray-400">
              <span>
                {state.phase === 'running' && t('panel_update.in_progress')}
                {state.phase === 'success' && t('panel_update.modal_done')}
                {state.phase === 'failed' && t('panel_update.modal_failed')}
              </span>
              <span>
                {elapsed > 0
                  ? t('panel_update.modal_elapsed', { time: formatDuration(elapsed) })
                  : `${progress}%`}
              </span>
            </div>
            <div className="h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
              <div
                className={`h-full transition-all duration-500 ${
                  state.phase === 'failed' ? 'bg-rose-500' : state.phase === 'success' ? 'bg-green-500' : 'bg-sky-500'
                }`}
                style={{ width: `${progress}%` }}
              />
            </div>
            {state.message && (
              <p className="mt-2 text-sm text-gray-700 dark:text-gray-300">{state.message}</p>
            )}
          </div>

          {changelog && state.phase !== 'failed' && (
            <div>
              <h3 className="mb-1.5 text-sm font-semibold text-gray-900 dark:text-white">
                {t('panel_update.modal_changes')}
              </h3>
              <pre className="max-h-52 overflow-y-auto whitespace-pre-wrap rounded-lg border border-gray-200 bg-gray-50 p-3 font-sans text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300">
                {state.title ? `${state.title}\n\n` : ''}
                {changelog}
              </pre>
            </div>
          )}

          {state.phase === 'success' && (
            <p className="rounded-lg bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950/40 dark:text-green-200">
              {t('panel_update.modal_reload_hint')}
            </p>
          )}

          {output && (
            <div>
              <button
                type="button"
                className="inline-flex items-center gap-1.5 text-xs text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200"
                onClick={() => setShowOutput((v) => !v)}
              >
                <ChevronDown className={`h-3.5 w-3.5 transition-transform ${showOutput ? 'rotate-180' : ''}`} />
                {t('panel_update.modal_output')}
              </button>
              {showOutput && (
                <pre className="mt-2 max-h-52 overflow-auto rounded-lg bg-gray-950 p-3 font-mono text-[10px] leading-relaxed text-gray-200">
                  {output.slice(-6000)}
                </pre>
              )}
            </div>
          )}
        </div>

        {done && (
          <div className="flex flex-wrap justify-end gap-2 border-t border-gray-100 px-5 py-3 dark:border-gray-800">
            <button type="button" className="btn-secondary" onClick={onClose}>
              {state.phase === 'success' ? t('panel_update.modal_later') : t('common.close')}
            </button>
            {state.phase === 'success' && (
              <button type="button" className="btn-primary inline-flex items-center gap-1.5" onClick={() => window.location.reload()}>
                <RefreshCw className="h-4 w-4" />
                {t('panel_update.modal_reload')}
              </button>
            )}
          </div>
        )}
      </div>
    </div>
  )
}
