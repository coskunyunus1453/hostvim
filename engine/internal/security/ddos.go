package security

import (
	"strconv"
	"strings"
)

type DdosSysctlStatus struct {
	TcpSyncookies      int `json:"tcp_syncookies"`
	TcpMaxSynBacklog   int `json:"tcp_max_syn_backlog"`
	TcpFinTimeout      int `json:"tcp_fin_timeout"`
	Score              int `json:"score"`
	SynCookiesEnabled  bool `json:"syn_cookies_enabled"`
}

func DdosSysctlStatusGet() (DdosSysctlStatus, error) {
	out, err := run("ddos-sysctl-status")
	if err != nil {
		return DdosSysctlStatus{}, err
	}
	st := DdosSysctlStatus{}
	for _, line := range strings.Split(out, "\n") {
		line = strings.TrimSpace(line)
		if line == "" {
			continue
		}
		kv := strings.SplitN(line, "=", 2)
		if len(kv) != 2 {
			continue
		}
		key := strings.ToLower(strings.TrimSpace(kv[0]))
		val := strings.TrimSpace(kv[1])
		n, _ := strconv.Atoi(val)
		switch key {
		case "tcp_syncookies":
			st.TcpSyncookies = n
			st.SynCookiesEnabled = n == 1
		case "tcp_max_syn_backlog":
			st.TcpMaxSynBacklog = n
		case "tcp_fin_timeout":
			st.TcpFinTimeout = n
		}
	}
	st.Score = 50
	if st.SynCookiesEnabled {
		st.Score += 30
	}
	if st.TcpMaxSynBacklog >= 2048 {
		st.Score += 10
	}
	if st.TcpFinTimeout > 0 && st.TcpFinTimeout <= 30 {
		st.Score += 10
	}
	if st.Score > 100 {
		st.Score = 100
	}
	return st, nil
}

func DdosSysctlHarden() (DdosSysctlStatus, error) {
	if _, err := run("ddos-sysctl-harden"); err != nil {
		return DdosSysctlStatus{}, err
	}
	return DdosSysctlStatusGet()
}
