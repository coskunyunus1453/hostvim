package api

import (
	"net/http"
	"strings"
	"time"

	"github.com/gin-gonic/gin"
	"panelze/engine/internal/config"
	"panelze/engine/internal/hosting"
	"panelze/engine/internal/nodeapp"
	"panelze/engine/internal/nginx"
	"panelze/engine/internal/sites"
)

func handleNodeAppsReconcile(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		report, err := nodeapp.ReconcileAll(cfg)
		nodeapp.StoreWatchdogSnapshot(report, err)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error(), "report": report})
			return
		}
		c.JSON(http.StatusOK, report)
	}
}

func handleNodeAppsWatchdogStatus(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		snap := nodeapp.LastWatchdogSnapshot()
		if snap.Report == nil && snap.Error == "" {
			c.JSON(http.StatusOK, gin.H{
				"checked": 0,
				"started": 0,
				"restarted": 0,
				"failed": 0,
				"items": []any{},
				"stale": true,
			})
			return
		}
		if snap.Error != "" && snap.Report == nil {
			c.JSON(http.StatusOK, gin.H{
				"error": snap.Error,
				"at": snap.At.UTC().Format(time.RFC3339),
				"failed": 0,
				"items": []any{},
			})
			return
		}
		out := gin.H{
			"at": snap.At.UTC().Format(time.RFC3339),
			"checked": snap.Report.Checked,
			"started": snap.Report.Started,
			"restarted": snap.Report.Restarted,
			"already_healthy": snap.Report.AlreadyHealthy,
			"failed": snap.Report.Failed,
			"pm2_resurrected": snap.Report.Pm2Resurrected,
			"items": snap.Report.Items,
		}
		if snap.Error != "" {
			out["error"] = snap.Error
		}
		c.JSON(http.StatusOK, out)
	}
}

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
	site.POST("/:domain/node-app/heal", handleNodeAppHeal(cfg))
}

func nodeAppDomainOK(c *gin.Context) (string, bool) {
	d := strings.ToLower(strings.TrimSpace(c.Param("domain")))
	if d == "" || strings.Contains(d, "..") || !nginx.DomainSafe(d) {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid domain"})
		return "", false
	}
	return d, true
}

func nodeAppPathSegment(c *gin.Context) string {
	if seg := strings.TrimSpace(c.Query("path_segment")); seg != "" {
		return seg
	}
	return ""
}

func handleNodeAppGet(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		d, ok := nodeAppDomainOK(c)
		if !ok {
			return
		}
		view, err := nodeapp.GetConfig(cfg, d, nodeAppPathSegment(c))
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
			WorkDir     string `json:"work_dir"`
			PathSegment string `json:"path_segment"`
		}
		_ = c.ShouldBindJSON(&req)
		seg := strings.TrimSpace(req.PathSegment)
		if seg == "" {
			seg = nodeAppPathSegment(c)
		}
		sc := nodeapp.ScopeFrom(d, seg)
		base := sc.SiteBase(cfg.Paths.WebRoot)
		det, err := nodeapp.Detect(base, req.WorkDir)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		meta, _ := sc.ReadMeta(cfg.Paths.WebRoot)
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
			PathSegment string `json:"path_segment"`
		}
		if err := c.ShouldBindJSON(&req); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		seg := strings.TrimSpace(req.PathSegment)
		if seg == "" {
			seg = nodeAppPathSegment(c)
		}
		sc := nodeapp.ScopeFrom(d, seg)
		meta, err := sc.ReadMeta(cfg.Paths.WebRoot)
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
		view, err := nodeapp.UpdateConfig(cfg, d, seg, patch, req.AppProfile)
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
			AppProfile  string `json:"app_profile"`
			PathSegment string `json:"path_segment"`
		}
		_ = c.ShouldBindJSON(&req)
		seg := strings.TrimSpace(req.PathSegment)
		if seg == "" {
			seg = nodeAppPathSegment(c)
		}
		view, err := nodeapp.AutoConfigureFromDetect(cfg, d, seg, req.AppProfile)
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
		var req struct {
			PathSegment string `json:"path_segment"`
		}
		_ = c.ShouldBindJSON(&req)
		seg := strings.TrimSpace(req.PathSegment)
		if seg == "" {
			seg = nodeAppPathSegment(c)
		}
		out, err := nodeapp.StartWithPrep(cfg, d, seg)
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
		var req struct {
			PathSegment string `json:"path_segment"`
		}
		_ = c.ShouldBindJSON(&req)
		seg := strings.TrimSpace(req.PathSegment)
		if seg == "" {
			seg = nodeAppPathSegment(c)
		}
		out, err := nodeapp.Stop(cfg, d, seg)
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
		var req struct {
			PathSegment string `json:"path_segment"`
		}
		_ = c.ShouldBindJSON(&req)
		seg := strings.TrimSpace(req.PathSegment)
		if seg == "" {
			seg = nodeAppPathSegment(c)
		}
		out, err := nodeapp.Restart(cfg, d, seg)
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
			UseCI       bool   `json:"use_ci"`
			PathSegment string `json:"path_segment"`
		}
		_ = c.ShouldBindJSON(&req)
		seg := strings.TrimSpace(req.PathSegment)
		if seg == "" {
			seg = nodeAppPathSegment(c)
		}
		out, err := nodeapp.NpmInstall(cfg, d, seg, req.UseCI)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error(), "output": out})
			return
		}
		c.JSON(http.StatusOK, gin.H{"message": "ok", "output": out})
	}
}

func handleNodeAppHeal(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		d, ok := nodeAppDomainOK(c)
		if !ok {
			return
		}
		var req struct {
			PathSegment string `json:"path_segment"`
		}
		_ = c.ShouldBindJSON(&req)
		seg := strings.TrimSpace(req.PathSegment)
		if seg == "" {
			seg = nodeAppPathSegment(c)
		}
		res, err := nodeapp.Heal(cfg, d, seg)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error(), "result": res})
			return
		}
		view, _ := nodeapp.GetConfig(cfg, d, seg)
		c.JSON(http.StatusOK, gin.H{
			"message": res.Message,
			"healthy": res.Healthy,
			"steps":   res.Steps,
			"config":  view,
		})
	}
}

func handleNodeAppBuild(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		d, ok := nodeAppDomainOK(c)
		if !ok {
			return
		}
		var req struct {
			PathSegment string `json:"path_segment"`
		}
		_ = c.ShouldBindJSON(&req)
		seg := strings.TrimSpace(req.PathSegment)
		if seg == "" {
			seg = nodeAppPathSegment(c)
		}
		out, err := nodeapp.NpmBuild(cfg, d, seg)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error(), "output": out})
			return
		}
		c.JSON(http.StatusOK, gin.H{"message": "ok", "output": out})
	}
}
