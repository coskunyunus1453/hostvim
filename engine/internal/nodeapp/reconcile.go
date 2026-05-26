package nodeapp

import (
	"fmt"
	"strings"
	"time"

	"hostvim/engine/internal/config"
	"hostvim/engine/internal/sites"
)

// ReconcileItem tek domain için watchdog sonucu.
type ReconcileItem struct {
	Domain  string `json:"domain"`
	Action  string `json:"action"`
	Message string `json:"message,omitempty"`
	Error   string `json:"error,omitempty"`
}

// ReconcileReport tüm Node uygulamaları için özet.
type ReconcileReport struct {
	Checked        int             `json:"checked"`
	Started        int             `json:"started"`
	Restarted      int             `json:"restarted"`
	AlreadyHealthy int             `json:"already_healthy"`
	Failed         int             `json:"failed"`
	Pm2Resurrected bool            `json:"pm2_resurrected,omitempty"`
	Items          []ReconcileItem `json:"items"`
}

// ReconcileAll etkin ve auto_start açık Node uygulamalarını kontrol eder; düşmüşse yeniden başlatır.
func ReconcileAll(cfg *config.Config) (*ReconcileReport, error) {
	if !cfg.Hosting.ManageNodeApps {
		return nil, fmt.Errorf("manage_node_apps devre dışı")
	}

	report := &ReconcileReport{Items: []ReconcileItem{}}
	if resurrected, err := EnsurePm2Daemon(cfg); err != nil {
		return report, fmt.Errorf("pm2 daemon: %w", err)
	} else if resurrected {
		report.Pm2Resurrected = true
	}

	domains, err := sites.ListDomains(cfg.Paths.WebRoot)
	if err != nil {
		return report, err
	}

	for _, domain := range domains {
		meta, err := sites.ReadSiteMeta(cfg.Paths.WebRoot, domain)
		if err != nil || meta == nil || meta.NodeApp == nil {
			continue
		}
		if !meta.NodeApp.Enabled {
			continue
		}

		report.Checked++
		item := ReconcileItem{Domain: domain}

		healthy, reason := isAppHealthy(cfg, domain, meta)
		if healthy {
			item.Action = "already_healthy"
			report.AlreadyHealthy++
			report.Items = append(report.Items, item)
			continue
		}

		if reason == "pm2_errored" {
			if _, err := Restart(cfg, domain); err == nil {
				if ok, _ := isAppHealthy(cfg, domain, meta); ok {
					item.Action = "restarted"
					item.Message = reason
					report.Restarted++
					report.Items = append(report.Items, item)
					continue
				}
			}
		}

		if _, err := Start(cfg, domain); err != nil {
			item.Action = "failed"
			item.Error = err.Error()
			report.Failed++
		} else {
			item.Action = "started"
			item.Message = reason
			report.Started++
		}
		report.Items = append(report.Items, item)
	}

	return report, nil
}

func isAppHealthy(cfg *config.Config, domain string, meta *sites.SiteMeta) (bool, string) {
	st, _ := StatusOf(cfg, domain, meta)
	if !st.Running {
		if strings.EqualFold(st.Status, "errored") || strings.EqualFold(st.Status, "stopped") {
			return false, "pm2_errored"
		}
		return false, "pm2_not_running"
	}
	port := 0
	if meta.NodeApp != nil {
		port = meta.NodeApp.ListenPort
		if port <= 0 {
			port = DefaultPortForProfile(meta.NodeApp.Profile)
		}
	}
	if port > 0 && !portListening(port, 2*time.Second) {
		return false, "port_not_listening"
	}
	return true, ""
}

func portListening(port int, timeout time.Duration) bool {
	return waitForListen(port, timeout) == nil
}
