import { useEffect, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Archive, FileArchive } from 'lucide-react'
import FileProgressBar from './FileProgressBar'
import FileProgressModal from './FileProgressModal'
import { estimateArchiveProgress, fmtElapsed } from './fileProgressUtils'
import { useSmoothProgress } from './useSmoothProgress'

type Props = {
  open: boolean
  kind: 'zip' | 'unzip' | null
  complete: boolean
}

export default function FileArchiveProgressOverlay({ open, kind, complete }: Props) {
  const { t } = useTranslation()
  const [targetPct, setTargetPct] = useState(0)
  const [elapsedSec, setElapsedSec] = useState(0)
  const startRef = useRef<number | null>(null)
  const rafRef = useRef<number>(0)

  useEffect(() => {
    if (!open || !kind) {
      setTargetPct(0)
      setElapsedSec(0)
      startRef.current = null
      return
    }

    if (complete) {
      setTargetPct(100)
      return
    }

    startRef.current = performance.now()
    const tick = () => {
      if (startRef.current == null) return
      const sec = (performance.now() - startRef.current) / 1000
      setElapsedSec(sec)
      setTargetPct(estimateArchiveProgress(sec))
      rafRef.current = requestAnimationFrame(tick)
    }

    rafRef.current = requestAnimationFrame(tick)
    return () => cancelAnimationFrame(rafRef.current)
  }, [open, kind, complete])

  const displayPct = useSmoothProgress(complete ? 100 : targetPct, complete ? 0.35 : 0.2)

  if (!open || !kind) return null

  const title = kind === 'zip' ? t('files.archive_progress_zip') : t('files.archive_progress_unzip')
  const subtitle =
    kind === 'zip' ? t('files.archive_progress_zip_hint') : t('files.archive_progress_unzip_hint')
  const status = complete
    ? t('files.archive_finishing')
    : elapsedSec > 45
      ? t('files.archive_wait_long')
      : t('files.archive_wait')

  return (
    <FileProgressModal
      open={open}
      title={title}
      subtitle={subtitle}
      icon={kind === 'zip' ? <Archive className="h-5 w-5" /> : <FileArchive className="h-5 w-5" />}
      busy={!complete}
      footer={
        <span>
          {t('files.archive_elapsed')}:{' '}
          <span className="font-medium text-slate-200">{fmtElapsed(elapsedSec)}</span>
        </span>
      }
    >
      <div className="space-y-1.5">
        <div className="flex items-center justify-between gap-2 text-xs text-slate-400">
          <span>{status}</span>
          <span className="tabular-nums font-medium text-slate-200">{Math.round(displayPct)}%</span>
        </div>
        <FileProgressBar value={displayPct} tone="violet" indeterminate={!complete && displayPct < 3} />
      </div>
    </FileProgressModal>
  )
}
