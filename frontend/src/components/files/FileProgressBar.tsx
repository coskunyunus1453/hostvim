import clsx from 'clsx'

type Props = {
  value: number
  size?: 'sm' | 'md'
  tone?: 'primary' | 'violet'
  indeterminate?: boolean
}

export default function FileProgressBar({
  value,
  size = 'md',
  tone = 'primary',
  indeterminate = false,
}: Props) {
  const trackClass = clsx(
    'relative w-full overflow-hidden rounded-full bg-slate-800/60',
    size === 'sm' ? 'h-1.5' : 'h-2',
  )

  const fillClass = clsx(
    'absolute inset-y-0 start-0 rounded-full',
    tone === 'primary' ? 'bg-primary-500' : 'bg-violet-500',
  )

  if (indeterminate) {
    return (
      <div className={trackClass} role="progressbar" aria-busy="true">
        <div className={clsx(fillClass, 'w-[36%] animate-[panelze-indeterminate_1.35s_ease-in-out_infinite]')} />
      </div>
    )
  }

  const width = `${Math.min(100, Math.max(1, value))}%`

  return (
    <div
      className={trackClass}
      role="progressbar"
      aria-valuemin={0}
      aria-valuemax={100}
      aria-valuenow={Math.round(value)}
    >
      <div className={fillClass} style={{ width }} />
    </div>
  )
}
