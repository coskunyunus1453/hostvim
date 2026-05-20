package system

import (
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"regexp"
	"runtime"
	"strings"
	"time"
)

// ServerSettings sunucu genel ayarları.
type ServerSettings struct {
	Hostname        string   `json:"hostname"`
	Timezone        string   `json:"timezone"`
	LocalTime       string   `json:"local_time"`
	NTPSynchronized bool     `json:"ntp_synchronized"`
	PrimaryIP       string   `json:"primary_ip"`
	OS              string   `json:"os,omitempty"`
	Timezones       []string `json:"timezones,omitempty"`
}

var hostnameRe = regexp.MustCompile(`^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$`)

var commonTimezones = []string{
	"UTC",
	"Europe/Istanbul",
	"Europe/London",
	"Europe/Berlin",
	"Europe/Paris",
	"America/New_York",
	"America/Chicago",
	"America/Los_Angeles",
	"Asia/Dubai",
	"Asia/Singapore",
	"Asia/Tokyo",
}

const hostvimSystemSettingsBin = "/usr/local/sbin/hostvim-system-settings"

// GetServerSettings hostname, saat dilimi ve NTP durumunu okur.
func GetServerSettings(dataDir string) ServerSettings {
	out := ServerSettings{
		LocalTime: time.Now().Format(time.RFC3339),
		PrimaryIP: PrimaryIP(),
		Timezones: commonTimezones,
	}
	if h, err := os.Hostname(); err == nil {
		out.Hostname = h
	}
	if runtime.GOOS == "linux" {
		if tz, ntp, lt, err := readTimedatectl(); err == nil {
			if tz != "" {
				out.Timezone = tz
			}
			out.NTPSynchronized = ntp
			if lt != "" {
				out.LocalTime = lt
			}
		} else if b, err := os.ReadFile("/etc/timezone"); err == nil {
			out.Timezone = strings.TrimSpace(string(b))
		}
		if out.Timezone == "" {
			if link, err := os.Readlink("/etc/localtime"); err == nil {
				if i := strings.Index(link, "zoneinfo/"); i >= 0 {
					out.Timezone = strings.TrimSpace(link[i+len("zoneinfo/"):])
				}
			}
		}
		if tzs, err := listTimezones(); err == nil && len(tzs) > 0 {
			out.Timezones = tzs
		}
	}
	_ = ApplyManagedAliases(dataDir)
	return out
}

func readTimedatectl() (timezone string, ntp bool, localTime string, err error) {
	if _, err := exec.LookPath("timedatectl"); err != nil {
		return "", false, "", err
	}
	out, err := exec.Command("timedatectl", "show", "-p", "Timezone", "-p", "NTPSynchronized", "-p", "TimeUSec").CombinedOutput()
	if err != nil {
		return "", false, "", err
	}
	for _, line := range strings.Split(string(out), "\n") {
		line = strings.TrimSpace(line)
		if strings.HasPrefix(line, "Timezone=") {
			timezone = strings.TrimPrefix(line, "Timezone=")
		}
		if strings.HasPrefix(line, "NTPSynchronized=") {
			ntp = strings.TrimSuffix(strings.TrimPrefix(line, "NTPSynchronized="), "\n") == "yes"
		}
		if strings.HasPrefix(line, "TimeUSec=") {
			raw := strings.TrimPrefix(line, "TimeUSec=")
			if t, parseErr := time.Parse("2006-01-02 15:04:05.999999999 MST", raw); parseErr == nil {
				localTime = t.Format(time.RFC3339)
			}
		}
	}
	return timezone, ntp, localTime, nil
}

func listTimezones() ([]string, error) {
	if _, err := exec.LookPath("timedatectl"); err != nil {
		return commonTimezones, nil
	}
	out, err := exec.Command("timedatectl", "list-timezones").CombinedOutput()
	if err != nil {
		return commonTimezones, err
	}
	var rows []string
	for _, line := range strings.Split(string(out), "\n") {
		line = strings.TrimSpace(line)
		if line != "" {
			rows = append(rows, line)
		}
	}
	if len(rows) == 0 {
		return commonTimezones, nil
	}
	return rows, nil
}

// UpdateServerSettings hostname ve/veya timezone günceller.
func UpdateServerSettings(hostname, timezone *string) error {
	current := GetServerSettings("")
	if hostname != nil {
		h := strings.TrimSpace(*hostname)
		if h == "" || !hostnameRe.MatchString(h) {
			return fmt.Errorf("invalid hostname")
		}
		cur := strings.TrimSpace(current.Hostname)
		if cur != "" && strings.EqualFold(cur, h) {
			hostname = nil
		} else if err := setHostname(h); err != nil {
			return err
		}
	}
	if timezone != nil {
		tz := strings.TrimSpace(*timezone)
		if tz == "" || strings.Contains(tz, "..") || strings.ContainsAny(tz, " \t\n") {
			return fmt.Errorf("invalid timezone")
		}
		if strings.EqualFold(strings.TrimSpace(current.Timezone), tz) {
			return nil
		}
		if err := setTimezone(tz); err != nil {
			return err
		}
	}
	return nil
}

func setHostname(name string) error {
	if runtime.GOOS != "linux" {
		return fmt.Errorf("hostname change supported only on Linux")
	}
	out, err := runPrivileged(hostvimSystemSettingsBin, "set-hostname", name)
	if err == nil {
		return nil
	}
	if _, look := exec.LookPath("hostnamectl"); look == nil {
		out2, err2 := runPrivileged("hostnamectl", "set-hostname", name)
		if err2 != nil {
			return combineCmdErrors(out, out2)
		}
		return nil
	}
	return fmt.Errorf("%s", formatCmdError(out, err))
}

func setTimezone(tz string) error {
	if runtime.GOOS != "linux" {
		return fmt.Errorf("timezone change supported only on Linux")
	}
	if err := validateZoneinfo(tz); err != nil {
		return err
	}
	out, err := runPrivileged(hostvimSystemSettingsBin, "set-timezone", tz)
	if err == nil {
		return nil
	}
	if _, look := exec.LookPath("timedatectl"); look == nil {
		out2, err2 := runPrivileged("timedatectl", "set-timezone", tz)
		if err2 != nil {
			return combineCmdErrors(out, out2)
		}
		return nil
	}
	return fmt.Errorf("%s", formatCmdError(out, err))
}

func validateZoneinfo(tz string) error {
	zonePath := filepath.Join("/usr/share/zoneinfo", tz)
	if st, err := os.Stat(zonePath); err != nil || st.IsDir() {
		return fmt.Errorf("invalid timezone")
	}
	return nil
}

// runPrivileged root veya sudo -n ile komut çalıştırır (engine www-data).
func runPrivileged(bin string, args ...string) ([]byte, error) {
	if os.Geteuid() == 0 {
		return exec.Command(bin, args...).CombinedOutput()
	}
	if _, err := exec.LookPath("sudo"); err != nil {
		return nil, fmt.Errorf("sudo not found")
	}
	return exec.Command("sudo", append([]string{"-n", bin}, args...)...).CombinedOutput()
}

func formatCmdError(out []byte, err error) string {
	msg := strings.TrimSpace(string(out))
	if msg == "" && err != nil {
		msg = err.Error()
	}
	if strings.Contains(msg, "password is required") {
		return msg + " — panel kurulumunda /etc/sudoers.d/hostvim-engine içinde hostvim-system-settings NOPASSWD tanımlı olmalı; sudo bash deploy/bootstrap/install-production.sh veya sudo hostvim-post-install çalıştırın"
	}
	return msg
}

func combineCmdErrors(a []byte, b []byte) error {
	sa := strings.TrimSpace(string(a))
	sb := strings.TrimSpace(string(b))
	if sa != "" && sb != "" {
		return fmt.Errorf("%s — %s", sa, sb)
	}
	if sa != "" {
		return fmt.Errorf("%s", sa)
	}
	return fmt.Errorf("%s", sb)
}
