package license

import (
	"crypto/ed25519"
	"encoding/base64"
	"encoding/json"
	"testing"
	"time"
)

// issue, test imzası üretir (panel `license:issue` ile aynı biçim).
func issue(t *testing.T, priv ed25519.PrivateKey, claims map[string]interface{}) string {
	t.Helper()
	payloadJSON, _ := json.Marshal(claims)
	payloadSeg := base64.RawURLEncoding.EncodeToString(payloadJSON)
	sig := ed25519.Sign(priv, []byte(payloadSeg))
	return prefix + "." + payloadSeg + "." + base64.RawURLEncoding.EncodeToString(sig)
}

func newKeys(t *testing.T) (string, ed25519.PrivateKey) {
	t.Helper()
	pub, priv, err := ed25519.GenerateKey(nil)
	if err != nil {
		t.Fatal(err)
	}
	return base64.StdEncoding.EncodeToString(pub), priv
}

func TestVerifyValid(t *testing.T) {
	pubB64, priv := newKeys(t)
	key := issue(t, priv, map[string]interface{}{
		"plan": "enterprise",
		"dom":  []interface{}{"panel.acme.com"},
		"exp":  time.Now().Add(24 * time.Hour).Unix(),
		"feat": []interface{}{"phpmyadmin_sso"},
	})
	r := Verify(key, pubB64, "panel.acme.com", 14)
	if !r.Valid || r.Plan != "enterprise" || !r.Features["phpmyadmin_sso"] {
		t.Fatalf("beklenen geçerli enterprise, alındı: %+v", r)
	}
}

func TestVerifyExpired(t *testing.T) {
	pubB64, priv := newKeys(t)
	key := issue(t, priv, map[string]interface{}{
		"plan": "pro",
		"exp":  time.Now().Add(-48 * time.Hour).Unix(),
	})
	if r := Verify(key, pubB64, "", 0); r.Valid || r.Code != "expired" {
		t.Fatalf("beklenen expired, alındı: %+v", r)
	}
}

func TestVerifyGrace(t *testing.T) {
	pubB64, priv := newKeys(t)
	key := issue(t, priv, map[string]interface{}{
		"plan": "pro",
		"exp":  time.Now().Add(-2 * time.Hour).Unix(),
	})
	r := Verify(key, pubB64, "", 14)
	if !r.Valid || r.Status != "grace" {
		t.Fatalf("beklenen grace içinde geçerli, alındı: %+v", r)
	}
}

func TestVerifyDomainMismatch(t *testing.T) {
	pubB64, priv := newKeys(t)
	key := issue(t, priv, map[string]interface{}{
		"plan": "pro",
		"dom":  []interface{}{"panel.acme.com"},
		"exp":  0,
	})
	if r := Verify(key, pubB64, "baska.com", 14); r.Valid || r.Code != "domain_mismatch" {
		t.Fatalf("beklenen domain_mismatch, alındı: %+v", r)
	}
}

func TestVerifyWrongKey(t *testing.T) {
	_, priv := newKeys(t)
	otherPub, _ := newKeys(t)
	key := issue(t, priv, map[string]interface{}{"plan": "pro", "exp": 0})
	if r := Verify(key, otherPub, "", 14); r.Valid || r.Code != "signature_invalid" {
		t.Fatalf("beklenen signature_invalid, alındı: %+v", r)
	}
}

func TestVerifyMalformed(t *testing.T) {
	pubB64, _ := newKeys(t)
	for _, k := range []string{"", "abc", "PLZ1.x", "PLZ1.x.y", "NOPE.a.b"} {
		if r := Verify(k, pubB64, "", 14); r.Valid {
			t.Fatalf("beklenen geçersiz: %q -> %+v", k, r)
		}
	}
}

func TestVerifyWildcardDomain(t *testing.T) {
	pubB64, priv := newKeys(t)
	key := issue(t, priv, map[string]interface{}{
		"plan": "pro",
		"dom":  []interface{}{"*.acme.com"},
		"exp":  0,
	})
	if r := Verify(key, pubB64, "panel.acme.com", 14); !r.Valid {
		t.Fatalf("wildcard eşleşmeli: %+v", r)
	}
}
