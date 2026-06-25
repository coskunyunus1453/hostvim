package hosting

import (
	"encoding/json"
	"os"
	"path/filepath"
	"regexp"
	"sort"
	"strconv"
	"strings"
)

var platformPHPMinRe = regexp.MustCompile(`>=\s*(\d+\.\d+(?:\.\d+)?)`)

type semver struct {
	major int
	minor int
	patch int
}

func parseSemver(v string) semver {
	v = strings.TrimSpace(v)
	v = strings.TrimPrefix(v, "v")
	parts := strings.Split(v, ".")
	out := semver{}
	if len(parts) > 0 {
		out.major, _ = strconv.Atoi(parts[0])
	}
	if len(parts) > 1 {
		out.minor, _ = strconv.Atoi(parts[1])
	}
	if len(parts) > 2 {
		out.patch, _ = strconv.Atoi(parts[2])
	}
	return out
}

func semverGTE(a, b semver) bool {
	if a.major != b.major {
		return a.major > b.major
	}
	if a.minor != b.minor {
		return a.minor > b.minor
	}
	return a.patch >= b.patch
}

func semverGT(a, b semver) bool {
	if a.major != b.major {
		return a.major > b.major
	}
	if a.minor != b.minor {
		return a.minor > b.minor
	}
	return a.patch > b.patch
}

// InstalledPHPVersions sunucuda mevcut php-fpm soket sürümlerini döndürür (örn. 8.4, 8.3).
func InstalledPHPVersions() []string {
	matches, err := filepath.Glob("/run/php/php*-fpm.sock")
	if err != nil {
		return nil
	}
	seen := map[string]bool{}
	var out []string
	for _, p := range matches {
		base := filepath.Base(p)
		base = strings.TrimSuffix(base, "-fpm.sock")
		base = strings.TrimPrefix(base, "php")
		if base == "" || seen[base] {
			continue
		}
		seen[base] = true
		out = append(out, base)
	}
	sort.Slice(out, func(i, j int) bool {
		return semverGT(parseSemver(out[i]), parseSemver(out[j]))
	})
	return out
}

// PickInstalledPHPVersion min sürümü karşılayan en yüksek kurulu PHP sürümünü seçer.
func PickInstalledPHPVersion(min string) string {
	min = strings.TrimSpace(min)
	if min == "" {
		return ""
	}
	minV := parseSemver(min)
	best := ""
	bestV := semver{}
	for _, v := range InstalledPHPVersions() {
		vv := parseSemver(v)
		if semverGTE(vv, minV) && (best == "" || semverGT(vv, bestV)) {
			best = v
			bestV = vv
		}
	}
	if best != "" {
		return best
	}
	return min
}

func readMinPHPFromComposerJSON(siteBase string) string {
	b, err := os.ReadFile(filepath.Join(siteBase, "composer.json"))
	if err != nil {
		return ""
	}
	var doc struct {
		Require map[string]string `json:"require"`
	}
	if json.Unmarshal(b, &doc) != nil {
		return ""
	}
	constraint := strings.TrimSpace(doc.Require["php"])
	if constraint == "" {
		return ""
	}
	return phpConstraintMinVersion(constraint)
}

func phpConstraintMinVersion(constraint string) string {
	constraint = strings.TrimSpace(constraint)
	if constraint == "" {
		return ""
	}
	constraint = strings.TrimPrefix(constraint, "^")
	constraint = strings.TrimPrefix(constraint, "~")
	constraint = strings.TrimPrefix(constraint, ">=")
	constraint = strings.TrimPrefix(constraint, ">")
	constraint = strings.TrimSpace(constraint)
	if constraint == "" {
		return ""
	}
	if !strings.Contains(constraint, ".") {
		return constraint + ".0"
	}
	parts := strings.Split(constraint, ".")
	if len(parts) == 2 {
		return constraint + ".0"
	}
	return constraint
}

func readMinPHPFromPlatformCheck(siteBase string) string {
	b, err := os.ReadFile(filepath.Join(siteBase, "vendor", "composer", "platform_check.php"))
	if err != nil {
		return ""
	}
	m := platformPHPMinRe.FindSubmatch(b)
	if len(m) < 2 {
		return ""
	}
	return string(m[1])
}

// DetectPHPVersionFromSite composer/platform_check üzerinden gerekli PHP sürümünü bulur.
func DetectPHPVersionFromSite(siteBase string) string {
	siteBase = filepath.Clean(siteBase)
	min := readMinPHPFromPlatformCheck(siteBase)
	if min == "" {
		min = readMinPHPFromComposerJSON(siteBase)
	}
	if min == "" {
		return ""
	}
	return PickInstalledPHPVersion(min)
}
