import { useTranslation } from 'react-i18next'
import { UploadCloud } from 'lucide-react'
import FileProgressBar from './FileProgressBar'
import FileProgressModal from './FileProgressModal'
import { fmtBytes, fmtEta, fmtRate } from './fileProgressUtils'
import { useSmoothProgress } from './useSmoothProgress'

export type FileUploadProgressView = {
  totalFiles: number
  currentIndex: number
  currentName: string
  currentLoaded: number
  currentTotal: number
  overallLoaded: number
  overallTotal: number
  speedBps: number
}

type Props = {
  open: boolean
  state: FileUploadProgressView | null
}

export default function FileUploadProgressOverlay({ open, state }: Props) {
  const { t } = useTranslation()

  const overallTarget =
    open && state && state.overallTotal > 0
      ? Math.min(100, (state.overallLoaded / state.overallTotal) * 100)
      : 0
  const fileTarget =
    open && state && state.currentTotal > 0
      ? Math.min(100, (state.currentLoaded / state.currentTotal) * 100)
      : 0

  const overallPct = useSmoothProgress(overallTarget)
  const filePct = useSmoothProgress(fileTarget, 0.28)

  if (!open || !state) return null

  const remaining = Math.max(0, state.overallTotal - state.overallLoaded)
  const etaSec = state.speedBps > 400 ? remaining / state.speedBps : NaN

  return (
    <FileProgressModal
      open={open}
      title={t('files.upload_progress_title')}
      subtitle={t('files.upload_progress_tagline')}
      icon={<UploadCloud className="h-5 w-5" strokeWidth={2} />}
      footer={
        <div className="flex flex-wrap items-center justify-between gap-2">
          <span>
            {t('files.upload_speed_label')}{' '}
            <span className="font-medium text-slate-200">{fmtRate(state.speedBps)}</span>
          </span>
          <span>
            {t('files.upload_eta_label')}{' '}
            <span className="font-medium text-slate-200">{fmtEta(etaSec)}</span>
          </span>
        </div>
      }
    >
      <div className="space-y-1.5">
        <div className="flex items-center justify-between gap-2 text-xs text-slate-400">
          <span>
            {t('files.upload_progress_batch', {
              current: state.currentIndex + 1,
              total: state.totalFiles,
            })}
          </span>
          <span className="tabular-nums font-medium text-slate-200">{Math.round(overallPct)}%</span>
        </div>
        <FileProgressBar value={overallPct} tone="primary" />
        <p className="text-xs text-slate-500">
          {fmtBytes(state.overallLoaded)} / {fmtBytes(state.overallTotal)}
        </p>
      </div>

      <div className="space-y-1.5">
        <div className="flex items-center justify-between gap-2 text-xs text-slate-400">
          <span className="truncate" title={state.currentName}>
            {t('files.upload_progress_current_file_bar')}:{' '}
            <span className="text-slate-300">{state.currentName || '—'}</span>
          </span>
          <span className="shrink-0 tabular-nums text-slate-300">{Math.round(filePct)}%</span>
        </div>
        <FileProgressBar value={filePct} size="sm" tone="primary" />
      </div>
    </FileProgressModal>
  )
}
