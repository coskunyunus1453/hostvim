package system

import (
	"encoding/json"
	"fmt"
	"net"
	"os"
	"os/exec"
	"path/filepath"
	"runtime"
	"strings"
	"sync"

	psnet "github.com/shirou/gopsutil/v3/net"
)

// IPAddress ağ arayüzüne bağlı tek adres.
type IPAddress struct {
	Address string `json:"address"`
	Family  string `json:"family"`
	Scope   string `json:"scope,omitempty"`
	Primary bool   `json:"primary"`
	Managed bool   `json:"managed"`
	Label   string `json:"label,omitempty"`
}

// NetworkInterface sunucu ağ arayüzü özeti.
type NetworkInterface struct {
	Name      string      `json:"name"`
	MAC       string      `json:"mac,omitempty"`
	State     string      `json:"state"`
	MTU       int         `json:"mtu"`
	Addresses []IPAddress `json:"addresses"`
}

type managedAlias struct {
	Interface string `json:"interface"`
	Address   string `json:"address"`
	Label     string `json:"label,omitempty"`
}

type aliasStore struct {
	Aliases []managedAlias `json:"aliases"`
}

var aliasMu sync.Mutex

func skipInterface(name string) bool {
	n := strings.ToLower(strings.TrimSpace(name))
	if n == "" || n == "lo" {
		return true
	}
	prefixes := []string{"docker", "br-", "veth", "virbr", "tun", "tap", "wg", "cni", "flannel", "kube", "dummy"}
	for _, p := range prefixes {
		if strings.HasPrefix(n, p) {
			return true
		}
	}
	return false
}

func aliasesPath(dataDir string) string {
	base := strings.TrimSpace(dataDir)
	if base == "" {
		base = "/var/lib/panelze"
	}
	return filepath.Join(base, "network-aliases.json")
}

func loadAliases(dataDir string) aliasStore {
	aliasMu.Lock()
	defer aliasMu.Unlock()
	p := aliasesPath(dataDir)
	b, err := os.ReadFile(p)
	if err != nil {
		return aliasStore{}
	}
	var st aliasStore
	if json.Unmarshal(b, &st) != nil {
		return aliasStore{}
	}
	return st
}

func saveAliases(dataDir string, st aliasStore) error {
	aliasMu.Lock()
	defer aliasMu.Unlock()
	p := aliasesPath(dataDir)
	if err := os.MkdirAll(filepath.Dir(p), 0o750); err != nil {
		return err
	}
	b, err := json.MarshalIndent(st, "", "  ")
	if err != nil {
		return err
	}
	return os.WriteFile(p, b, 0o640)
}

func managedSet(st aliasStore) map[string]managedAlias {
	out := map[string]managedAlias{}
	for _, a := range st.Aliases {
		key := strings.ToLower(a.Interface + "|" + a.Address)
		out[key] = a
	}
	return out
}

// PrimaryIP ilk uygun genel IPv4 adresini döndürür.
func PrimaryIP() string {
	ifaces, err := psnet.Interfaces()
	if err != nil {
		return ""
	}
	for _, iface := range ifaces {
		if skipInterface(iface.Name) {
			continue
		}
		for _, addr := range iface.Addrs {
			ip, _, err := net.ParseCIDR(addr.Addr)
			if err != nil || ip == nil || ip.IsLoopback() {
				continue
			}
			if v4 := ip.To4(); v4 != nil && !v4.IsLinkLocalUnicast() {
				return v4.String()
			}
		}
	}
	return ""
}

// ListNetwork arayüzleri ve atanmış IP'leri listeler.
func ListNetwork(dataDir string) ([]NetworkInterface, string, error) {
	ifaces, err := psnet.Interfaces()
	if err != nil {
		return nil, "", err
	}
	managed := managedSet(loadAliases(dataDir))
	primary := PrimaryIP()
	var rows []NetworkInterface
	for _, iface := range ifaces {
		if skipInterface(iface.Name) {
			continue
		}
		row := NetworkInterface{
			Name:  iface.Name,
			MTU:   int(iface.MTU),
			State: "unknown",
		}
		if len(iface.HardwareAddr) > 0 {
			row.MAC = iface.HardwareAddr
		}
		for _, addr := range iface.Addrs {
			ip, _, err := net.ParseCIDR(addr.Addr)
			if err != nil || ip == nil {
				continue
			}
			family := "ipv6"
			if ip.To4() != nil {
				family = "ipv4"
			}
			scope := "global"
			if ip.IsLoopback() {
				scope = "host"
			} else if ip.IsLinkLocalUnicast() || ip.IsLinkLocalMulticast() {
				scope = "link"
			}
			isPrimary := family == "ipv4" && ip.String() == primary
			key := strings.ToLower(iface.Name + "|" + addr.Addr)
			m, ok := managed[key]
			row.Addresses = append(row.Addresses, IPAddress{
				Address: addr.Addr,
				Family:  family,
				Scope:   scope,
				Primary: isPrimary,
				Managed: ok,
				Label:   m.Label,
			})
		}
		if len(row.Addresses) > 0 {
			rows = append(rows, row)
		}
	}
	return rows, primary, nil
}

// ApplyManagedAliases kayıtlı panel IP'lerini arayüze uygular.
func ApplyManagedAliases(dataDir string) error {
	st := loadAliases(dataDir)
	var lastErr error
	for _, a := range st.Aliases {
		if err := runIPAdd(a.Interface, a.Address); err != nil {
			lastErr = err
		}
	}
	return lastErr
}

// AddNetworkAddress yeni IP ekler ve kalıcı listeye yazar.
func AddNetworkAddress(dataDir, iface, address, label string) error {
	iface = strings.TrimSpace(iface)
	address = strings.TrimSpace(address)
	if iface == "" || address == "" {
		return fmt.Errorf("interface and address required")
	}
	if _, _, err := net.ParseCIDR(address); err != nil {
		if net.ParseIP(address) == nil {
			return fmt.Errorf("invalid address")
		}
		address = address + "/32"
	}
	if skipInterface(iface) {
		return fmt.Errorf("invalid interface")
	}
	if err := runIPAdd(iface, address); err != nil {
		return err
	}
	st := loadAliases(dataDir)
	key := strings.ToLower(iface + "|" + address)
	for _, a := range st.Aliases {
		if strings.ToLower(a.Interface+"|"+a.Address) == key {
			return nil
		}
	}
	st.Aliases = append(st.Aliases, managedAlias{Interface: iface, Address: address, Label: strings.TrimSpace(label)})
	return saveAliases(dataDir, st)
}

// RemoveNetworkAddress IP'yi kaldırır; yalnızca panel tarafından eklenen veya ikincil adresler.
func RemoveNetworkAddress(dataDir, iface, address string, force bool) error {
	iface = strings.TrimSpace(iface)
	address = strings.TrimSpace(address)
	if iface == "" || address == "" {
		return fmt.Errorf("interface and address required")
	}
	if !strings.Contains(address, "/") {
		address = address + "/32"
	}
	primary := PrimaryIP()
	host := strings.Split(address, "/")[0]
	if !force && host == primary {
		return fmt.Errorf("primary IP cannot be removed")
	}
	st := loadAliases(dataDir)
	key := strings.ToLower(iface + "|" + address)
	managed := false
	var kept []managedAlias
	for _, a := range st.Aliases {
		if strings.ToLower(a.Interface+"|"+a.Address) == key {
			managed = true
			continue
		}
		kept = append(kept, a)
	}
	if !managed && !force {
		return fmt.Errorf("only panel-managed secondary addresses can be removed")
	}
	if err := runIPDel(iface, address); err != nil {
		return err
	}
	st.Aliases = kept
	return saveAliases(dataDir, st)
}

func runIPAdd(iface, address string) error {
	if runtime.GOOS != "linux" {
		return fmt.Errorf("network address changes supported only on Linux")
	}
	args := []string{"addr", "add", address, "dev", iface}
	if _, err := exec.Command("ip", args...).CombinedOutput(); err == nil {
		return nil
	}
	out, err := exec.Command("sudo", append([]string{"-n", "ip"}, args...)...).CombinedOutput()
	if err != nil {
		return fmt.Errorf("%s: %s", strings.TrimSpace(err.Error()), strings.TrimSpace(string(out)))
	}
	return nil
}

func runIPDel(iface, address string) error {
	if runtime.GOOS != "linux" {
		return fmt.Errorf("network address changes supported only on Linux")
	}
	args := []string{"addr", "del", address, "dev", iface}
	if _, err := exec.Command("ip", args...).CombinedOutput(); err == nil {
		return nil
	}
	out, err := exec.Command("sudo", append([]string{"-n", "ip"}, args...)...).CombinedOutput()
	if err != nil {
		return fmt.Errorf("%s: %s", strings.TrimSpace(err.Error()), strings.TrimSpace(string(out)))
	}
	return nil
}
