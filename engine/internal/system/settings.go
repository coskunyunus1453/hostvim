package system

import (
	"fmt"
	"os"
	"os/exec"
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
	if hostname != nil {
		h := strings.TrimSpace(*hostname)
		if h == "" || !hostnameRe.MatchString(h) {
			return fmt.Errorf("invalid hostname")
		}
		if err := setHostname(h); err != nil {
			return err
		}
	}
	if timezone != nil {
		tz := strings.TrimSpace(*timezone)
		if tz == "" || strings.Contains(tz, "..") || strings.ContainsAny(tz, " \t\n") {
			return fmt.Errorf("invalid timezone")
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
	if _, err := exec.LookPath("hostnamectl"); err == nil {
		out, err := exec.Command("hostnamectl", "set-hostname", name).CombinedOutput()
		if err == nil {
			return nil
		}
		out2, err2 := exec.Command("sudo", "-n", "hostnamectl", "set-hostname", name).CombinedOutput()
		if err2 != nil {
			return fmt.Errorf("%s — %s", strings.TrimSpace(string(out)), strings.TrimSpace(string(out2)))
		}
		return nil
	}
	return fmt.Errorf("hostnamectl not found")
}

func setTimezone(tz string) error {
	if runtime.GOOS != "linux" {
		return fmt.Errorf("timezone change supported only on Linux")
	}
	if _, err := exec.LookPath("timedatectl"); err == nil {
		out, err := exec.Command("timedatectl", "set-timezone", tz).CombinedOutput()
		if err == nil {
			return nil
		}
		out2, err2 := exec.Command("sudo", "-n", "timedatectl", "set-timezone", tz).CombinedOutput()
		if err2 != nil {
			return fmt.Errorf("%s — %s", strings.TrimSpace(string(out)), strings.TrimSpace(string(out2)))
		}
		return nil
	}
	return fmt.Errorf("timedatectl not found")
}
