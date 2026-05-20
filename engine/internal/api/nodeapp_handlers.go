package api

import (
	"net/http"
	"strings"

	"github.com/gin-gonic/gin"
	"hostvim/engine/internal/config"
	"hostvim/engine/internal/hosting"
	"hostvim/engine/internal/nodeapp"
	"hostvim/engine/internal/nginx"
	"hostvim/engine/internal/sites"
)

func registerNodeAppRoutes(cfg *config.Config, site *gin.RouterGroup) {
	site.GET("/:domain/node-app", handleNodeAppGet(cfg))
	site.POST("/:domain/node-app/detect", handleNodeAppDetect(cfg))
	site.PUT("/:domain/node-app", handleNodeAppUpdate(cfg))
	site.POST("/:domain/node-app/auto-configure", handleNodeAppAutoConfigure(cfg))
	site.POST("/:domain/node-app/start", handleNodeAppStart(cfg))
	site.POST("/:domain/node-app/stop", handleNodeAppStop(cfg))
	site.POST("/:domain/node-app/restart", handleNodeAppRestart(cfg))
	site.POST("/:domain/node-app/install", handleNodeAppInstall(cfg))
	site.POST("/:domain/node-app/build", handleNodeAppBuild(cfg))
}

func nodeAppDomainOK(c *gin.Context) (string, bool) {
	d := strings.ToLower(strings.TrimSpace(c.Param("domain")))
	if d == "" || strings.Contains(d, "..") || !nginx.DomainSafe(d) {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid domain"})
		return "", false
	}
	return d, true
}

func handleNodeAppGet(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		d, ok := nodeAppDomainOK(c)
		if !ok {
			return
		}
		view, err := nodeapp.GetConfig(cfg, d)
		if err != nil {
			c.JSON(http.StatusNotFound, gin.H{"error": err.Error()})
			return
		}
		c.JSON(http.StatusOK, view)
	}
}

func handleNodeAppDetect(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		d, ok := nodeAppDomainOK(c)
		if !ok {
			return
		}
		var req struct {
			WorkDir string `json:"work_dir"`
		}
		_ = c.ShouldBindJSON(&req)
		base := detectDocumentRootBase(cfg.Paths.WebRoot, d)
		det, err := nodeapp.Detect(base, req.WorkDir)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		meta, _ := sites.ReadSiteMeta(cfg.Paths.WebRoot, d)
		appProfile := ""
		if meta != nil {
			appProfile = strings.TrimSpace(meta.AppProfile)
		}
		c.JSON(http.StatusOK, gin.H{
			"detect":      det,
			"app_profile": appProfile,
		})
	}
}

func handleNodeAppUpdate(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		d, ok := nodeAppDomainOK(c)
		if !ok {
			return
		}
		var req struct {
			Enabled     *bool  `json:"enabled"`
			Profile     string `json:"profile"`
			AppProfile  string `json:"app_profile"`
			WorkDir     string `json:"work_dir"`
			StartScript string `json:"start_script"`
			ListenPort  int    `json:"listen_port"`
			AutoStart   *bool  `json:"auto_start"`
			EnvFile     string `json:"env_file"`
		}
		if err := c.ShouldBindJSON(&req); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		meta, err := sites.ReadSiteMeta(cfg.Paths.WebRoot, d)
		if err != nil || meta == nil {
			c.JSON(http.StatusNotFound, gin.H{"error": "site not found"})
			return
		}
		patch := &sites.NodeAppConfig{}
		if meta.NodeApp != nil {
			*patch = *meta.NodeApp
		}
		if req.Enabled != nil {
			patch.Enabled = *req.Enabled
		}
		if req.Profile != "" {
			patch.Profile = hosting.NormalizeAppProfile(req.Profile)
		}
		if req.WorkDir != "" {
			patch.WorkDir = req.WorkDir
		}
		if req.StartScript != "" {
			patch.StartScript = req.StartScript
		}
		if req.ListenPort > 0 {
			patch.ListenPort = req.ListenPort
		}
		if req.AutoStart != nil {
			patch.AutoStart = *req.AutoStart
		}
		if req.EnvFile != "" {
			patch.EnvFile = req.EnvFile
		}
		view, err := nodeapp.UpdateConfig(cfg, d, patch, req.AppProfile)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		c.JSON(http.StatusOK, view)
	}
}

func handleNodeAppAutoConfigure(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		d, ok := nodeAppDomainOK(c)
		if !ok {
			return
		}
		var req struct {
			AppProfile string `json:"app_profile"`
		}
		_ = c.ShouldBindJSON(&req)
		view, err := nodeapp.AutoConfigureFromDetect(cfg, d, req.AppProfile)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		c.JSON(http.StatusOK, view)
	}
}

func handleNodeAppStart(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		d, ok := nodeAppDomainOK(c)
		if !ok {
			return
		}
		out, err := nodeapp.Start(cfg, d)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error(), "output": out})
			return
		}
		c.JSON(http.StatusOK, gin.H{"message": "started", "output": out})
	}
}

func handleNodeAppStop(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		d, ok := nodeAppDomainOK(c)
		if !ok {
			return
		}
		out, err := nodeapp.Stop(cfg, d)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error(), "output": out})
			return
		}
		c.JSON(http.StatusOK, gin.H{"message": "stopped", "output": out})
	}
}

func handleNodeAppRestart(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		d, ok := nodeAppDomainOK(c)
		if !ok {
			return
		}
		out, err := nodeapp.Restart(cfg, d)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error(), "output": out})
			return
		}
		c.JSON(http.StatusOK, gin.H{"message": "restarted", "output": out})
	}
}

func handleNodeAppInstall(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		d, ok := nodeAppDomainOK(c)
		if !ok {
			return
		}
		var req struct {
			UseCI bool `json:"use_ci"`
		}
		_ = c.ShouldBindJSON(&req)
		out, err := nodeapp.NpmInstall(cfg, d, req.UseCI)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error(), "output": out})
			return
		}
		c.JSON(http.StatusOK, gin.H{"message": "ok", "output": out})
	}
}

func handleNodeAppBuild(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		d, ok := nodeAppDomainOK(c)
		if !ok {
			return
		}
		out, err := nodeapp.NpmBuild(cfg, d)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error(), "output": out})
			return
		}
		c.JSON(http.StatusOK, gin.H{"message": "ok", "output": out})
	}
}
