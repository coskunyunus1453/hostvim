/** Bayt cinsinden kullanım — küçük değerlerde KB/B gösterir (0 MB yanılgısını önler). */
export function fmtStorageBytes(bytes?: number | null): string {
  if (bytes == null || !Number.isFinite(bytes)) return '—'
  if (bytes === 0) return '0 B'

  const kb = bytes / 1024
  if (kb < 1) return `${Math.round(bytes)} B`
  if (kb < 1024) {
    return kb < 10 ? `${kb.toFixed(1)} KB` : `${Math.round(kb)} KB`
  }

  const mb = kb / 1024
  if (mb < 1024) {
    return mb < 10 ? `${mb.toFixed(1)} MB` : `${Math.round(mb)} MB`
  }

  const gb = mb / 1024
  return `${gb < 10 ? gb.toFixed(1) : Math.round(gb)} GB`
}

/** Paket limitleri MB cinsinden gelir (genelde tam sayı, ≥5120). */
export function fmtStorageLimitMb(mb?: number | null): string {
  if (mb == null || !Number.isFinite(mb)) return '—'
  if (mb >= 1024) return `${Math.round((mb / 1024) * 10) / 10} GB`
  return `${Math.round(mb)} MB`
}

/** Disk doluluk yüzdesi — bayt tabanlı (küçük sitelerde 0% yerine gerçek oran). */
export function storageUsagePercent(usedBytes: number, limitMb: number): number {
  if (limitMb <= 0 || usedBytes < 0) return 0
  const limitBytes = limitMb * 1024 * 1024
  if (limitBytes <= 0) return 0
  return Math.min(100, Math.round((usedBytes / limitBytes) * 100))
}
