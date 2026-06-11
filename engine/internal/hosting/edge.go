package hosting

import "panelze/engine/internal/config"

// NginxEdgeProxyEnabled nginx 80/443 üzerinde SSL sonlandırıp Apache/OLS backend'e proxy eder.
func NginxEdgeProxyEnabled(cfg *config.Config) bool {
	if cfg == nil {
		return false
	}
	return cfg.Hosting.NginxEdgeProxy && cfg.Hosting.NginxManageVhosts
}

func ApacheBackendPort(cfg *config.Config) int {
	if cfg == nil {
		return 8080
	}
	p := cfg.Hosting.ApacheHTTPPort
	if p <= 0 {
		return 8080
	}
	return p
}

func OLSBackendPort(cfg *config.Config) int {
	if cfg == nil {
		return 8088
	}
	p := cfg.Hosting.OLSHTTPPort
	if p <= 0 {
		return 8088
	}
	return p
}
