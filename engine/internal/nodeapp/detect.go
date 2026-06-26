package nodeapp

import (
	"encoding/json"
	"fmt"
	"io/fs"
	"os"
	"path/filepath"
	"sort"
	"strings"
)

// WorkDirCandidate panelde seçilebilir çalışma dizini (public_html göreli).
type WorkDirCandidate struct {
	Rel         string `json:"rel"`
	Label       string `json:"label"`
	PackageName string `json:"package_name,omitempty"`
	HasStart    bool   `json:"has_start"`
}

// DetectResult package.json ve dizin sinyallerinden önerilen Node yapılandırması.
type DetectResult struct {
	HasPackageJSON    bool               `json:"has_package_json"`
	Profile           string             `json:"profile"`
	WorkDir           string             `json:"work_dir"`
	StartScript       string             `json:"start_script"`
	SuggestedPort     int                `json:"suggested_port"`
	Scripts           []string           `json:"scripts"`
	PackageName       string             `json:"package_name,omitempty"`
	WorkDirCandidates []WorkDirCandidate `json:"work_dir_candidates,omitempty"`
}

type pkgJSON struct {
	Name         string            `json:"name"`
	Scripts      map[string]string `json:"scripts"`
	Dependencies map[string]string `json:"dependencies"`
}

func workDirLabel(rel string) string {
	if rel == "" || rel == "." {
		return "public_html (site kökü)"
	}
	return "public_html/" + filepath.ToSlash(rel)
}

// ScanWorkDirCandidates public_html altında package.json bulunan dizinleri listeler.
func ScanWorkDirCandidates(siteBase string, maxDepth int) []WorkDirCandidate {
	siteBase = filepath.Clean(siteBase)
	if maxDepth <= 0 {
		maxDepth = 5
	}
	seen := map[string]bool{}
	var out []WorkDirCandidate

	add := func(rel string) {
		rel = filepath.ToSlash(strings.TrimSpace(rel))
		if rel == "" {
			rel = "."
		}
		if seen[rel] {
			return
		}
		seen[rel] = true
		abs := siteBase
		if rel != "." {
			abs = filepath.Join(siteBase, rel)
		}
		c := WorkDirCandidate{Rel: rel, Label: workDirLabel(rel)}
		if b, err := os.ReadFile(filepath.Join(abs, "package.json")); err == nil {
			var pkg pkgJSON
			if json.Unmarshal(b, &pkg) == nil {
				c.PackageName = strings.TrimSpace(pkg.Name)
				c.HasStart = scriptMapHasStart(pkg.Scripts)
			}
		}
		out = append(out, c)
	}

	add(".")

	_ = filepath.WalkDir(siteBase, func(path string, d fs.DirEntry, err error) error {
		if err != nil {
			return nil
		}
		if path == siteBase {
			return nil
		}
		base := filepath.Base(path)
		if d.IsDir() && (base == "node_modules" || base == ".git" || base == "vendor" || strings.HasPrefix(base, ".")) {
			return fs.SkipDir
		}
		rel, rerr := filepath.Rel(siteBase, path)
		if rerr != nil {
			return nil
		}
		rel = filepath.ToSlash(rel)
		depth := strings.Count(rel, "/") + 1
		if depth > maxDepth {
			if d.IsDir() {
				return fs.SkipDir
			}
			return nil
		}
		if d.IsDir() && fileExists(filepath.Join(path, "package.json")) {
			add(rel)
		}
		return nil
	})

	sort.Slice(out, func(i, j int) bool {
		if out[i].Rel == "." {
			return true
		}
		if out[j].Rel == "." {
			return false
		}
		if out[i].HasStart != out[j].HasStart {
			return out[i].HasStart
		}
		return len(out[i].Rel) < len(out[j].Rel)
	})
	return out
}

func scriptMapHasStart(scripts map[string]string) bool {
	if len(scripts) == 0 {
		return false
	}
	for _, pref := range []string{"start", "start:prod", "prod", "serve"} {
		if _, ok := scripts[pref]; ok {
			return true
		}
	}
	return false
}

func scoreDetectResult(rel string, det *DetectResult) int {
	if det == nil || !det.HasPackageJSON {
		return -1
	}
	sc := 0
	if det.StartScript != "" {
		sc += 50
	}
	if rel == "." {
		sc += 20
	}
	depth := len(strings.Split(rel, "/"))
	if depth < 15 {
		sc += 15 - depth
	}
	switch det.Profile {
	case "nextjs", "nuxt", "strapi", "n8n":
		sc += 10
	}
	return sc
}

// DetectBest en uygun package.json konumunu seçer (otomatik yapılandırma için).
func DetectBest(siteBase string) (*DetectResult, error) {
	candidates := ScanWorkDirCandidates(siteBase, 5)
	var best *DetectResult
	bestScore := -1
	for _, c := range candidates {
		det, err := Detect(siteBase, c.Rel)
		if err != nil {
			continue
		}
		det.WorkDirCandidates = candidates
		sc := scoreDetectResult(c.Rel, det)
		if sc > bestScore {
			bestScore = sc
			best = det
		}
	}
	if best != nil {
		return best, nil
	}
	det, err := Detect(siteBase, ".")
	if det != nil {
		det.WorkDirCandidates = candidates
	}
	return det, err
}

// Detect siteBase (public_html) altında package.json arar ve profil önerir.
func Detect(siteBase string, workDirRel string) (*DetectResult, error) {
	siteBase = filepath.Clean(siteBase)
	candidates := ScanWorkDirCandidates(siteBase, 5)
	workDirRel = strings.TrimSpace(workDirRel)
	if workDirRel == "" || workDirRel == "." {
		workDirRel = "."
	}
	if strings.Contains(workDirRel, "..") || filepath.IsAbs(workDirRel) {
		return nil, fmt.Errorf("invalid work_dir")
	}

	det, err := detectAtWorkDir(siteBase, workDirRel)
	if err != nil {
		return nil, err
	}
	det.WorkDirCandidates = candidates

	if !det.HasPackageJSON && workDirRel == "." {
		for _, c := range candidates {
			if c.Rel == "." {
				continue
			}
			alt, aerr := detectAtWorkDir(siteBase, c.Rel)
			if aerr != nil {
				continue
			}
			if alt.HasPackageJSON {
				alt.WorkDirCandidates = candidates
				return alt, nil
			}
		}
	}
	return det, nil
}

func detectAtWorkDir(siteBase, workDirRel string) (*DetectResult, error) {
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
	suggestedPort := inferPortFromWorkDir(siteBase, workDirRel, profile)

	return &DetectResult{
		HasPackageJSON: true,
		Profile:        profile,
		WorkDir:        workDirRel,
		StartScript:    script,
		SuggestedPort:  suggestedPort,
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
	for _, pref := range []string{"start", "start:prod", "prod", "serve"} {
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
