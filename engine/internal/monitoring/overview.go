package monitoring

import (
	"strings"

	"github.com/shirou/gopsutil/v3/cpu"
	"github.com/shirou/gopsutil/v3/load"
	"github.com/shirou/gopsutil/v3/mem"
)

// CollectOverview dashboard kartları için hızlı özet (süreç listesi yok).
func CollectOverview(rootPath string) ExtendedSnapshot {
	base := Collect(rootPath)
	out := ExtendedSnapshot{SystemSnapshot: base}

	if infos, err := cpu.Info(); err == nil && len(infos) > 0 {
		out.CPUModel = strings.TrimSpace(infos[0].ModelName)
	}
	if n, err := cpu.Counts(true); err == nil {
		out.CPUCoresLogical = n
	}
	if v, err := mem.VirtualMemory(); err == nil {
		out.MemoryAvailable = v.Available
	}
	if s, err := mem.SwapMemory(); err == nil {
		out.SwapTotal = s.Total
		out.SwapUsed = s.Used
		out.SwapPercent = s.UsedPercent
	}
	if avg, err := load.Avg(); err == nil && avg != nil {
		out.Load1 = avg.Load1
		out.Load5 = avg.Load5
		out.Load15 = avg.Load15
	}
	dr, dw, rx, tx := ComputeIORates()
	out.DiskReadBytesPerSec = dr
	out.DiskWriteBytesPerSec = dw
	out.NetRxBytesPerSec = rx
	out.NetTxBytesPerSec = tx

	return out
}
