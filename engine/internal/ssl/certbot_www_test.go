package ssl

import (
	"testing"

	"hostvim/engine/internal/config"
)

func TestCertbotShouldIncludeWww(t *testing.T) {
	cfg := &config.Config{}
	cfg.Hosting.LetsEncryptIncludeWww = true

	cases := []struct {
		domain string
		want   bool
	}{
		{"ornek.com", true},
		{"www.ornek.com", false},
		{"blog.ornek.com", false},
		{"api.staging.ornek.com", false},
	}
	for _, tc := range cases {
		if got := certbotShouldIncludeWww(cfg, tc.domain); got != tc.want {
			t.Errorf("certbotShouldIncludeWww(%q) = %v, want %v", tc.domain, got, tc.want)
		}
	}

	cfg.Hosting.LetsEncryptIncludeWww = false
	if certbotShouldIncludeWww(cfg, "ornek.com") {
		t.Error("flag false iken www eklenmemeli")
	}
}
