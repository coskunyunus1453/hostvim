package nodeapp

import (
	"time"

	"panelze/engine/internal/config"
	"github.com/sirupsen/logrus"
)

// RunWatchdog periyodik olarak Node uygulamalarını kontrol eder (PM2 + port).
func RunWatchdog(cfg *config.Config, log *logrus.Logger) {
	if !cfg.Hosting.ManageNodeApps || !cfg.Hosting.NodeWatchdogEnabled {
		return
	}
	interval := cfg.Hosting.NodeWatchdogIntervalSeconds
	if interval <= 0 {
		interval = 60
	}

	go func() {
		time.Sleep(15 * time.Second)
		if report, err := ReconcileAll(cfg); err != nil {
			log.Warnf("node watchdog initial reconcile: %v", err)
		} else if report != nil && (report.Started > 0 || report.Restarted > 0 || report.Failed > 0) {
			log.Infof("node watchdog initial: checked=%d started=%d restarted=%d failed=%d",
				report.Checked, report.Started, report.Restarted, report.Failed)
		}

		ticker := time.NewTicker(time.Duration(interval) * time.Second)
		defer ticker.Stop()
		for range ticker.C {
			report, err := ReconcileAll(cfg)
			if err != nil {
				log.Warnf("node watchdog reconcile: %v", err)
				continue
			}
			if report == nil {
				continue
			}
			if report.Failed > 0 {
				log.Warnf("node watchdog: checked=%d started=%d restarted=%d failed=%d",
					report.Checked, report.Started, report.Restarted, report.Failed)
			} else if report.Started > 0 || report.Restarted > 0 || report.Pm2Resurrected {
				log.Infof("node watchdog: checked=%d started=%d restarted=%d pm2_resurrected=%v",
					report.Checked, report.Started, report.Restarted, report.Pm2Resurrected)
			}
		}
	}()
}
