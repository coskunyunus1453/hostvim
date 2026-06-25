package nodeapp

import (
	"fmt"
	"os"
	"path/filepath"
	"strings"
	"time"

	"panelze/engine/internal/config"
	"panelze/engine/internal/sites"
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

type reconcileTarget struct {
	scope SiteScope
	meta  *sites.SiteMeta
}

func listNodeAppTargets(webRoot string) ([]reconcileTarget, error) {
	var out []reconcileTarget
	domains, err := sites.ListDomains(webRoot)
	if err != nil {
		return nil, err
	}
	for _, d := range domains {
		sc := scopeFrom(d, "")
		meta, err := sc.readMeta(webRoot)
		if err == nil && meta != nil && meta.NodeApp != nil && meta.NodeApp.Enabled {
			out = append(out, reconcileTarget{scope: sc, meta: meta})
		}
		subDir := filepath.Join(webRoot, d, ".panelze", "subdomains")
		entries, err := os.ReadDir(subDir)
		if err != nil {
			continue
		}
		for _, ent := range entries {
			if ent.IsDir() || !strings.HasSuffix(ent.Name(), ".json") {
				continue
			}
			seg := strings.TrimSuffix(ent.Name(), ".json")
			subSc := scopeFrom(d, seg)
			subMeta, err := subSc.readMeta(webRoot)
			if err == nil && subMeta != nil && subMeta.NodeApp != nil && subMeta.NodeApp.Enabled {
				out = append(out, reconcileTarget{scope: subSc, meta: subMeta})
			}
		}
	}
	return out, nil
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

	targets, err := listNodeAppTargets(cfg.Paths.WebRoot)
	if err != nil {
		return report, err
	}

	for _, t := range targets {
		if !t.meta.NodeApp.AutoStart {
			continue
		}
		report.Checked++
		item := ReconcileItem{Domain: t.scope.label()}

		if _, _, err := syncNodeListenPort(cfg, t.scope, t.meta); err == nil {
			if fresh, rerr := t.scope.readMeta(cfg.Paths.WebRoot); rerr == nil && fresh != nil {
				t.meta = fresh
				_ = t.scope.applyWebServer(cfg, t.meta)
			}
		}

		healthy, reason := isAppHealthy(cfg, t.scope, t.meta)
		if healthy {
			item.Action = "already_healthy"
			if reason != "" {
				item.Message = reason
			}
			report.AlreadyHealthy++
			report.Items = append(report.Items, item)
			continue
		}

		if reason == "pm2_errored" {
			if _, err := Restart(cfg, t.scope.ParentDomain, t.scope.PathSegment); err == nil {
				if ok, _ := isAppHealthy(cfg, t.scope, t.meta); ok {
					item.Action = "restarted"
					item.Message = reason
					report.Restarted++
					report.Items = append(report.Items, item)
					continue
				}
			}
		}

		if _, err := startWithPrep(cfg, t.scope.ParentDomain, t.scope.PathSegment); err != nil {
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

func isAppHealthy(cfg *config.Config, sc SiteScope, meta *sites.SiteMeta) (bool, string) {
	if meta == nil || meta.NodeApp == nil {
		return true, ""
	}
	port := meta.NodeApp.ListenPort
	if port <= 0 {
		port = DefaultPortForProfile(meta.NodeApp.Profile)
	}
	if port > 0 && isPortListening(port) {
		return true, ""
	}
	workDir := meta.NodeApp.WorkDir
	if workDir == "" {
		workDir = "."
	}
	if workAbs, _, err := sc.resolveWorkAbs(cfg.Paths.WebRoot, workDir); err == nil {
		if buildInProgress(workAbs) {
			return true, "build_in_progress"
		}
	}
	st, _ := StatusOf(cfg, sc, meta)
	if st.Running {
		if port > 0 {
			if isPortListening(port) {
				return true, ""
			}
			if waitForListen(port, 3*time.Second) == nil {
				return true, ""
			}
			return false, "port_not_listening"
		}
		return true, ""
	}
	if strings.EqualFold(st.Status, "errored") || strings.EqualFold(st.Status, "stopped") {
		return false, "pm2_errored"
	}
	return false, "pm2_not_running"
}

func portListening(port int, timeout time.Duration) bool {
	return isPortListening(port) || waitForListen(port, timeout) == nil
}
