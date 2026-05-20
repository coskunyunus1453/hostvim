package hosting

import "strconv"

// pathReq — profil için beklenen dosya/klasör.
type pathReq struct {
	Path     string
	Severity string
}

var profileRequiredPaths = map[string][]pathReq{
	"wordpress": {
		{Path: "index.php", Severity: "critical"},
		{Path: "wp-config.php", Severity: "critical"},
		{Path: "wp-content", Severity: "critical"},
		{Path: "wp-includes", Severity: "critical"},
		{Path: "wp-admin", Severity: "warning"},
	},
	"laravel": {
		{Path: "artisan", Severity: "critical"},
		{Path: "app", Severity: "critical"},
		{Path: "bootstrap", Severity: "warning"},
		{Path: "public/index.php", Severity: "critical"},
		{Path: "vendor", Severity: "warning"},
	},
	"symfony": {
		{Path: "bin", Severity: "warning"},
		{Path: "config", Severity: "critical"},
		{Path: "public/index.php", Severity: "critical"},
		{Path: "vendor", Severity: "warning"},
	},
	"codeigniter4": {
		{Path: "app", Severity: "critical"},
		{Path: "writable", Severity: "warning"},
		{Path: "public/index.php", Severity: "critical"},
		{Path: "spark", Severity: "warning"},
	},
	"codeigniter3": {
		{Path: "index.php", Severity: "critical"},
		{Path: "application", Severity: "critical"},
		{Path: "system", Severity: "critical"},
	},
	"opencart": {
		{Path: "index.php", Severity: "critical"},
		{Path: "config.php", Severity: "critical"},
		{Path: "admin", Severity: "critical"},
		{Path: "catalog", Severity: "critical"},
		{Path: "system", Severity: "critical"},
		{Path: "image", Severity: "warning"},
	},
	"prestashop": {
		{Path: "index.php", Severity: "critical"},
		{Path: "config/settings.inc.php", Severity: "critical"},
		{Path: "classes", Severity: "critical"},
		{Path: "themes", Severity: "warning"},
		{Path: "modules", Severity: "warning"},
	},
	"joomla": {
		{Path: "index.php", Severity: "critical"},
		{Path: "configuration.php", Severity: "critical"},
		{Path: "administrator", Severity: "critical"},
		{Path: "components", Severity: "warning"},
	},
	"drupal": {
		{Path: "index.php", Severity: "critical"},
		{Path: "core", Severity: "critical"},
		{Path: "sites", Severity: "critical"},
		{Path: "modules", Severity: "warning"},
	},
	"magento": {
		{Path: "index.php", Severity: "critical"},
		{Path: "app", Severity: "critical"},
		{Path: "bin/magento", Severity: "warning"},
		{Path: "vendor", Severity: "warning"},
		{Path: "pub", Severity: "warning"},
	},
	"moodle": {
		{Path: "config.php", Severity: "critical"},
		{Path: "version.php", Severity: "warning"},
		{Path: "lib", Severity: "critical"},
	},
	"mediawiki": {
		{Path: "index.php", Severity: "critical"},
		{Path: "LocalSettings.php", Severity: "critical"},
		{Path: "includes", Severity: "critical"},
	},
}

func appendIntegrityIssues(profile string, fs *stackFS, issues *[]StackIssue) {
	if profile == "woocommerce" {
		profile = "wordpress"
	}
	reqs, ok := profileRequiredPaths[profile]
	if !ok {
		return
	}
	missing := 0
	for _, req := range reqs {
		if fs.exists(req.Path) {
			continue
		}
		missing++
		*issues = append(*issues, StackIssue{
			Code:     "missing_path",
			Severity: req.Severity,
			Params:   map[string]string{"path": req.Path, "profile": profile},
			Fixable:  false,
		})
	}
	if missing >= 2 {
		*issues = append(*issues, StackIssue{
			Code:     "incomplete_install",
			Severity: "critical",
			Params:   map[string]string{"profile": profile, "count": strconv.Itoa(missing)},
			Fixable:  false,
		})
	}
}
