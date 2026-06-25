package nodeapp

import (
	"os"
	"path/filepath"
	"strconv"
	"strings"

	"panelze/engine/internal/config"
	"panelze/engine/internal/sites"
)

// pm2StartPlan PM2 start komutu — package.json içindeki -p bayrağını yok sayarak panel portunu kullanır.
type pm2StartPlan struct {
	Program string
	Args    []string
}

func syncNodeListenPort(cfg *config.Config, sc SiteScope, meta *sites.SiteMeta) (port int, changed bool, err error) {
	if meta == nil || meta.NodeApp == nil {
		return 0, false, nil
	}
	profile := strings.TrimSpace(meta.NodeApp.Profile)
	if profile == "" {
		profile = strings.TrimSpace(meta.AppProfile)
	}
	workDir := meta.NodeApp.WorkDir
	siteBase := sc.siteBase(cfg.Paths.WebRoot)
	allocated := AllocateListenPort(cfg, sc, profile, 0, siteBase, workDir)
	if allocated <= 0 {
		return 0, false, nil
	}
	changed = meta.NodeApp.ListenPort != allocated
	meta.NodeApp.ListenPort = allocated
	if changed {
		err = sc.writeMeta(cfg.Paths.WebRoot, meta)
	}
	return allocated, changed, err
}

func buildPm2StartPlan(workAbs, profile, script string, port int) (pm2StartPlan, error) {
	profile = strings.ToLower(strings.TrimSpace(profile))
	script = strings.TrimSpace(script)
	if script == "" {
		script = "start"
	}
	portStr := strconv.Itoa(port)
	host := "127.0.0.1"

	switch profile {
	case "nextjs":
		bin := filepath.Join(workAbs, "node_modules", ".bin", "next")
		if st, err := os.Stat(bin); err == nil && !st.IsDir() {
			return pm2StartPlan{
				Program: bin,
				Args:    []string{"start", "-H", host, "-p", portStr},
			}, nil
		}
	case "nuxt":
		bin := filepath.Join(workAbs, "node_modules", ".bin", "nuxt")
		if st, err := os.Stat(bin); err == nil && !st.IsDir() {
			return pm2StartPlan{
				Program: bin,
				Args:    []string{"start", "--hostname", host, "--port", portStr},
			}, nil
		}
	case "strapi":
		bin := filepath.Join(workAbs, "node_modules", ".bin", "strapi")
		if st, err := os.Stat(bin); err == nil && !st.IsDir() {
			return pm2StartPlan{
				Program: bin,
				Args:    []string{"start"},
			}, nil
		}
	}

	return pm2StartPlan{
		Program: "npm",
		Args:    []string{"run", script},
	}, nil
}

func applyNodeListenPortAndVhost(cfg *config.Config, sc SiteScope, meta *sites.SiteMeta) error {
	if meta == nil || meta.NodeApp == nil {
		return nil
	}
	_, changed, err := syncNodeListenPort(cfg, sc, meta)
	if err != nil {
		return err
	}
	if !changed {
		return nil
	}
	fresh, err := sc.readMeta(cfg.Paths.WebRoot)
	if err != nil || fresh == nil {
		return err
	}
	return sc.applyWebServer(cfg, fresh)
}
