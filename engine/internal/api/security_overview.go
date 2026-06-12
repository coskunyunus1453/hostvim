package api

import (
	"github.com/gin-gonic/gin"
	"panelze/engine/internal/config"
	"panelze/engine/internal/panelmirror"
	"panelze/engine/internal/security"
)

func buildSecurityOverviewResponse(cfg *config.Config) gin.H {
	snap := security.CollectStatusSnapshot()
	rules, _ := panelmirror.FirewallRulesList(cfg)
	clamavLast, _ := panelmirror.SecurityGetValue(cfg, "clamav_last_scan")

	overviewForAdvisor := map[string]interface{}{
		"fail2ban": map[string]interface{}{
			"enabled":   snap.Fail2ban.Enabled,
			"installed": snap.Fail2ban.Installed,
		},
		"modsecurity": map[string]interface{}{
			"enabled":   snap.Modsecurity.Enabled,
			"installed": snap.Modsecurity.Installed,
		},
		"clamav": map[string]interface{}{
			"enabled":   snap.Clamav.Enabled,
			"installed": snap.Clamav.Installed,
			"last_scan": nullIfEmpty(clamavLast),
		},
		"firewall": map[string]interface{}{
			"enabled":      snap.Firewall.Enabled,
			"recent_rules": rulesSlice(rules),
		},
	}
	score, items := security.BuildAdvisorReport(overviewForAdvisor)

	return gin.H{
		"fail2ban": gin.H{
			"enabled":   snap.Fail2ban.Enabled,
			"installed": snap.Fail2ban.Installed,
			"jails":     []string{"sshd", "panelze-auth", "panelsar-auth"},
			"settings": func() gin.H {
				b, f, m, e := security.Fail2banJailGet()
				_, jailErr := security.NormalizeComponentError(e)
				return gin.H{
					"bantime":  b,
					"findtime": f,
					"maxretry": m,
					"error":    jailErr,
				}
			}(),
			"error": snap.Fail2ban.Error,
		},
		"firewall": gin.H{
			"enabled":        snap.Firewall.Enabled,
			"backend":        "iptables",
			"default_policy": "DROP",
			"recent_rules":   rules,
		},
		"modsecurity": gin.H{
			"enabled":   snap.Modsecurity.Enabled,
			"installed": snap.Modsecurity.Installed,
			"error":     snap.Modsecurity.Error,
		},
		"clamav": gin.H{
			"enabled":   snap.Clamav.Enabled,
			"installed": snap.Clamav.Installed,
			"last_scan": nullIfEmpty(clamavLast),
			"error":     snap.Clamav.Error,
		},
		"advisor": gin.H{
			"score": score,
			"items": items,
		},
	}
}

func rulesSlice(rules []gin.H) []interface{} {
	out := make([]interface{}, len(rules))
	for i, r := range rules {
		out[i] = r
	}
	return out
}
