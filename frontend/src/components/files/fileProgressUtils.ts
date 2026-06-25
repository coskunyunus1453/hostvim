export function fmtBytes(n: number): string {
  if (!Number.isFinite(n) || n < 0) return '—'
  if (n < 1024) return `${Math.round(n)} B`
  if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`
  if (n < 1024 * 1024 * 1024) return `${(n / (1024 * 1024)).toFixed(2)} MB`
  return `${(n / (1024 * 1024 * 1024)).toFixed(2)} GB`
}

export function fmtRate(bps: number): string {
  if (!Number.isFinite(bps) || bps <= 0) return '—'
  return `${fmtBytes(bps)}/s`
}

export function fmtEta(sec: number): string {
  if (!Number.isFinite(sec) || sec < 0 || sec > 86400) return '…'
  if (sec < 90) return `${Math.max(1, Math.ceil(sec))} sn`
  const m = Math.floor(sec / 60)
  const s = Math.ceil(sec % 60)
  return `${m} dk ${s} sn`
}

export function fmtElapsed(sec: number): string {
  if (!Number.isFinite(sec) || sec < 0) return '0 sn'
  if (sec < 60) return `${Math.max(1, Math.floor(sec))} sn`
  const m = Math.floor(sec / 60)
  const s = Math.floor(sec % 60)
  return s > 0 ? `${m} dk ${s} sn` : `${m} dk`
}

/** Sunucu yanıtı beklenirken üst sınıra yaklaşan tahmini ilerleme */
export function estimateArchiveProgress(seconds: number): number {
  if (seconds < 4) {
    return 8 + seconds * 5
  }
  if (seconds < 30) {
    return 28 + (seconds - 4) * 1.6
  }
  if (seconds < 120) {
    return 70 + (seconds - 30) * 0.18
  }
  return Math.min(96, 86 + (seconds - 120) * 0.04)
}
