/** Sekme arka plandayken polling durdurur — CPU/ağ tasarrufu. */
export function pollWhenVisible(intervalMs: number): number | false {
  if (typeof document !== 'undefined' && document.hidden) {
    return false
  }
  return intervalMs
}
