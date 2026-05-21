package security

import (
	"fmt"
	"strconv"
	"strings"
)

// ApplyFirewallRule iptables HOSTVIM-FW zincirine kural ekler (hostvim-security).
func ApplyFirewallRule(action, protocol, port, source string) error {
	action = strings.ToLower(strings.TrimSpace(action))
	protocol = strings.ToLower(strings.TrimSpace(protocol))
	port = strings.TrimSpace(port)
	source = strings.TrimSpace(source)

	if action != "allow" && action != "deny" {
		return fmt.Errorf("invalid action")
	}
	switch protocol {
	case "tcp", "udp", "icmp", "any":
	default:
		return fmt.Errorf("invalid protocol")
	}
	if port != "" && port != "any" {
		if _, err := strconv.Atoi(port); err != nil {
			return fmt.Errorf("invalid port")
		}
	}
	if source != "" && source != "any" {
		if strings.Contains(source, " ") || strings.Contains(source, "..") {
			return fmt.Errorf("invalid source")
		}
	}
	_, err := run("firewall-rule-apply", action, protocol, port, source)
	return err
}

// AdvisorItem güvenlik danışmanı önerisi.
type AdvisorItem struct {
	Key       string `json:"key"`
	Severity  string `json:"severity"`
	Title     string `json:"title"`
	Detail    string `json:"detail"`
	Tab       string `json:"tab,omitempty"`
	ActionKey string `json:"action_key,omitempty"`
	Ok        bool   `json:"ok"`
}

// BuildAdvisorReport overview verisinden danışman raporu üretir.
func BuildAdvisorReport(overview map[string]interface{}) (score int, items []AdvisorItem) {
	items = []AdvisorItem{}
	score = 100

	fail2ban := mapField(overview, "fail2ban")
	modsec := mapField(overview, "modsecurity")
	clamav := mapField(overview, "clamav")
	firewall := mapField(overview, "firewall")

	fbOn := boolField(fail2ban, "enabled")
	msOn := boolField(modsec, "enabled")
	cvOn := boolField(clamav, "enabled")
	fbInstalled := boolField(fail2ban, "installed")
	msInstalled := boolField(modsec, "installed")
	cvInstalled := boolField(clamav, "installed")

	add := func(it AdvisorItem) {
		if !it.Ok {
			switch it.Severity {
			case "critical":
				score -= 25
			case "high":
				score -= 15
			case "medium":
				score -= 8
			default:
				score -= 4
			}
		}
		items = append(items, it)
	}

	add(AdvisorItem{
		Key: "fail2ban", Severity: "high", Tab: "brute", ActionKey: "enable_fail2ban",
		Ok: fbOn,
		Title: "Fail2ban / IP engelleme",
		Detail: pick(fbOn, "SSH ve panel brute-force koruması aktif.", pick(fbInstalled, "Fail2ban kapalı — Brute Force sekmesinden etkinleştirin.", "Fail2ban kurulu değil — Brute Force sekmesinden kurun ve açın.")),
	})
	add(AdvisorItem{
		Key: "modsecurity", Severity: "high", Tab: "website", ActionKey: "enable_modsec",
		Ok: msOn,
		Title: "ModSecurity (WAF)",
		Detail: pick(msOn, "Web uygulama güvenlik duvarı aktif.", pick(msInstalled, "ModSecurity kapalı — Website sekmesinden etkinleştirin.", "ModSecurity kurulu değil — Website sekmesinden kurun.")),
	})
	add(AdvisorItem{
		Key: "clamav", Severity: "medium", Tab: "server", ActionKey: "enable_clamav",
		Ok: cvOn,
		Title: "Antivirüs (ClamAV)",
		Detail: pick(cvOn, "ClamAV servisi çalışıyor.", pick(cvInstalled, "ClamAV kapalı — Sunucu Güvenliği sekmesinden açın.", "ClamAV kurulu değil — Sunucu Güvenliği sekmesinden kurun.")),
	})

	lastScan := stringField(clamav, "last_scan")
	if cvOn && lastScan == "" {
		add(AdvisorItem{
			Key: "clamav_scan", Severity: "low", Tab: "server", ActionKey: "run_clamav_scan",
			Ok: false, Title: "ClamAV taraması",
			Detail: "Henüz tamamlanmış tarama kaydı yok. Düzenli tarama için Sunucu Güvenliği sekmesinden tarama başlatın.",
		})
	} else if cvOn {
		add(AdvisorItem{Key: "clamav_scan", Severity: "low", Tab: "server", Ok: true, Title: "ClamAV taraması", Detail: "Son tarama: " + lastScan})
	}

	fwOn := boolField(firewall, "enabled")
	rules := sliceField(firewall, "recent_rules")
	ruleCount := len(rules)
	add(AdvisorItem{
		Key: "firewall", Severity: "medium", Tab: "firewall", ActionKey: "review_firewall",
		Ok: fwOn,
		Title: "Güvenlik duvarı koruması",
		Detail: pick(fwOn, pick(ruleCount > 0, fmt.Sprintf("HOSTVIM-FW aktif (%d kayıtlı kural).", ruleCount), "Güvenlik duvarı zinciri aktif."), "Güvenlik duvarı kapalı veya yapılandırılmamış — Güvenlik Duvarı sekmesinden etkinleştirin."),
	})

	if score < 0 {
		score = 0
	}
	if score > 100 {
		score = 100
	}
	return score, items
}

func mapField(m map[string]interface{}, key string) map[string]interface{} {
	if m == nil {
		return nil
	}
	v, ok := m[key].(map[string]interface{})
	if !ok {
		return nil
	}
	return v
}

func boolField(m map[string]interface{}, key string) bool {
	if m == nil {
		return false
	}
	v, ok := m[key].(bool)
	return ok && v
}

func stringField(m map[string]interface{}, key string) string {
	if m == nil {
		return ""
	}
	switch v := m[key].(type) {
	case string:
		return strings.TrimSpace(v)
	default:
		return ""
	}
}

func sliceField(m map[string]interface{}, key string) []interface{} {
	if m == nil {
		return nil
	}
	v, ok := m[key].([]interface{})
	if ok {
		return v
	}
	return nil
}

func pick(cond bool, a, b string) string {
	if cond {
		return a
	}
	return b
}
