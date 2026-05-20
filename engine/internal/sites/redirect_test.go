package sites

import (
	"strings"
	"testing"
)

func TestBuildNginxRedirectRender_exact301WithQuery(t *testing.T) {
	render, err := BuildNginxRedirectRender([]RedirectRule{{
		ID:            "old-page",
		Source:        "/eski",
		Target:        "/yeni",
		Status:        301,
		Enabled:       true,
		PreserveQuery: true,
		MatchType:     "exact",
	}})
	if err != nil {
		t.Fatal(err)
	}
	if !strings.Contains(render.Blocks, "location = /eski") {
		t.Fatalf("expected exact location, got:\n%s", render.Blocks)
	}
	if !strings.Contains(render.Blocks, "return 301 /yeni$is_args$args") {
		t.Fatalf("expected 301 with query suffix, got:\n%s", render.Blocks)
	}
}

func TestBuildNginxRedirectRender_wildcard(t *testing.T) {
	render, err := BuildNginxRedirectRender([]RedirectRule{{
		ID:        "blog",
		Source:    "/blog/*",
		Target:    "/yazi/*",
		Status:    301,
		Enabled:   true,
		MatchType: "wildcard",
	}})
	if err != nil {
		t.Fatal(err)
	}
	if !strings.Contains(render.Blocks, `location ~ ^/blog/(.*)$`) {
		t.Fatalf("expected wildcard regex, got:\n%s", render.Blocks)
	}
	if !strings.Contains(render.Blocks, "return 301 /yazi/$1") {
		t.Fatalf("expected captured path in target, got:\n%s", render.Blocks)
	}
}

func TestBuildNginxRedirectRender_fullSite(t *testing.T) {
	render, err := BuildNginxRedirectRender([]RedirectRule{{
		ID:      "all",
		Source:  "/",
		Target:  "https://example.com",
		Status:  301,
		Enabled: true,
	}})
	if err != nil {
		t.Fatal(err)
	}
	if !render.HasFullSiteRedirect {
		t.Fatal("expected full site redirect")
	}
	dir := FullSiteReturnDirective(render)
	if dir != "return 301 https://example.com$request_uri$is_args$args;" {
		t.Fatalf("unexpected full site directive: %q", dir)
	}
}

func TestNormalizeRedirectRules_rejectsInvalidStatus(t *testing.T) {
	_, err := NormalizeRedirectRules([]RedirectRule{{
		Source: "/a",
		Target: "/b",
		Status: 303,
	}})
	if err == nil {
		t.Fatal("expected error for status 303")
	}
}
