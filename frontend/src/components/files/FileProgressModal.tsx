import { createPortal } from 'react-dom'
import type { ReactNode } from 'react'
import clsx from 'clsx'

type Props = {
  open: boolean
  title: string
  subtitle?: string
  icon: ReactNode
  busy?: boolean
  children: ReactNode
  footer?: ReactNode
}

export default function FileProgressModal({
  open,
  title,
  subtitle,
  icon,
  busy = true,
  children,
  footer,
}: Props) {
  if (typeof document === 'undefined' || !open) return null

  return createPortal(
    <div
      className="fixed inset-0 z-[200] flex items-center justify-center p-4"
      role="dialog"
      aria-modal="true"
      aria-busy={busy}
    >
      <div className="absolute inset-0 bg-slate-950/60 backdrop-blur-[2px]" />
      <div
        className={clsx(
          'relative w-full max-w-sm rounded-2xl border border-slate-700/50',
          'bg-slate-900/95 p-5 shadow-xl shadow-black/30 sm:p-6',
        )}
      >
        <div className="flex items-start gap-3.5">
          <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-800 text-slate-100">
            {icon}
          </div>
          <div className="min-w-0 flex-1 pt-0.5">
            <h2 className="text-base font-semibold tracking-tight text-white">{title}</h2>
            {subtitle ? <p className="mt-0.5 text-sm text-slate-400">{subtitle}</p> : null}
          </div>
        </div>

        <div className="mt-5 space-y-4">{children}</div>

        {footer ? <div className="mt-4 border-t border-slate-800 pt-4 text-sm text-slate-400">{footer}</div> : null}
      </div>

      <style>{`
        @keyframes panelze-indeterminate {
          0% { transform: translateX(-120%); }
          100% { transform: translateX(320%); }
        }
      `}</style>
    </div>,
    document.body,
  )
}
