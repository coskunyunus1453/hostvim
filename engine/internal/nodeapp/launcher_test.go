package nodeapp

import (
	"os"
	"path/filepath"
	"testing"
)

func TestInferPortFromWorkDirNextJS(t *testing.T) {
	t.Parallel()
	base := t.TempDir()
	pkg := `{"scripts":{"start":"next start -p 3003","dev":"next dev -p 3003"}}`
	if err := os.WriteFile(filepath.Join(base, "package.json"), []byte(pkg), 0o644); err != nil {
		t.Fatal(err)
	}
	got := inferPortFromWorkDir(base, ".", "nextjs")
	if got != 3003 {
		t.Fatalf("expected 3003, got %d", got)
	}
}

func TestBuildPm2StartPlanNextJS(t *testing.T) {
	t.Parallel()
	base := t.TempDir()
	binDir := filepath.Join(base, "node_modules", ".bin")
	if err := os.MkdirAll(binDir, 0o755); err != nil {
		t.Fatal(err)
	}
	if err := os.WriteFile(filepath.Join(binDir, "next"), []byte("#!/bin/sh\n"), 0o755); err != nil {
		t.Fatal(err)
	}
	plan, err := buildPm2StartPlan(base, "nextjs", "start", 3003)
	if err != nil {
		t.Fatal(err)
	}
	if plan.Program == "npm" {
		t.Fatalf("expected direct next binary, got npm")
	}
	if len(plan.Args) < 4 || plan.Args[len(plan.Args)-1] != "3003" {
		t.Fatalf("unexpected args: %v", plan.Args)
	}
}
