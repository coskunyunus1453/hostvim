package monitoring

import (
	"path/filepath"
	"sort"
	"strings"
	"time"

	"github.com/shirou/gopsutil/v3/cpu"
	"github.com/shirou/gopsutil/v3/disk"
	"github.com/shirou/gopsutil/v3/load"
	"github.com/shirou/gopsutil/v3/mem"
	"github.com/shirou/gopsutil/v3/process"
)

// ProcessTop süreç özeti (panel dashboard).
type ProcessTop struct {
	PID               int32   `json:"pid"`
	Name              string  `json:"name"`
	CPUPercent        float64 `json:"cpu_percent"`          // tek çekirdek bazında (çok çekirdekte >100 olabilir)
	CPUPercentOfTotal float64 `json:"cpu_percent_of_total"` // tüm çekirdeklere göre (üst kart ile aynı ölçek)
	RSSBytes          uint64  `json:"rss_bytes"`
}

// MountUsage disk bölümü kullanımı.
type MountUsage struct {
	Path       string  `json:"path"`
	Fstype     string  `json:"fstype,omitempty"`
	UsedPct    float64 `json:"used_percent"`
	UsedBytes  uint64  `json:"used_bytes"`
	TotalBytes uint64  `json:"total_bytes"`
}

// ExtendedSnapshot Collect + CPU/RAM/disk detay ve top listeler.
type ExtendedSnapshot struct {
	SystemSnapshot
	CPUModel             string       `json:"cpu_model"`
	CPUCoresLogical      int          `json:"cpu_cores_logical"`
	MemoryAvailable      uint64       `json:"memory_available"`
	SwapTotal            uint64       `json:"swap_total"`
	SwapUsed             uint64       `json:"swap_used"`
	SwapPercent          float64      `json:"swap_percent"`
	Load1                float64      `json:"load1"`
	Load5                float64      `json:"load5"`
	Load15               float64      `json:"load15"`
	DiskReadBytesPerSec  float64      `json:"disk_read_bytes_per_sec"`
	DiskWriteBytesPerSec float64      `json:"disk_write_bytes_per_sec"`
	NetRxBytesPerSec     float64      `json:"net_rx_bytes_per_sec"`
	NetTxBytesPerSec     float64      `json:"net_tx_bytes_per_sec"`
	TopCPUProcesses      []ProcessTop `json:"top_cpu_processes"`
	TopMemoryProcesses   []ProcessTop `json:"top_memory_processes"`
	TopDiskMounts        []MountUsage `json:"top_disk_mounts"`
}

func skipMountFstype(fs string) bool {
	switch strings.ToLower(fs) {
	case "tmpfs", "devtmpfs", "proc", "sysfs", "cgroup2", "cgroup", "overlay", "squashfs", "autofs", "rpc_pipefs", "configfs", "debugfs", "tracefs", "securityfs", "pstore", "bpf", "fusectl":
		return true
	default:
		return false
	}
}

// CollectExtended tam snapshot (dashboard detayları için).
func CollectExtended(rootPath string) ExtendedSnapshot {
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

	out.TopCPUProcesses = topCPUProcesses(3)
	cores := float64(out.CPUCoresLogical)
	if cores < 1 {
		cores = 1
	}
	for i := range out.TopCPUProcesses {
		out.TopCPUProcesses[i].CPUPercentOfTotal = out.TopCPUProcesses[i].CPUPercent / cores
	}
	out.TopMemoryProcesses = topMemoryProcesses(3)
	out.TopDiskMounts = topDiskMountsByUsage(3, rootPath)

	return out
}

func topDiskMountsByUsage(limit int, preferRoot string) []MountUsage {
	if limit < 1 {
		limit = 3
	}
	if preferRoot == "" {
		preferRoot = "/"
	}
	parts, err := disk.Partitions(false)
	if err != nil {
		return nil
	}
	var rows []MountUsage
	seen := map[string]struct{}{}
	for _, p := range parts {
		if skipMountFstype(p.Fstype) {
			continue
		}
		mp := p.Mountpoint
		if mp == "" {
			continue
		}
		if _, ok := seen[mp]; ok {
			continue
		}
		u, err := disk.Usage(mp)
		if err != nil || u.Total < 64*1024*1024 {
			continue
		}
		seen[mp] = struct{}{}
		rows = append(rows, MountUsage{
			Path:       mp,
			Fstype:     p.Fstype,
			UsedPct:    u.UsedPercent,
			UsedBytes:  u.Used,
			TotalBytes: u.Total,
		})
	}
	sort.Slice(rows, func(i, j int) bool {
		return rows[i].UsedPct > rows[j].UsedPct
	})
	if len(rows) > limit {
		rows = rows[:limit]
	}
	return rows
}

func topCPUProcesses(limit int) []ProcessTop {
	if limit < 1 {
		limit = 3
	}
	return topCPUProcessesGopsutil(limit)
}

func topMemoryProcesses(limit int) []ProcessTop {
	if limit < 1 {
		limit = 3
	}
	return topMemoryProcessesGopsutil(limit)
}

func skipEphemeralProcess(name string) bool {
	base := strings.ToLower(filepath.Base(strings.TrimSpace(name)))
	switch base {
	case "ps", "top", "htop", "btop", "pidof", "pgrep", "sort":
		return true
	default:
		return false
	}
}

func topCPUProcessesGopsutil(limit int) []ProcessTop {
	procs, err := process.Processes()
	if err != nil {
		return nil
	}
	type row struct {
		p   *process.Process
		cpu float64
		rss uint64
		nm  string
	}
	var rows []row
	for _, p := range procs {
		_, _ = p.CPUPercent()
	}
	time.Sleep(300 * time.Millisecond)
	for _, p := range procs {
		cpuP, err := p.CPUPercent()
		if err != nil || cpuP <= 0 {
			continue
		}
		nm, _ := p.Name()
		if skipEphemeralProcess(nm) {
			continue
		}
		var rss uint64
		if mi, _ := p.MemoryInfo(); mi != nil {
			rss = mi.RSS
		}
		rows = append(rows, row{p: p, cpu: cpuP, rss: rss, nm: nm})
	}
	sort.Slice(rows, func(i, j int) bool {
		return rows[i].cpu > rows[j].cpu
	})
	var out []ProcessTop
	for i := 0; i < len(rows) && len(out) < limit; i++ {
		out = append(out, ProcessTop{
			PID:        rows[i].p.Pid,
			Name:       rows[i].nm,
			CPUPercent: rows[i].cpu,
			RSSBytes:   rows[i].rss,
		})
	}
	return out
}

func topMemoryProcessesGopsutil(limit int) []ProcessTop {
	procs, err := process.Processes()
	if err != nil {
		return nil
	}
	type row struct {
		p   *process.Process
		rss uint64
		nm  string
		cpu float64
	}
	var rows []row
	for _, p := range procs {
		mi, err := p.MemoryInfo()
		if err != nil || mi == nil {
			continue
		}
		nm, _ := p.Name()
		cpuP, _ := p.CPUPercent()
		rows = append(rows, row{p: p, rss: mi.RSS, nm: nm, cpu: cpuP})
	}
	sort.Slice(rows, func(i, j int) bool {
		return rows[i].rss > rows[j].rss
	})
	var out []ProcessTop
	for i := 0; i < len(rows) && i < limit; i++ {
		out = append(out, ProcessTop{
			PID:        rows[i].p.Pid,
			Name:       rows[i].nm,
			CPUPercent: rows[i].cpu,
			RSSBytes:   rows[i].rss,
		})
	}
	return out
}
