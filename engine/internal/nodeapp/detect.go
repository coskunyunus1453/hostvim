package nodeapp

import (
	"encoding/json"
	"fmt"
	"os"
	"path/filepath"
	"strings"
)

// DetectResult package.json ve dizin sinyallerinden önerilen Node yapılandırması.
type DetectResult struct {
	HasPackageJSON bool     `json:"has_package_json"`
	Profile        string   `json:"profile"`
	WorkDir        string   `json:"work_dir"`
	StartScript    string   `json:"start_script"`
	SuggestedPort  int      `json:"suggested_port"`
	Scripts        []string `json:"scripts"`
	PackageName    string   `json:"package_name,omitempty"`
}

type pkgJSON struct {
	Name         string            `json:"name"`
	Scripts      map[string]string `json:"scripts"`
	Dependencies map[string]string `json:"dependencies"`
}

// Detect siteBase (public_html) altında package.json arar ve profil önerir.
func Detect(siteBase string, workDirRel string) (*DetectResult, error) {
	siteBase = filepath.Clean(siteBase)
	workDirRel = strings.TrimSpace(workDirRel)
	if workDirRel == "" || workDirRel == "." {
		workDirRel = "."
	}
	if strings.Contains(workDirRel, "..") || filepath.IsAbs(workDirRel) {
		return nil, fmt.Errorf("invalid work_dir")
	}
	workAbs := siteBase
	if workDirRel != "." {
		workAbs = filepath.Join(siteBase, workDirRel)
	}
	workAbs = filepath.Clean(workAbs)
	if !strings.HasPrefix(workAbs+string(os.PathSeparator), siteBase+string(os.PathSeparator)) && workAbs != siteBase {
		return nil, fmt.Errorf("work_dir escapes site base")
	}

	pkgPath := filepath.Join(workAbs, "package.json")
	b, err := os.ReadFile(pkgPath)
	if err != nil {
		if os.IsNotExist(err) {
			return &DetectResult{WorkDir: workDirRel}, nil
		}
		return nil, err
	}

	var pkg pkgJSON
	if err := json.Unmarshal(b, &pkg); err != nil {
		return nil, fmt.Errorf("package.json parse: %w", err)
	}

	profile := inferProfile(workAbs, &pkg)
	script := pickStartScript(pkg.Scripts)
	scripts := scriptKeys(pkg.Scripts)

	return &DetectResult{
		HasPackageJSON: true,
		Profile:        profile,
		WorkDir:        workDirRel,
		StartScript:    script,
		SuggestedPort:  DefaultPortForProfile(profile),
		Scripts:        scripts,
		PackageName:    strings.TrimSpace(pkg.Name),
	}, nil
}

func scriptKeys(m map[string]string) []string {
	if len(m) == 0 {
		return nil
	}
	keys := make([]string, 0, len(m))
	for k := range m {
		keys = append(keys, k)
	}
	// stable order for UI
	for i := 0; i < len(keys); i++ {
		for j := i + 1; j < len(keys); j++ {
			if keys[j] < keys[i] {
				keys[i], keys[j] = keys[j], keys[i]
			}
		}
	}
	return keys
}

func pickStartScript(scripts map[string]string) string {
	if len(scripts) == 0 {
		return "start"
	}
	for _, pref := range []string{"start", "start:prod", "prod", "serve", "dev"} {
		if _, ok := scripts[pref]; ok {
			return pref
		}
	}
	for k := range scripts {
		return k
	}
	return "start"
}

func inferProfile(workAbs string, pkg *pkgJSON) string {
	deps := pkg.Dependencies
	hasDep := func(name string) bool {
		_, ok := deps[name]
		return ok
	}
	if fileExists(filepath.Join(workAbs, "next.config.js")) ||
		fileExists(filepath.Join(workAbs, "next.config.mjs")) ||
		fileExists(filepath.Join(workAbs, "next.config.ts")) ||
		hasDep("next") {
		return "nextjs"
	}
	if fileExists(filepath.Join(workAbs, "nuxt.config.ts")) ||
		fileExists(filepath.Join(workAbs, "nuxt.config.js")) ||
		hasDep("nuxt") {
		return "nuxt"
	}
	if fileExists(filepath.Join(workAbs, "strapi.config.ts")) ||
		fileExists(filepath.Join(workAbs, "strapi.config.js")) ||
		hasDep("@strapi/strapi") {
		return "strapi"
	}
	if fileExists(filepath.Join(workAbs, "n8n.config.js")) || hasDep("n8n") {
		return "n8n"
	}
	return "node"
}

func fileExists(p string) bool {
	st, err := os.Stat(p)
	return err == nil && !st.IsDir()
}
