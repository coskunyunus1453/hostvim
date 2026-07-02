package backup

import (
	"bytes"
	"context"
	"fmt"
	"io"
	"os"
	"os/exec"
	"path/filepath"
	"strings"

	"panelze/engine/internal/config"
)

// ArchiveDomain creates a gzip tar of paths.WebRoot/domain into destPath.
// destPath must lie under allowedDestPrefix (engine-state/backup-files).
func ArchiveDomain(ctx context.Context, cfg *config.Config, domain, destPath, allowedDestPrefix string) error {
	if cfg == nil {
		return fmt.Errorf("nil config")
	}
	webRoot := filepath.Clean(cfg.Paths.WebRoot)
	if webRoot == "" || webRoot == "." {
		return fmt.Errorf("paths.web_root is not set")
	}
	rel, err := filepath.Rel(webRoot, filepath.Join(webRoot, domain))
	if err != nil || rel == ".." || strings.HasPrefix(rel, ".."+string(filepath.Separator)) {
		return fmt.Errorf("invalid domain path")
	}
	siteDir := filepath.Join(webRoot, domain)
	fi, err := os.Stat(siteDir)
	if err != nil {
		return fmt.Errorf("site directory: %w", err)
	}
	if !fi.IsDir() {
		return fmt.Errorf("site path is not a directory")
	}

	absDest, err := filepath.Abs(filepath.Clean(destPath))
	if err != nil {
		return fmt.Errorf("dest path: %w", err)
	}
	absPrefix, err := filepath.Abs(filepath.Clean(allowedDestPrefix))
	if err != nil {
		return fmt.Errorf("backup dir: %w", err)
	}
	if !strings.HasPrefix(absDest, absPrefix+string(filepath.Separator)) && absDest != absPrefix {
		return fmt.Errorf("refusing to write backup outside backup directory")
	}
	if err := os.MkdirAll(filepath.Dir(absDest), 0o750); err != nil {
		return fmt.Errorf("mkdir backup parent: %w", err)
	}

	tarBin := strings.TrimSpace(cfg.Hosting.BackupTarPath)
	if tarBin == "" {
		tarBin = "tar"
	}

	// GNU/BSD tar: archive top-level folder "domain" under webRoot.
	var stderr bytes.Buffer
	cmd := exec.CommandContext(ctx, tarBin, "-czf", absDest, "-C", webRoot, domain)
	cmd.Stderr = &stderr
	if err := cmd.Run(); err != nil {
		_ = os.Remove(absDest)
		msg := strings.TrimSpace(stderr.String())
		if msg != "" {
			return fmt.Errorf("tar: %w (%s)", err, msg)
		}
		return fmt.Errorf("tar: %w", err)
	}
	return nil
}

// ArchiveDomainIncremental, GNU tar --listed-incremental ile arttırımlı (veya level-0 tam)
// yedek üretir.
//
//   - parentSnapshot boş/eksikse: snapshotOut sıfırdan oluşturulur → TAM (level 0) yedek.
//   - parentSnapshot varsa: içeriği snapshotOut'a kopyalanır ve tar yalnızca o snapshot'tan
//     BU YANA değişen dosyaları arşivler (arttırımlı). tar, snapshotOut'u yerinde günceller;
//     böylece snapshotOut bu yedeğin "sonrası" durumunu yansıtır ve bir sonraki arttırımlı
//     yedek bundan devam eder.
//
// destPath ve snapshotOut, allowedDestPrefix altında olmalıdır.
func ArchiveDomainIncremental(ctx context.Context, cfg *config.Config, domain, destPath, snapshotOut, parentSnapshot, allowedDestPrefix string) error {
	if cfg == nil {
		return fmt.Errorf("nil config")
	}
	webRoot := filepath.Clean(cfg.Paths.WebRoot)
	if webRoot == "" || webRoot == "." {
		return fmt.Errorf("paths.web_root is not set")
	}
	rel, err := filepath.Rel(webRoot, filepath.Join(webRoot, domain))
	if err != nil || rel == ".." || strings.HasPrefix(rel, ".."+string(filepath.Separator)) {
		return fmt.Errorf("invalid domain path")
	}
	siteDir := filepath.Join(webRoot, domain)
	fi, err := os.Stat(siteDir)
	if err != nil {
		return fmt.Errorf("site directory: %w", err)
	}
	if !fi.IsDir() {
		return fmt.Errorf("site path is not a directory")
	}

	absPrefix, err := filepath.Abs(filepath.Clean(allowedDestPrefix))
	if err != nil {
		return fmt.Errorf("backup dir: %w", err)
	}
	absDest, err := ensureUnderPrefix(destPath, absPrefix)
	if err != nil {
		return err
	}
	absSnap, err := ensureUnderPrefix(snapshotOut, absPrefix)
	if err != nil {
		return err
	}
	if err := os.MkdirAll(filepath.Dir(absDest), 0o750); err != nil {
		return fmt.Errorf("mkdir backup parent: %w", err)
	}
	if err := os.MkdirAll(filepath.Dir(absSnap), 0o750); err != nil {
		return fmt.Errorf("mkdir snapshot parent: %w", err)
	}

	// Snapshot hazırlığı: parent varsa kopyala (arttırımlı), yoksa temiz başla (tam).
	_ = os.Remove(absSnap)
	if strings.TrimSpace(parentSnapshot) != "" {
		absParent, perr := ensureUnderPrefix(parentSnapshot, absPrefix)
		if perr == nil {
			if _, serr := os.Stat(absParent); serr == nil {
				if cerr := copyFile(absParent, absSnap); cerr != nil {
					return fmt.Errorf("copy parent snapshot: %w", cerr)
				}
			}
		}
	}

	tarBin := strings.TrimSpace(cfg.Hosting.BackupTarPath)
	if tarBin == "" {
		tarBin = "tar"
	}

	var stderr bytes.Buffer
	cmd := exec.CommandContext(ctx, tarBin,
		"--listed-incremental="+absSnap,
		"-czf", absDest,
		"-C", webRoot, domain,
	)
	cmd.Stderr = &stderr
	if err := cmd.Run(); err != nil {
		_ = os.Remove(absDest)
		msg := strings.TrimSpace(stderr.String())
		if msg != "" {
			return fmt.Errorf("tar incremental: %w (%s)", err, msg)
		}
		return fmt.Errorf("tar incremental: %w", err)
	}
	return nil
}

// RestoreDomainChain, arttırımlı zinciri (base → ... → hedef) SIRAYLA açar.
// Her arşiv --listed-incremental=/dev/null ile açılır; böylece silinen dosyalar da
// doğru şekilde uygulanır (GNU tar incremental restore semantiği).
func RestoreDomainChain(ctx context.Context, cfg *config.Config, archivePaths []string, allowedArchivePrefix string) error {
	if cfg == nil {
		return fmt.Errorf("nil config")
	}
	if len(archivePaths) == 0 {
		return fmt.Errorf("empty restore chain")
	}
	webRoot := filepath.Clean(cfg.Paths.WebRoot)
	if webRoot == "" {
		return fmt.Errorf("paths.web_root is not set")
	}
	absWeb, err := filepath.Abs(webRoot)
	if err != nil {
		return fmt.Errorf("web root: %w", err)
	}
	if fi, err := os.Stat(absWeb); err != nil || !fi.IsDir() {
		if err != nil {
			return fmt.Errorf("web root: %w", err)
		}
		return fmt.Errorf("web root is not a directory")
	}
	absArcPrefix, err := filepath.Abs(filepath.Clean(allowedArchivePrefix))
	if err != nil {
		return fmt.Errorf("archive prefix: %w", err)
	}

	tarBin := strings.TrimSpace(cfg.Hosting.BackupTarPath)
	if tarBin == "" {
		tarBin = "tar"
	}

	for _, p := range archivePaths {
		absArc, aerr := ensureUnderPrefix(p, absArcPrefix)
		if aerr != nil {
			return aerr
		}
		if _, serr := os.Stat(absArc); serr != nil {
			return fmt.Errorf("archive: %w", serr)
		}
		var stderr bytes.Buffer
		cmd := exec.CommandContext(ctx, tarBin,
			"--listed-incremental=/dev/null",
			"-xzf", absArc,
			"-C", absWeb,
		)
		cmd.Stderr = &stderr
		if err := cmd.Run(); err != nil {
			msg := strings.TrimSpace(stderr.String())
			if msg != "" {
				return fmt.Errorf("tar extract (%s): %w (%s)", filepath.Base(absArc), err, msg)
			}
			return fmt.Errorf("tar extract (%s): %w", filepath.Base(absArc), err)
		}
	}
	return nil
}

// ensureUnderPrefix, path'i mutlaklaştırır ve prefix altında kaldığını doğrular.
func ensureUnderPrefix(path, absPrefix string) (string, error) {
	abs, err := filepath.Abs(filepath.Clean(path))
	if err != nil {
		return "", fmt.Errorf("path: %w", err)
	}
	if abs != absPrefix && !strings.HasPrefix(abs, absPrefix+string(filepath.Separator)) {
		return "", fmt.Errorf("refusing to access path outside backup directory")
	}
	return abs, nil
}

func copyFile(src, dst string) error {
	in, err := os.Open(src)
	if err != nil {
		return err
	}
	defer in.Close()
	out, err := os.OpenFile(dst, os.O_WRONLY|os.O_CREATE|os.O_TRUNC, 0o640)
	if err != nil {
		return err
	}
	if _, err := io.Copy(out, in); err != nil {
		out.Close()
		return err
	}
	return out.Close()
}

// RestoreDomain extracts a tar.gz created by ArchiveDomain into paths.WebRoot (overwrites site files).
func RestoreDomain(ctx context.Context, cfg *config.Config, archivePath, allowedArchivePrefix string) error {
	if cfg == nil {
		return fmt.Errorf("nil config")
	}
	webRoot := filepath.Clean(cfg.Paths.WebRoot)
	if webRoot == "" {
		return fmt.Errorf("paths.web_root is not set")
	}
	absArc, err := filepath.Abs(filepath.Clean(archivePath))
	if err != nil {
		return fmt.Errorf("archive path: %w", err)
	}
	absArcPrefix, err := filepath.Abs(filepath.Clean(allowedArchivePrefix))
	if err != nil {
		return fmt.Errorf("archive prefix: %w", err)
	}
	if !strings.HasPrefix(absArc, absArcPrefix+string(filepath.Separator)) && absArc != absArcPrefix {
		return fmt.Errorf("refusing to read archive outside backup directory")
	}
	if _, err := os.Stat(absArc); err != nil {
		return fmt.Errorf("archive: %w", err)
	}
	absWeb, err := filepath.Abs(webRoot)
	if err != nil {
		return fmt.Errorf("web root: %w", err)
	}
	if fi, err := os.Stat(absWeb); err != nil || !fi.IsDir() {
		if err != nil {
			return fmt.Errorf("web root: %w", err)
		}
		return fmt.Errorf("web root is not a directory")
	}

	tarBin := strings.TrimSpace(cfg.Hosting.BackupTarPath)
	if tarBin == "" {
		tarBin = "tar"
	}
	var stderr bytes.Buffer
	cmd := exec.CommandContext(ctx, tarBin, "-xzf", absArc, "-C", absWeb)
	cmd.Stderr = &stderr
	if err := cmd.Run(); err != nil {
		msg := strings.TrimSpace(stderr.String())
		if msg != "" {
			return fmt.Errorf("tar extract: %w (%s)", err, msg)
		}
		return fmt.Errorf("tar extract: %w", err)
	}
	return nil
}
