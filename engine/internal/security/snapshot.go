package security

import (
	"strings"
	"sync"
)

// ModuleState tek bir güvenlik bileşeninin kurulum ve çalışma durumu.
type ModuleState struct {
	Enabled   bool
	Installed bool
	Error     string
}

// StatusSnapshot paralel toplanmış güvenlik durumu.
type StatusSnapshot struct {
	Fail2ban    ModuleState
	Modsecurity ModuleState
	Clamav      ModuleState
	Firewall    ModuleState
}

// CollectStatusSnapshot fail2ban, modsec, clamav ve firewall durumunu paralel okur.
func CollectStatusSnapshot() StatusSnapshot {
	var (
		fail2banOn, modsecOn, clamavOn bool
		fail2banErr, modsecErr, clamavErr, fwErr error
		fwOn bool
		wg   sync.WaitGroup
	)
	wg.Add(4)
	go func() {
		defer wg.Done()
		fail2banOn, fail2banErr = EnabledStatus("fail2ban")
	}()
	go func() {
		defer wg.Done()
		modsecOn, modsecErr = EnabledStatus("modsec")
	}()
	go func() {
		defer wg.Done()
		clamavOn, clamavErr = EnabledStatus("clamav")
	}()
	go func() {
		defer wg.Done()
		fwOn, fwErr = FirewallActive()
	}()
	wg.Wait()

	return StatusSnapshot{
		Fail2ban:    moduleState(fail2banOn, fail2banErr),
		Modsecurity: moduleState(modsecOn, modsecErr),
		Clamav:      moduleState(clamavOn, clamavErr),
		Firewall:    moduleState(fwOn, fwErr),
	}
}

func moduleState(enabled bool, err error) ModuleState {
	installed, display := NormalizeComponentError(err)
	if err != nil {
		enabled = false
	}
	return ModuleState{
		Enabled:   enabled,
		Installed: installed,
		Error:     display,
	}
}

// NormalizeComponentError panelze-security hatalarını kurulum/çalışma durumuna çevirir.
func NormalizeComponentError(err error) (installed bool, display string) {
	if err == nil {
		return true, ""
	}
	msg := err.Error()
	low := strings.ToLower(msg)
	if strings.Contains(low, "fail2ban service not installed") {
		return false, ""
	}
	if strings.Contains(low, "missing modsecurity config file") && !strings.Contains(low, "after install") {
		return false, ""
	}
	if strings.Contains(low, "clamav services not installed") {
		return false, ""
	}
	if i := strings.LastIndex(msg, ": "); i >= 0 {
		tail := strings.TrimSpace(msg[i+2:])
		if tail != "" && !strings.HasPrefix(strings.ToLower(tail), "exit status") {
			return true, tail
		}
	}
	return true, msg
}
