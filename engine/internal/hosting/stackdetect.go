package hosting

import (
	"os"
	"path/filepath"
	"strings"
)

// StackIssue — tespit kodu; metin panel/frontend i18n ile üretilir.
type StackIssue struct {
	Code     string            `json:"code"`
	Severity string            `json:"severity"` // critical, warning, info
	Fixable  bool              `json:"fixable"`
	FixID    string            `json:"fix_id,omitempty"`
	Params   map[string]string `json:"params,omitempty"`
}

// StackScanResult — site dosya yapısı + önerilen web yığını.
type StackScanResult struct {
	Profile             string       `json:"profile"`
	Runtime             string       `json:"runtime"`
	Confidence          string       `json:"confidence"`
	Signals             []string     `json:"signals"`
	RecommendedVariant  string       `json:"recommended_variant"`
	RecommendedDocRoot  string       `json:"recommended_doc_root"`
	CurrentDocRoot      string       `json:"current_doc_root"`
	DocrootAligned      bool         `json:"docroot_aligned"`
	CurrentServerType   string       `json:"current_server_type"`
	IndexPath           string       `json:"index_path,omitempty"`
	GuidanceKey         string       `json:"guidance_key,omitempty"`
	Issues              []StackIssue `json:"issues"`
	SuggestedPHPVersion string       `json:"suggested_php_version,omitempty"`
	HasPackageJSON      bool         `json:"has_package_json"`
	HasUserIni          bool         `json:"has_user_ini"`
}

type stackFS struct {
	base string
}

func (s *stackFS) exists(rel string) bool {
	if rel == "" || rel == "." {
		_, err := os.Stat(s.base)
		return err == nil
	}
	_, err := os.Stat(filepath.Join(s.base, filepath.FromSlash(rel)))
	return err == nil
}

func (s *stackFS) isDir(rel string) bool {
	fi, err := os.Stat(filepath.Join(s.base, filepath.FromSlash(rel)))
	return err == nil && fi.IsDir()
}

func (s *stackFS) readUserIni() string {
	b, err := os.ReadFile(filepath.Join(s.base, ".user.ini"))
	if err != nil {
		return ""
	}
	return string(b)
}

// ScanSiteStack public_html tabanını tarar.
func ScanSiteStack(siteBase, metaDocRoot, serverType string) (*StackScanResult, error) {
	siteBase = filepath.Clean(siteBase)
	if siteBase == "" {
		return nil, os.ErrInvalid
	}
	if st, err := os.Stat(siteBase); err != nil || !st.IsDir() {
		return nil, err
	}

	fs := &stackFS{base: siteBase}
	signals := []string{}
	issues := []StackIssue{}

	add := func(sig string) { signals = append(signals, sig) }

	has := func(p string) bool {
		ok := fs.exists(p)
		if ok {
			add(p)
		}
		return ok
	}

	publicIndex := has("public/index.php")
	rootIndexPHP := has("index.php")
	rootIndexHTML := has("index.html")
	hasArtisan := has("artisan")
	hasComposer := has("composer.json")
	hasPkg := has("package.json")
	hasWp := has("wp-config.php") || fs.isDir("wp-content")
	hasWoo := hasWp && fs.isDir("wp-content/plugins/woocommerce")
	hasCI3 := fs.isDir("application") && fs.isDir("system") && rootIndexPHP && !publicIndex
	hasCI4 := fs.isDir("app") && fs.isDir("writable") && publicIndex && has("spark")
	hasSymfony := fs.isDir("bin") && hasComposer && fs.isDir("config") && fs.isDir("src") && publicIndex && !hasArtisan
	hasDrupal := fs.isDir("core") && fs.isDir("sites")
	hasJoomla := has("configuration.php") && fs.isDir("administrator")
	hasOpenCart := has("config.php") && fs.isDir("catalog") && fs.isDir("admin") && fs.isDir("system")
	hasPrestaShop := has("config/settings.inc.php") && fs.isDir("classes") && fs.isDir("themes")
	hasMagento := fs.isDir("app") && fs.isDir("vendor") && hasComposer && !hasArtisan && !hasCI4 && fs.isDir("pub")
	hasMoodle := has("config.php") && fs.isDir("lib") && has("version.php")
	hasMediaWiki := has("LocalSettings.php") && fs.isDir("includes")
	hasNext := hasPkg && (has(".next") || has("next.config.js") || has("next.config.mjs") || has("next.config.ts"))
	hasOutStatic := has("out/index.html")
	hasNuxt := hasPkg && (has("nuxt.config.js") || has("nuxt.config.ts"))
	hasStrapi := hasPkg && (has("strapi.config.js") || has("strapi.config.ts")) && fs.isDir("src")
	hasN8n := hasPkg && (has("n8n.config.js") || has("n8n.json"))
	hasHtaccess := has(".htaccess")

	profile := "standard"
	runtime := "php"
	variant := "root"
	confidence := "low"
	indexPath := ""

	switch {
	case hasArtisan && publicIndex:
		profile, variant, confidence = "laravel", "public", "high"
		indexPath = "public/index.php"
	case hasSymfony:
		profile, variant, confidence = "symfony", "public", "high"
		indexPath = "public/index.php"
	case hasCI4:
		profile, variant, confidence = "codeigniter4", "public", "high"
		indexPath = "public/index.php"
	case hasCI3:
		profile, variant, confidence = "codeigniter3", "root", "high"
		indexPath = "index.php"
	case hasWp:
		profile, variant, confidence = "wordpress", "root", "high"
		indexPath = "index.php"
		if hasWoo {
			profile = "woocommerce"
		}
	case hasPrestaShop:
		profile, variant, confidence = "prestashop", "root", "high"
		indexPath = "index.php"
	case hasOpenCart:
		profile, variant, confidence = "opencart", "root", "high"
		indexPath = "index.php"
	case hasDrupal:
		profile, variant, confidence = "drupal", "root", "high"
		indexPath = "index.php"
	case hasJoomla:
		profile, variant, confidence = "joomla", "root", "high"
		indexPath = "index.php"
	case hasMagento:
		profile, variant, confidence = "magento", "root", "medium"
		indexPath = "index.php"
	case hasMoodle:
		profile, variant, confidence = "moodle", "root", "high"
		indexPath = "index.php"
	case hasMediaWiki:
		profile, variant, confidence = "mediawiki", "root", "high"
		indexPath = "index.php"
	case hasNext && hasOutStatic:
		profile, runtime, variant, confidence = "nextjs", "static", "root", "high"
		indexPath = "out/index.html"
	case hasNext:
		profile, runtime, variant, confidence = "nextjs", "node", "root", "high"
	case hasNuxt:
		profile, runtime, variant, confidence = "nuxt", "node", "root", "high"
	case hasStrapi:
		profile, runtime, variant, confidence = "strapi", "node", "root", "high"
	case hasN8n:
		profile, runtime, variant, confidence = "n8n", "node", "root", "high"
	case hasPkg:
		profile, runtime, variant, confidence = "node", "node", "root", "medium"
	case rootIndexPHP && !publicIndex:
		profile, variant, confidence = "php", "root", "medium"
		indexPath = "index.php"
	case publicIndex:
		profile, variant, confidence = "php", "public", "medium"
		indexPath = "public/index.php"
	case rootIndexHTML && !rootIndexPHP:
		profile, runtime, variant, confidence = "static", "static", "root", "medium"
		indexPath = "index.html"
	case hasHtaccess:
		profile, variant, confidence = "htaccess", "root", "low"
	default:
		profile, variant, confidence = "standard", "root", "low"
	}

	recommended := siteBase
	if variant == "public" {
		recommended = filepath.Join(siteBase, "public")
	}
	if runtime == "static" && hasOutStatic {
		recommended = filepath.Join(siteBase, "out")
	}

	current := strings.TrimSpace(metaDocRoot)
	if current == "" {
		current = siteBase
	}
	st := strings.ToLower(strings.TrimSpace(serverType))
	if st != "apache" && st != "openlitespeed" && st != "nginx" {
		st = "nginx"
	}
	serverType = st

	appendIntegrityIssues(profile, fs, &issues)

	if profile == "laravel" || profile == "symfony" || profile == "codeigniter4" {
		if fs.exists("public") && !fs.exists("public/storage") && fs.exists("storage/app/public") {
			issues = append(issues, StackIssue{
				Code: "storage_symlink_missing", Severity: "warning",
				Fixable: true, FixID: "storage_symlink",
			})
		}
	}

	if indexPath != "" && !fs.exists(indexPath) {
		issues = append(issues, StackIssue{
			Code: "missing_index", Severity: "critical",
			Params: map[string]string{"path": indexPath},
			Fixable: false,
		})
	}

	curClean := filepath.Clean(current)
	recClean := filepath.Clean(recommended)
	docrootAligned := curClean == recClean
	if !docrootAligned {
		issues = append(issues, StackIssue{
			Code: "docroot_mismatch", Severity: "critical",
			Params: map[string]string{"recommended": recClean, "current": curClean, "profile": profile},
			Fixable: true, FixID: "apply_docroot",
		})
	}

	if DocrootIsPublicVariant(curClean, siteBase) && EnvHasPublicURLSuffix(siteBase) {
		issues = append(issues, StackIssue{
			Code: "app_url_public_suffix", Severity: "warning",
			Fixable: true, FixID: "normalize_app_url",
		})
	}

	ini := fs.readUserIni()
	hasUserIni := ini != ""
	if hasUserIni && (strings.Contains(ini, "/www/wwwroot/") || strings.Contains(ini, "wwwroot")) {
		issues = append(issues, StackIssue{
			Code: "stale_user_ini", Severity: "warning",
			Fixable: true, FixID: "remove_user_ini",
		})
	}

	if runtime == "node" {
		issues = append(issues, StackIssue{
			Code: "node_reverse_proxy", Severity: "info",
			Fixable: true, FixID: "configure_node",
		})
	}

	if serverType == "nginx" && runtime != "node" && runtime != "static" {
		issues = append(issues, StackIssue{
			Code: "nginx_perf_optional", Severity: "info",
			Fixable: true, FixID: "nginx_perf_standard",
		})
	}

	if serverType == "apache" && hasHtaccess {
		issues = append(issues, StackIssue{
			Code: "apache_htaccess_ok", Severity: "info", Fixable: false,
		})
	}

	if serverType == "openlitespeed" {
		issues = append(issues, StackIssue{
			Code: "ols_active", Severity: "info", Fixable: false,
		})
	}

	suggestedPHP := ""
	if hasComposer || profile == "wordpress" || profile == "laravel" || profile == "magento" {
		suggestedPHP = "8.2"
	}
	if profile == "prestashop" || profile == "opencart" {
		suggestedPHP = "8.1"
	}

	guidanceKey := "stack_guidance." + profile

	return &StackScanResult{
		Profile:             profile,
		Runtime:             runtime,
		Confidence:          confidence,
		Signals:             signals,
		RecommendedVariant:  variant,
		RecommendedDocRoot:  recClean,
		CurrentDocRoot:      curClean,
		DocrootAligned:      docrootAligned,
		CurrentServerType:   serverType,
		IndexPath:           indexPath,
		GuidanceKey:         guidanceKey,
		Issues:              issues,
		SuggestedPHPVersion: suggestedPHP,
		HasPackageJSON:      hasPkg,
		HasUserIni:          hasUserIni,
	}, nil
}

// DocrootVariantForDetectedProfile algılanan profile için varyant.
func DocrootVariantForDetectedProfile(profile string) string {
	switch NormalizeAppProfile(profile) {
	case "laravel", "symfony", "codeigniter4":
		return "public"
	default:
		return DocrootVariantForProfile(profile)
	}
}
