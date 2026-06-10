package api

import (
	"crypto/rand"
	"encoding/hex"
	"net/http"
	"strings"

	"github.com/gin-gonic/gin"
	"panelze/engine/internal/config"
	"panelze/engine/internal/hosting"
	"panelze/engine/internal/nginx"
	"panelze/engine/internal/sites"
)

func registerRedirectRoutes(cfg *config.Config, site *gin.RouterGroup) {
	site.GET("/:domain/redirects", handleRedirectsGet(cfg))
	site.PUT("/:domain/redirects", handleRedirectsPut(cfg))
}

func redirectDomainOK(c *gin.Context) (string, bool) {
	d := strings.ToLower(strings.TrimSpace(c.Param("domain")))
	if d == "" || strings.Contains(d, "..") || !nginx.DomainSafe(d) {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid domain"})
		return "", false
	}
	return d, true
}

func handleRedirectsGet(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		d, ok := redirectDomainOK(c)
		if !ok {
			return
		}
		meta, err := sites.ReadSiteMeta(cfg.Paths.WebRoot, d)
		if err != nil || meta == nil {
			c.JSON(http.StatusNotFound, gin.H{"error": "site not found"})
			return
		}
		rules := meta.RedirectRules
		if rules == nil {
			rules = []sites.RedirectRule{}
		}
		c.JSON(http.StatusOK, gin.H{
			"domain":      d,
			"rules":       rules,
			"server_type": strings.TrimSpace(meta.ServerType),
		})
	}
}

func handleRedirectsPut(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		d, ok := redirectDomainOK(c)
		if !ok {
			return
		}
		var req struct {
			Rules []sites.RedirectRule `json:"rules"`
		}
		if err := c.ShouldBindJSON(&req); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		for i := range req.Rules {
			if strings.TrimSpace(req.Rules[i].ID) == "" {
				req.Rules[i].ID = "r-" + randomRedirectID()
			}
		}
		norm, err := sites.NormalizeRedirectRules(req.Rules)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		meta, err := sites.ReadSiteMeta(cfg.Paths.WebRoot, d)
		if err != nil || meta == nil {
			c.JSON(http.StatusNotFound, gin.H{"error": "site not found"})
			return
		}
		st := strings.ToLower(strings.TrimSpace(meta.ServerType))
		if st == "" {
			st = "nginx"
		}
		if st != "nginx" {
			c.JSON(http.StatusUnprocessableEntity, gin.H{"error": "redirect rules supported only on nginx"})
			return
		}
		meta.RedirectRules = norm
		if err := sites.WriteSiteMeta(cfg.Paths.WebRoot, d, meta); err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
			return
		}
		docRoot := strings.TrimSpace(meta.DocumentRoot)
		if docRoot == "" {
			c.JSON(http.StatusBadRequest, gin.H{"error": "document_root missing"})
			return
		}
		if err := hosting.ApplyWebServer(cfg, d, docRoot, meta, ""); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		c.JSON(http.StatusOK, gin.H{"domain": d, "rules": norm, "ok": true})
	}
}

func randomRedirectID() string {
	b := make([]byte, 4)
	if _, err := rand.Read(b); err != nil {
		return "0000"
	}
	return hex.EncodeToString(b)
}
