import clsx from 'clsx'

type Props = {
  label: string
  value: number | null
  unit: string
  max?: number
  active?: boolean
  color?: 'indigo' | 'emerald' | 'sky' | 'amber'
  size?: number
}

const COLORS = {
  indigo: { stroke: '#6366f1', glow: 'rgba(99,102,241,0.35)' },
  emerald: { stroke: '#10b981', glow: 'rgba(16,185,129,0.35)' },
  sky: { stroke: '#0ea5e9', glow: 'rgba(14,165,233,0.35)' },
  amber: { stroke: '#f59e0b', glow: 'rgba(245,158,11,0.35)' },
}

export default function SpeedGauge({
  label,
  value,
  unit,
  max = 1000,
  active = false,
  color = 'indigo',
  size = 168,
}: Props) {
  const stroke = 10
  const r = (size - stroke) / 2
  const c = 2 * Math.PI * r
  const pct = value != null && max > 0 ? Math.min(1, Math.max(0, value / max)) : 0
  const offset = c * (1 - pct)
  const palette = COLORS[color]
  const display = value != null ? (value >= 100 ? Math.round(value) : value.toFixed(1)) : '—'

  return (
    <div className="flex flex-col items-center">
      <div className="relative" style={{ width: size, height: size }}>
        <svg width={size} height={size} className={clsx(active && 'animate-pulse')}>
          <circle
            cx={size / 2}
            cy={size / 2}
            r={r}
            fill="none"
            stroke="currentColor"
            strokeWidth={stroke}
            className="text-gray-200 dark:text-gray-700"
          />
          <circle
            cx={size / 2}
            cy={size / 2}
            r={r}
            fill="none"
            stroke={palette.stroke}
            strokeWidth={stroke}
            strokeLinecap="round"
            strokeDasharray={c}
            strokeDashoffset={offset}
            transform={`rotate(-90 ${size / 2} ${size / 2})`}
            style={{
              transition: 'stroke-dashoffset 0.6s ease-out',
              filter: active ? `drop-shadow(0 0 8px ${palette.glow})` : undefined,
            }}
          />
        </svg>
        <div className="absolute inset-0 flex flex-col items-center justify-center text-center px-2">
          <span className="text-2xl font-bold tabular-nums text-gray-900 dark:text-white">{display}</span>
          <span className="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">{unit}</span>
        </div>
      </div>
      <p className="mt-2 text-sm font-medium text-gray-700 dark:text-gray-300">{label}</p>
    </div>
  )
}
