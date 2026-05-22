package api

import (
	"net/http"
	"path/filepath"
	"strings"

	"github.com/gin-gonic/gin"
	"hostvim/engine/internal/config"
	"hostvim/engine/internal/hosting"
	"hostvim/engine/internal/nginx"
	"hostvim/engine/internal/sites"
)

func registerStackScanRoutes(cfg *config.Config, site *gin.RouterGroup) {
	site.GET("/:domain/stack-scan", handleStackScan(cfg))
	site.POST("/:domain/laravel-storage-link", handleLaravelStorageLink(cfg))
	site.POST("/:domain/normalize-public-urls", handleNormalizePublicURLs(cfg))
}

func handleNormalizePublicURLs(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		domain := strings.ToLower(strings.TrimSpace(c.Param("domain")))
		if domain == "" || strings.Contains(domain, "..") || !nginx.DomainSafe(domain) {
			c.JSON(http.StatusBadRequest, gin.H{"error": "invalid domain"})
			return
		}
		base := detectDocumentRootBase(cfg.Paths.WebRoot, domain)
		meta, err := sites.ReadSiteMeta(cfg.Paths.WebRoot, domain)
		if err != nil || meta == nil {
			c.JSON(http.StatusNotFound, gin.H{"error": "site not found"})
			return
		}
		docRoot := strings.TrimSpace(meta.DocumentRoot)
		if docRoot == "" {
			docRoot = base
		}
		if !hosting.DocrootIsPublicVariant(docRoot, base) {
			c.JSON(http.StatusOK, gin.H{"domain": domain, "ok": true, "changed": []string{}, "message": "document root is not public variant"})
			return
		}
		changed, err := hosting.NormalizePublicDocrootEnv(base, domain)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		c.JSON(http.StatusOK, gin.H{"domain": domain, "ok": true, "changed": changed})
	}
}

func handleLaravelStorageLink(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		domain := strings.ToLower(strings.TrimSpace(c.Param("domain")))
		if domain == "" || strings.Contains(domain, "..") || !nginx.DomainSafe(domain) {
			c.JSON(http.StatusBadRequest, gin.H{"error": "invalid domain"})
			return
		}
		base := detectDocumentRootBase(cfg.Paths.WebRoot, domain)
		if err := hosting.EnsureStoragePublicLink(base); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		c.JSON(http.StatusOK, gin.H{"domain": domain, "ok": true, "message": "storage link ensured"})
	}
}

func handleStackScan(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		domain := strings.ToLower(strings.TrimSpace(c.Param("domain")))
		if domain == "" || strings.Contains(domain, "..") || !nginx.DomainSafe(domain) {
			c.JSON(http.StatusBadRequest, gin.H{"error": "invalid domain"})
			return
		}
		base := detectDocumentRootBase(cfg.Paths.WebRoot, domain)
		meta, err := sites.ReadSiteMeta(cfg.Paths.WebRoot, domain)
		if err != nil || meta == nil {
			c.JSON(http.StatusNotFound, gin.H{"error": "site not found"})
			return
		}
		docRoot := strings.TrimSpace(meta.DocumentRoot)
		if docRoot == "" {
			docRoot = base
		}
		serverType := strings.TrimSpace(meta.ServerType)
		scan, err := hosting.ScanSiteStack(base, docRoot, serverType)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		c.JSON(http.StatusOK, gin.H{
			"domain":      domain,
			"site_base":   base,
			"scan":        scan,
			"app_profile": strings.TrimSpace(meta.AppProfile),
			"node_app":    meta.NodeApp,
			"web_root":    filepath.Clean(cfg.Paths.WebRoot),
		})
	}
}
