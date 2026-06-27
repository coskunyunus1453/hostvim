// Package license, panel ile aynı offline imzalı lisans biçimini (Ed25519)
// doğrular. Biçim: PLZ1.<base64url(payload_json)>.<base64url(signature)>
//
// İmza, ortadaki base64url(payload) parçasının ham baytları üzerinden atılır.
// Doğrulama, satıcının gömülü public key'i ile yapılır (private key dağıtılmaz).
package license

import (
	"crypto/ed25519"
	"encoding/base64"
	"encoding/json"
	"strings"
	"time"
)

// DefaultPublicKey — satıcının (vendor) gömülü Ed25519 public key'i (base64).
// Panel config/panelze.php → license.public_key ile AYNI olmalıdır.
const DefaultPublicKey = "FXdTK/pw7dRW0UPabD3SuGKvjZOKdCEpMBAtyTucE4U="

const prefix = "PLZ1"

// Result — doğrulama sonucu (engine yanıtı için).
type Result struct {
	Valid     bool                   `json:"valid"`
	Plan      string                 `json:"plan,omitempty"`
	PlanName  string                 `json:"plan_name,omitempty"`
	Features  map[string]bool        `json:"features,omitempty"`
	ExpiresAt *string                `json:"expires_at"`
	Status    string                 `json:"status,omitempty"`
	Code      string                 `json:"code,omitempty"`
	Message   string                 `json:"message,omitempty"`
	Source    string                 `json:"source"`
	LicenseID string                 `json:"license_id,omitempty"`
	Claims    map[string]interface{} `json:"-"`
}

func fail(code, msg string) Result {
	return Result{Valid: false, Code: code, Message: msg, Source: "offline"}
}

// Verify, anahtarı verilen base64 public key ile doğrular. publicKeyB64 boşsa
// DefaultPublicKey kullanılır. host boş ("") ise domain bağlama kontrolü atlanır.
func Verify(key, publicKeyB64, host string, graceDays int) Result {
	key = strings.TrimSpace(key)
	if publicKeyB64 == "" {
		publicKeyB64 = DefaultPublicKey
	}
	pub, err := base64.StdEncoding.DecodeString(strings.TrimSpace(publicKeyB64))
	if err != nil || len(pub) != ed25519.PublicKeySize {
		return fail("no_public_key", "Gömülü lisans public key geçersiz.")
	}

	parts := strings.Split(key, ".")
	if len(parts) != 3 || parts[0] != prefix {
		return fail("malformed", "Lisans anahtarı biçimi geçersiz.")
	}
	payloadSeg := parts[1]
	sig, err := base64.RawURLEncoding.DecodeString(parts[2])
	if err != nil || len(sig) != ed25519.SignatureSize {
		return fail("malformed", "Lisans anahtarı çözümlenemedi.")
	}
	payloadJSON, err := base64.RawURLEncoding.DecodeString(payloadSeg)
	if err != nil {
		return fail("malformed", "Lisans anahtarı çözümlenemedi.")
	}

	if !ed25519.Verify(ed25519.PublicKey(pub), []byte(payloadSeg), sig) {
		return fail("signature_invalid", "Lisans imzası geçersiz.")
	}

	var claims map[string]interface{}
	if err := json.Unmarshal(payloadJSON, &claims); err != nil {
		return fail("malformed", "Lisans içeriği okunamadı.")
	}

	plan, _ := claims["plan"].(string)
	if plan == "" {
		plan = "standard"
	}
	lid, _ := claims["lid"].(string)

	now := time.Now().Unix()
	status := "active"
	var expiresAt *string
	if expF, ok := claims["exp"].(float64); ok && int64(expF) > 0 {
		exp := int64(expF)
		iso := time.Unix(exp, 0).UTC().Format(time.RFC3339)
		expiresAt = &iso
		grace := graceDays
		if gF, ok := claims["grace"].(float64); ok {
			grace = int(gF)
		}
		if now > exp+int64(grace)*86400 {
			r := fail("expired", "Lisans süresi doldu.")
			r.Plan = plan
			r.ExpiresAt = expiresAt
			r.LicenseID = lid
			return r
		}
		if now > exp {
			status = "grace"
		}
	}

	// Domain bağlama (host verildiyse)
	if host != "" {
		if doms, ok := claims["dom"].([]interface{}); ok && !hostMatches(host, doms) {
			r := fail("domain_mismatch", "Lisans bu host'a bağlı değil.")
			r.Plan = plan
			return r
		}
	}

	code := "ok"
	msg := ""
	if status == "grace" {
		code = "grace"
		msg = "Lisans süresi doldu, ek süre (grace) içinde."
	}

	return Result{
		Valid:     true,
		Plan:      plan,
		PlanName:  plan,
		Features:  normalizeFeatures(claims["feat"]),
		ExpiresAt: expiresAt,
		Status:    status,
		Code:      code,
		Message:   msg,
		Source:    "offline",
		LicenseID: lid,
		Claims:    claims,
	}
}

func normalizeFeatures(feat interface{}) map[string]bool {
	out := map[string]bool{}
	switch v := feat.(type) {
	case []interface{}:
		for _, item := range v {
			if s, ok := item.(string); ok && s != "" {
				out[s] = true
			}
		}
	case map[string]interface{}:
		for k, val := range v {
			switch vv := val.(type) {
			case bool:
				out[k] = vv
			case map[string]interface{}:
				en, _ := vv["enabled"].(bool)
				out[k] = en
			default:
				out[k] = true
			}
		}
	}
	return out
}

func hostMatches(host string, doms []interface{}) bool {
	if len(doms) == 0 {
		return true
	}
	host = strings.ToLower(strings.TrimSpace(host))
	for _, d := range doms {
		ds, ok := d.(string)
		if !ok {
			continue
		}
		ds = strings.ToLower(strings.TrimSpace(ds))
		if ds == "*" || ds == host {
			return true
		}
		if strings.HasPrefix(ds, "*.") {
			suffix := ds[1:] // ".x.com"
			if host == ds[2:] || strings.HasSuffix(host, suffix) {
				return true
			}
		}
	}
	return false
}
