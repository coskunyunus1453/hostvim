package nodeapp

import (
	"fmt"
	"path/filepath"
	"strings"

	"panelze/engine/internal/config"
	"panelze/engine/internal/hosting"
	"panelze/engine/internal/sites"
)

// SiteScope bir Node uygulamasının barındırma konumu (ana site veya alt alan).
type SiteScope struct {
	ParentDomain string
	PathSegment  string
}

func scopeFrom(parentDomain, pathSegment string) SiteScope {
	return SiteScope{
		ParentDomain: strings.ToLower(strings.TrimSpace(parentDomain)),
		PathSegment:  strings.TrimSpace(pathSegment),
	}
}

// ScopeFrom API katmanı için dışa açık site kapsamı oluşturur.
func ScopeFrom(parentDomain, pathSegment string) SiteScope {
	return scopeFrom(parentDomain, pathSegment)
}

func (sc SiteScope) isSubdomain() bool {
	return sc.PathSegment != ""
}

// SiteBase public_html yolunu döndürür.
func (sc SiteScope) SiteBase(webRoot string) string {
	return sc.siteBase(webRoot)
}

// ReadMeta site veya alt site meta verisini okur.
func (sc SiteScope) ReadMeta(webRoot string) (*sites.SiteMeta, error) {
	return sc.readMeta(webRoot)
}

func (sc SiteScope) siteBase(webRoot string) string {
	if sc.isSubdomain() {
		return filepath.Clean(filepath.Join(webRoot, sc.ParentDomain, sc.PathSegment, "public_html"))
	}
	return filepath.Clean(filepath.Join(webRoot, sc.ParentDomain, "public_html"))
}

func (sc SiteScope) readMeta(webRoot string) (*sites.SiteMeta, error) {
	if sc.isSubdomain() {
		return sites.ReadSubdomainMeta(webRoot, sc.ParentDomain, sc.PathSegment)
	}
	return sites.ReadSiteMeta(webRoot, sc.ParentDomain)
}

func (sc SiteScope) writeMeta(webRoot string, meta *sites.SiteMeta) error {
	if sc.isSubdomain() {
		return sites.WriteSubdomainMeta(webRoot, sc.ParentDomain, sc.PathSegment, meta)
	}
	return sites.WriteSiteMeta(webRoot, sc.ParentDomain, meta)
}

func (sc SiteScope) pm2Name() string {
	key := sc.ParentDomain
	if sc.isSubdomain() {
		key = sc.ParentDomain + "--" + sc.PathSegment
	}
	key = strings.ToLower(strings.TrimSpace(key))
	key = strings.ReplaceAll(key, ".", "-")
	key = strings.ReplaceAll(key, "/", "-")
	return "panelze-" + key
}

func (sc SiteScope) publicHostname(meta *sites.SiteMeta) string {
	if meta != nil && strings.TrimSpace(meta.Hostname) != "" {
		return strings.ToLower(strings.TrimSpace(meta.Hostname))
	}
	if !sc.isSubdomain() {
		return sc.ParentDomain
	}
	return ""
}

func (sc SiteScope) vhostDomain(meta *sites.SiteMeta) string {
	if h := sc.publicHostname(meta); h != "" {
		return h
	}
	return sc.ParentDomain
}

func (sc SiteScope) applyWebServer(cfg *config.Config, meta *sites.SiteMeta) error {
	docRoot := ""
	if meta != nil {
		docRoot = strings.TrimSpace(meta.DocumentRoot)
	}
	if docRoot == "" {
		docRoot = sc.siteBase(cfg.Paths.WebRoot)
	}
	if sc.isSubdomain() {
		return hosting.ApplySubdomainVhost(cfg, sc.ParentDomain, sc.vhostDomain(meta), docRoot, meta)
	}
	parentMeta, _ := sites.ReadSiteMeta(cfg.Paths.WebRoot, sc.ParentDomain)
	sock := ""
	if parentMeta != nil {
		_ = parentMeta
	}
	return hosting.ApplyWebServer(cfg, sc.ParentDomain, docRoot, meta, sock)
}

func (sc SiteScope) resolveWorkAbs(webRoot, workDirRel string) (workAbs, workRel string, err error) {
	base := sc.siteBase(webRoot)
	workDirRel = strings.TrimSpace(workDirRel)
	if workDirRel == "" {
		workDirRel = "."
	}
	if strings.Contains(workDirRel, "..") || filepath.IsAbs(workDirRel) {
		return "", "", fmt.Errorf("invalid work_dir")
	}
	workAbs = base
	if workDirRel != "." {
		workAbs = filepath.Join(base, workDirRel)
	}
	workAbs = filepath.Clean(workAbs)
	if !strings.HasPrefix(workAbs+string(filepath.Separator), base+string(filepath.Separator)) && workAbs != base {
		return "", "", fmt.Errorf("work_dir escapes site base")
	}
	return workAbs, workDirRel, nil
}

func (sc SiteScope) label() string {
	if sc.isSubdomain() {
		return sc.ParentDomain + "/" + sc.PathSegment
	}
	return sc.ParentDomain
}
