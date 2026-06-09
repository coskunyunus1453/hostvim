import type { ProcessTopRow } from '../types'

/** Süreç CPU’sunu üst kartla aynı ölçekte (0–100, tüm çekirdekler) gösterir. */
export function processCpuOfTotal(p: ProcessTopRow, logicalCores?: number): number {
  if (p.cpu_percent_of_total != null && !Number.isNaN(p.cpu_percent_of_total)) {
    return p.cpu_percent_of_total
  }
  const cores = logicalCores && logicalCores > 0 ? logicalCores : 1
  return (p.cpu_percent ?? 0) / cores
}
