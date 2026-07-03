package sitecage

import (
	"os/exec"
	"strconv"
	"strings"
)

// sliceSlugFromDomain, panelze-site-cage betiğindeki slug_from_domain ile birebir aynı
// kuralı uygular: küçük harf, '.' → '-', yalnızca [a-z0-9-], en fazla 48 karakter.
func sliceSlugFromDomain(domain string) string {
	domain = strings.ToLower(strings.TrimSpace(domain))
	slug := strings.Map(func(r rune) rune {
		if (r >= 'a' && r <= 'z') || (r >= '0' && r <= '9') || r == '-' {
			return r
		}
		if r == '.' {
			return '-'
		}
		return -1
	}, domain)
	if len(slug) > 48 {
		slug = slug[:48]
	}
	return slug
}

// SliceUnitForDomain sitenin PanelKafes kaynak slice birim adı (panelkafes-<slug>.slice).
func SliceUnitForDomain(domain string) string {
	return "panelkafes-" + sliceSlugFromDomain(domain) + ".slice"
}

// MemoryUsageBytes sitenin cgroup slice'ının anlık bellek kullanımı (bayt).
// systemctl show MemoryCurrent değerini okur (fpm + node dahil tüm alt süreçler).
// slice yoksa/çalışmıyorsa (0, false) döner. Root gerektirmez.
func MemoryUsageBytes(domain string) (int64, bool) {
	unit := SliceUnitForDomain(domain)
	out, err := exec.Command("systemctl", "show", "-p", "MemoryCurrent", "--value", unit).Output()
	if err != nil {
		return 0, false
	}
	v := strings.TrimSpace(string(out))
	if v == "" || v == "[not set]" || v == "infinity" {
		return 0, false
	}
	n, err := strconv.ParseInt(v, 10, 64)
	if err != nil || n < 0 {
		return 0, false
	}
	return n, true
}
