package security

import (
	"strconv"
	"strings"
)

type SSHHardeningStatus struct {
	Port                   int      `json:"port"`
	PermitRootLogin        string   `json:"permit_root_login"`
	PasswordAuthentication string   `json:"password_authentication"`
	PubkeyAuthentication   string   `json:"pubkey_authentication"`
	MaxAuthTries           int      `json:"max_auth_tries"`
	Score                  int      `json:"score"`
	Recommendations        []string `json:"recommendations"`
}

func SSHHardeningAudit() (SSHHardeningStatus, error) {
	out, err := run("ssh-hardening-audit")
	if err != nil {
		return SSHHardeningStatus{}, err
	}
	st := SSHHardeningStatus{
		Port:                   22,
		PermitRootLogin:        "unknown",
		PasswordAuthentication: "unknown",
		PubkeyAuthentication:   "unknown",
		MaxAuthTries:           6,
	}
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
		val := strings.ToLower(strings.TrimSpace(kv[1]))
		switch key {
		case "port":
			if n, e := strconv.Atoi(val); e == nil {
				st.Port = n
			}
		case "permitrootlogin":
			st.PermitRootLogin = val
		case "passwordauthentication":
			st.PasswordAuthentication = val
		case "pubkeyauthentication":
			st.PubkeyAuthentication = val
		case "maxauthtries":
			if n, e := strconv.Atoi(val); e == nil {
				st.MaxAuthTries = n
			}
		}
	}
	st.Score, st.Recommendations = scoreSSH(st)
	return st, nil
}

func SSHHardeningApply() (SSHHardeningStatus, error) {
	if _, err := run("ssh-hardening-apply"); err != nil {
		return SSHHardeningStatus{}, err
	}
	return SSHHardeningAudit()
}

func scoreSSH(st SSHHardeningStatus) (int, []string) {
	score := 100
	var rec []string
	if st.Port == 22 {
		score -= 15
		rec = append(rec, "ssh_non_default_port")
	}
	prl := strings.ToLower(st.PermitRootLogin)
	if prl == "yes" {
		score -= 35
		rec = append(rec, "ssh_disable_root_password")
	}
	if strings.EqualFold(st.PasswordAuthentication, "yes") {
		score -= 25
		rec = append(rec, "ssh_disable_password_auth")
	}
	if !strings.EqualFold(st.PubkeyAuthentication, "yes") {
		score -= 20
		rec = append(rec, "ssh_enable_pubkey")
	}
	if st.MaxAuthTries > 4 {
		score -= 10
		rec = append(rec, "ssh_lower_max_auth_tries")
	}
	if score < 0 {
		score = 0
	}
	return score, rec
}
