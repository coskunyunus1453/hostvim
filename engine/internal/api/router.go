package api

import (
	"errors"
	"fmt"
	"io"
	"net/http"
	"os"
	"os/exec"
	"path/filepath"
	"strconv"
	"strings"
	"syscall"

	"github.com/gin-gonic/gin"
	"github.com/sirupsen/logrus"
	"panelze/engine/internal/config"
	"panelze/engine/internal/daemon"
	"panelze/engine/internal/files"
	"panelze/engine/internal/hosting"
	"panelze/engine/internal/metrics"
	"panelze/engine/internal/middleware"
	"panelze/engine/internal/nginx"
	"panelze/engine/internal/nodeapp"
	"panelze/engine/internal/phpfpm"
	"panelze/engine/internal/sitecage"
	"panelze/engine/internal/sites"
	"panelze/engine/internal/ssl"
	"panelze/engine/internal/terminal"
)

func NewRouter(cfg *config.Config, d *daemon.Daemon, log *logrus.Logger) *gin.Engine {
	files.ConfigureUnzipLimits(
		cfg.Files.MaxUnzipEntries,
		cfg.Files.MaxUnzipUncompressedBytes,
		cfg.Files.MaxUnzipEntryRatio,
		cfg.Files.MaxUnzipArchiveRatio,
	)

	if !cfg.Server.Debug {
		gin.SetMode(gin.ReleaseMode)
	}

	r := gin.New()
	// Büyük dosya yöneticisi yüklemeleri (varsayılan Gin ~32MB bellek eşiği)
	r.MaxMultipartMemory = 128 << 20
	r.Use(gin.Recovery())
	r.Use(middleware.Logger(log))
	r.Use(middleware.CORS(cfg))

	r.GET("/health", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{
			"status":  "healthy",
			"engine":  "panelze",
			"version": "0.1.0",
			"running": d.IsRunning(),
		})
	})

	promPath := strings.TrimSpace(cfg.Server.PrometheusPath)
	if promPath == "" {
		promPath = "/metrics"
	}
	if cfg.Server.PrometheusEnabled {
		metrics.Init()
		r.GET(promPath, metrics.Handler())
	}

	r.GET("/ws/terminal", terminal.HandleWS(cfg, log))

	api := r.Group("/api/v1")
	api.Use(middleware.AuthRequired(cfg))
	{
		svc := api.Group("/services")
		{
			svc.GET("", handleListServices(d))
			svc.GET("/:name", handleGetService(d))
			svc.POST("/:name/start", handleStartService(d))
			svc.POST("/:name/stop", handleStopService(d))
			svc.POST("/:name/restart", handleRestartService(d))
		}

		site := api.Group("/sites")
		{
			site.POST("", handleCreateSite(cfg, d))
			site.POST("/rename", handleRenameSite(cfg))
			site.POST("/:domain/suspend", handleSuspendSite(cfg, d))
			site.POST("/:domain/activate", handleActivateSite(cfg, d))
			site.POST("/:domain/reapply-webserver", handleReapplyWebServer(cfg))
			site.POST("/:domain/document-root", handleSetDocumentRoot(cfg, d))
			site.DELETE("/:domain", handleDeleteSite(cfg, d))
			site.GET("", handleListSites(cfg, d))
			site.GET("/:domain/logs", handleSiteLogs(cfg, d))
			site.POST("/:domain/subdomains", handleAddSubdomain(cfg, d))
			site.DELETE("/:domain/subdomains", handleRemoveSubdomain(cfg, d))
			site.POST("/:domain/subdomains/:segment/sync-web", handleSyncSubdomainWeb(cfg))
			site.POST("/:domain/aliases", handleAddSiteAlias(cfg, d))
			site.DELETE("/:domain/aliases", handleRemoveSiteAlias(cfg, d))
			registerNodeAppRoutes(cfg, site)
			api.GET("/node-apps/watchdog-status", handleNodeAppsWatchdogStatus(cfg))
			api.POST("/node-apps/reconcile", handleNodeAppsReconcile(cfg))
			registerStackScanRoutes(cfg, site)
			registerRedirectRoutes(cfg, site)
		}

		ssl := api.Group("/ssl")
		{
			ssl.POST("/issue", handleIssueSSL(cfg))
			ssl.POST("/renew", handleRenewSSL(cfg))
			ssl.POST("/revoke", handleRevokeSSL(cfg))
			ssl.POST("/manual", handleManualSSL(cfg))
		}

		registerModuleRoutes(cfg, d, api, log)
	}

	return r
}

func handleListServices(d *daemon.Daemon) gin.HandlerFunc {
	return func(c *gin.Context) {
		services := d.ServiceManager().GetAllServices()
		c.JSON(http.StatusOK, gin.H{"services": services})
	}
}

func handleGetService(d *daemon.Daemon) gin.HandlerFunc {
	return func(c *gin.Context) {
		name := c.Param("name")
		svc, err := d.ServiceManager().GetService(name)
		if err != nil {
			c.JSON(http.StatusNotFound, gin.H{"error": err.Error()})
			return
		}
		c.JSON(http.StatusOK, svc)
	}
}

func handleStartService(d *daemon.Daemon) gin.HandlerFunc {
	return func(c *gin.Context) {
		name := c.Param("name")
		if err := d.ServiceManager().StartService(name); err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
			return
		}
		c.JSON(http.StatusOK, gin.H{"message": "Service started"})
	}
}

func handleStopService(d *daemon.Daemon) gin.HandlerFunc {
	return func(c *gin.Context) {
		name := c.Param("name")
		if err := d.ServiceManager().StopService(name); err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
			return
		}
		c.JSON(http.StatusOK, gin.H{"message": "Service stopped"})
	}
}

func handleRestartService(d *daemon.Daemon) gin.HandlerFunc {
	return func(c *gin.Context) {
		name := c.Param("name")
		if err := d.ServiceManager().RestartService(name); err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
			return
		}
		c.JSON(http.StatusOK, gin.H{"message": "Service restarted"})
	}
}

// rollbackFailedSiteProvision yeni site oluşturma başarısız olunca eski siteyi geri yükler; yalnızca ilk kurulumda dizini siler.
func rollbackFailedSiteProvision(cfg *config.Config, domain, phpV string, oldMeta *sites.SiteMeta, ps phpfpm.HostingPoolSettings, poolPrev []byte, poolHadPrev bool, oldPoolBak []byte, oldPoolBakHad bool) {
	if cfg.Hosting.PHPFPMmanagePools {
		_ = phpfpm.RestorePoolConf(ps, domain, phpV, poolPrev, poolHadPrev)
		if cfg.Hosting.PHPFPMreloadAfterPool {
			_ = phpfpm.Reload(phpV)
		}
		if oldPoolBakHad && oldMeta != nil {
			_ = phpfpm.RestorePoolConf(ps, domain, oldMeta.PHPVersion, oldPoolBak, true)
			if cfg.Hosting.PHPFPMreloadAfterPool {
				_ = phpfpm.Reload(oldMeta.PHPVersion)
			}
		}
	}
	if oldMeta == nil {
		_ = sites.Remove(cfg.Paths.WebRoot, domain)
		return
	}
	restored := *oldMeta
	if strings.TrimSpace(restored.PHPVersion) == "" {
		restored.PHPVersion = phpV
	}
	_ = sites.WriteSiteMeta(cfg.Paths.WebRoot, domain, &restored)
	docRoot := strings.TrimSpace(restored.DocumentRoot)
	if docRoot == "" {
		docRoot = filepath.Join(cfg.Paths.WebRoot, domain, "public_html")
	}
	phpSocket := nginx.EffectivePHPSocket(restored.PHPVersion, cfg.Hosting.PHPFPMsocket)
	if cfg.Hosting.PHPFPMmanagePools {
		phpSocket = ps.SocketForDomain(domain)
	}
	_ = hosting.ApplyWebServer(cfg, domain, docRoot, &restored, phpSocket)
}

func handleCreateSite(cfg *config.Config, d *daemon.Daemon) gin.HandlerFunc {
	return func(c *gin.Context) {
		var req struct {
			Domain     string `json:"domain" binding:"required"`
			UserID     uint   `json:"user_id" binding:"required"`
			PHP        string `json:"php_version"`
			ServerType string `json:"server_type"`
			// Paket bazlı PanelKafes kaynak limiti (0 = global varsayılan).
			CPULimit      int `json:"cpu_limit"`
			MemoryLimitMB int `json:"memory_limit_mb"`
		}
		if err := c.ShouldBindJSON(&req); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		phpV := strings.TrimSpace(req.PHP)
		if phpV == "" {
			phpV = "8.2"
		}

		oldMeta, _ := sites.ReadSiteMeta(cfg.Paths.WebRoot, req.Domain)
		docRoot, err := sites.Provision(cfg.Paths.WebRoot, req.Domain, phpV, req.ServerType)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
			return
		}

		cageCfg := sitecage.FromHosting(cfg)
		if cageCfg.Enabled {
			cageUser, cerr := sitecage.Ensure(cageCfg, cfg.Paths.WebRoot, req.Domain, req.CPULimit, req.MemoryLimitMB)
			if cerr != nil {
				_ = sites.Remove(cfg.Paths.WebRoot, req.Domain)
				c.JSON(http.StatusInternalServerError, gin.H{"error": "panelkafes: " + cerr.Error()})
				return
			}
			metaCage, _ := sites.ReadSiteMeta(cfg.Paths.WebRoot, req.Domain)
			if metaCage == nil {
				metaCage = &sites.SiteMeta{PHPVersion: phpV, DocumentRoot: docRoot}
			}
			metaCage.CageEnabled = true
			metaCage.CageUser = cageUser
			metaCage.CPUPercent = req.CPULimit
			metaCage.MemoryMB = req.MemoryLimitMB
			_ = sites.WriteSiteMeta(cfg.Paths.WebRoot, req.Domain, metaCage)
		}

		ps := phpfpmSettings(cfg)
		phpSocket := nginx.EffectivePHPSocket(phpV, cfg.Hosting.PHPFPMsocket)
		var poolPrev []byte
		var poolHadPrev bool
		var oldPoolBak []byte
		var oldPoolBakHad bool
		metaForPool, _ := sites.ReadSiteMeta(cfg.Paths.WebRoot, req.Domain)
		poolOpts := sitecage.PoolOptions(cfg, metaForPool, req.Domain, docRoot)
		if sitecage.ManagePools(cfg) {
			if oldMeta != nil && phpfpm.NormalizeVersion(oldMeta.PHPVersion) != phpfpm.NormalizeVersion(phpV) {
				oldPoolBak, oldPoolBakHad = phpfpm.ReadPoolSnapshot(ps, req.Domain, oldMeta.PHPVersion)
			}
			sock, pprev, phad, perr := phpfpm.WritePool(ps, req.Domain, phpV, docRoot, poolOpts)
			poolPrev, poolHadPrev = pprev, phad
			if perr != nil {
				rollbackFailedSiteProvision(cfg, req.Domain, phpV, oldMeta, ps, poolPrev, poolHadPrev, oldPoolBak, oldPoolBakHad)
				c.JSON(http.StatusInternalServerError, gin.H{"error": perr.Error()})
				return
			}
			phpSocket = sock
			if cfg.Hosting.PHPFPMreloadAfterPool {
				if rerr := phpfpm.Reload(phpV); rerr != nil {
					rollbackFailedSiteProvision(cfg, req.Domain, phpV, oldMeta, ps, poolPrev, poolHadPrev, oldPoolBak, oldPoolBakHad)
					c.JSON(http.StatusInternalServerError, gin.H{"error": rerr.Error()})
					return
				}
			}
			if oldMeta != nil && phpfpm.NormalizeVersion(oldMeta.PHPVersion) != phpfpm.NormalizeVersion(phpV) {
				_ = phpfpm.RemovePool(ps, req.Domain, oldMeta.PHPVersion)
				if cfg.Hosting.PHPFPMreloadAfterPool {
					_ = phpfpm.Reload(oldMeta.PHPVersion)
				}
			}
		}

		metaNow, _ := sites.ReadSiteMeta(cfg.Paths.WebRoot, req.Domain)
		if err := hosting.ApplyWebServer(cfg, req.Domain, docRoot, metaNow, phpSocket); err != nil {
			rollbackFailedSiteProvision(cfg, req.Domain, phpV, oldMeta, ps, poolPrev, poolHadPrev, oldPoolBak, oldPoolBakHad)
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
			return
		}

		// Yeni site PanelKafes açıkken otomatik kendi FPM servisi + kaynak slice'ine alınır.
		if cageCfg.Enabled && sitecage.ManagePools(cfg) {
			_ = sitecage.ApplyService(cageCfg, req.Domain, phpV, req.CPULimit, req.MemoryLimitMB)
		}

		st := "nginx"
		if metaNow != nil && metaNow.ServerType != "" {
			st = sites.NormalizeServerType(metaNow.ServerType)
		}

		cageUserOut := ""
		if metaNow != nil {
			cageUserOut = metaNow.CageUser
		}

		c.JSON(http.StatusCreated, gin.H{
			"message":              "Site created",
			"domain":               req.Domain,
			"document_root":        docRoot,
			"web_root":             cfg.Paths.WebRoot,
			"server_type":          st,
			"nginx_vhost":          cfg.Hosting.NginxManageVhosts,
			"apache_vhost":         cfg.Hosting.ApacheManageVhosts,
			"openlitespeed_vhost":  cfg.Hosting.OLSManageVhosts,
			"php_fpm_manage_pools": sitecage.ManagePools(cfg),
			"site_cage_enabled":    cfg.Hosting.SiteCageEnabled,
			"cage_user":            cageUserOut,
			"php_fpm_socket":       phpSocket,
		})
	}
}

// handleReapplyWebServer mevcut site dosyalarını koruyarak yalnızca meta + vhost günceller (PHP / web sunucu geçişi).
func handleReapplyWebServer(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		domain := strings.ToLower(strings.TrimSpace(c.Param("domain")))
		if domain == "" || strings.Contains(domain, "..") || !nginx.DomainSafe(domain) {
			c.JSON(http.StatusBadRequest, gin.H{"error": "invalid domain"})
			return
		}
		var req struct {
			ServerType string `json:"server_type"`
			PHPVersion string `json:"php_version"`
		}
		_ = c.ShouldBindJSON(&req)

		siteRoot := filepath.Join(cfg.Paths.WebRoot, domain)
		if _, err := os.Stat(siteRoot); err != nil {
			c.JSON(http.StatusNotFound, gin.H{
				"error":             "domain root not found",
				"needs_reprovision": true,
			})
			return
		}

		oldMeta, _ := sites.ReadSiteMeta(cfg.Paths.WebRoot, domain)
		phpV := strings.TrimSpace(req.PHPVersion)
		if phpV == "" && oldMeta != nil {
			phpV = strings.TrimSpace(oldMeta.PHPVersion)
		}
		if phpV == "" {
			phpV = "8.2"
		}
		st := strings.TrimSpace(req.ServerType)
		if st == "" && oldMeta != nil {
			st = strings.TrimSpace(oldMeta.ServerType)
		}
		if st == "" {
			st = "nginx"
		}
		st = sites.NormalizeServerType(st)

		docRoot, err := sites.Provision(cfg.Paths.WebRoot, domain, phpV, st)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
			return
		}

		ps := phpfpmSettings(cfg)
		phpSocket := nginx.EffectivePHPSocket(phpV, cfg.Hosting.PHPFPMsocket)
		if sitecage.ManagePools(cfg) {
			poolOpts := sitecage.PoolOptions(cfg, oldMeta, domain, docRoot)
			sock, _, _, perr := phpfpm.WritePool(ps, domain, phpV, docRoot, poolOpts)
			if perr != nil {
				c.JSON(http.StatusInternalServerError, gin.H{"error": perr.Error()})
				return
			}
			phpSocket = sock
			if cfg.Hosting.PHPFPMreloadAfterPool {
				_ = phpfpm.Reload(phpV)
			}
		}

		metaNow, _ := sites.ReadSiteMeta(cfg.Paths.WebRoot, domain)
		if err := hosting.ApplyWebServer(cfg, domain, docRoot, metaNow, phpSocket); err != nil {
			if oldMeta != nil {
				_ = sites.WriteSiteMeta(cfg.Paths.WebRoot, domain, oldMeta)
				docRootOld := strings.TrimSpace(oldMeta.DocumentRoot)
				if docRootOld == "" {
					docRootOld = filepath.Join(cfg.Paths.WebRoot, domain, "public_html")
				}
				sockOld := nginx.EffectivePHPSocket(oldMeta.PHPVersion, cfg.Hosting.PHPFPMsocket)
				if cfg.Hosting.PHPFPMmanagePools {
					sockOld = ps.SocketForDomain(domain)
				}
				_ = hosting.ApplyWebServer(cfg, domain, docRootOld, oldMeta, sockOld)
			}
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
			return
		}

		c.JSON(http.StatusOK, gin.H{
			"ok":            true,
			"domain":        domain,
			"document_root": docRoot,
			"server_type":   st,
			"message":       "web server configuration reapplied",
		})
	}
}

func phpfpmSettings(cfg *config.Config) phpfpm.HostingPoolSettings {
	c := sitecage.FromHosting(cfg)
	ps := phpfpm.HostingPoolSettings{
		PoolDirTemplate: cfg.Hosting.PHPFPMpoolDirTemplate,
		SocketListenDir: cfg.Hosting.PHPFPMlistenDir,
		FPMUser:         cfg.Hosting.PHPFPMpoolUser,
		FPMGroup:        cfg.Hosting.PHPFPMpoolGroup,
	}
	if c.Enabled {
		if strings.TrimSpace(ps.FPMGroup) == "" || ps.FPMGroup == "www-data" {
			ps.FPMGroup = c.Group
		}
		ps.ListenOwner = c.EngineUser
		ps.ListenGroup = c.EngineUser
		ps.Helper = c.Helper
	}
	return ps
}

func handleRenameSite(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		var req struct {
			From string `json:"from" binding:"required"`
			To   string `json:"to" binding:"required"`
		}
		if err := c.ShouldBindJSON(&req); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		from := strings.ToLower(strings.TrimSpace(req.From))
		to := strings.ToLower(strings.TrimSpace(req.To))
		if from == "" || to == "" || from == to || strings.Contains(from, "..") || strings.Contains(to, "..") ||
			!nginx.DomainSafe(from) || !nginx.DomainSafe(to) {
			c.JSON(http.StatusBadRequest, gin.H{"error": "invalid domain"})
			return
		}
		if err := hosting.RenamePrimarySite(cfg, from, to); err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
			return
		}
		c.JSON(http.StatusOK, gin.H{"message": "site renamed", "from": from, "to": to})
	}
}

func handleSuspendSite(cfg *config.Config, d *daemon.Daemon) gin.HandlerFunc {
	return func(c *gin.Context) {
		domain := c.Param("domain")
		meta, err := sites.ReadSiteMeta(cfg.Paths.WebRoot, domain)
		if err != nil || meta == nil {
			c.JSON(http.StatusNotFound, gin.H{"error": "site not found"})
			return
		}
		if err := hosting.RemoveWebServer(cfg, domain, meta); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		c.JSON(http.StatusOK, gin.H{"message": "site suspended", "domain": domain})
	}
}

func handleActivateSite(cfg *config.Config, d *daemon.Daemon) gin.HandlerFunc {
	return func(c *gin.Context) {
		domain := c.Param("domain")
		meta, err := sites.ReadSiteMeta(cfg.Paths.WebRoot, domain)
		if err != nil || meta == nil {
			c.JSON(http.StatusNotFound, gin.H{"error": "site not found"})
			return
		}
		ps := phpfpmSettings(cfg)
		phpSocket := ""
		if cfg.Hosting.PHPFPMmanagePools {
			phpSocket = ps.SocketForDomain(domain)
		} else {
			phpSocket = nginx.EffectivePHPSocket(meta.PHPVersion, cfg.Hosting.PHPFPMsocket)
		}
		if err := hosting.ApplyWebServer(cfg, domain, meta.DocumentRoot, meta, phpSocket); err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
			return
		}
		c.JSON(http.StatusOK, gin.H{"message": "site activated", "domain": domain})
	}
}

func handleSetDocumentRoot(cfg *config.Config, d *daemon.Daemon) gin.HandlerFunc {
	return func(c *gin.Context) {
		domain := strings.ToLower(strings.TrimSpace(c.Param("domain")))
		if domain == "" || strings.Contains(domain, "..") || !nginx.DomainSafe(domain) {
			c.JSON(http.StatusBadRequest, gin.H{"error": "invalid domain"})
			return
		}
		var req struct {
			Variant string `json:"variant"`
			Profile string `json:"profile"`
			CustomPath string `json:"custom_path"`
		}
		if err := c.ShouldBindJSON(&req); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		meta, err := sites.ReadSiteMeta(cfg.Paths.WebRoot, domain)
		if err != nil || meta == nil {
			c.JSON(http.StatusNotFound, gin.H{"error": "site not found"})
			return
		}
		base := detectDocumentRootBase(cfg.Paths.WebRoot, domain)
		variant := strings.ToLower(strings.TrimSpace(req.Variant))
		target := ""
		customRel := strings.TrimSpace(req.CustomPath)
		if customRel != "" {
			if filepath.IsAbs(customRel) {
				c.JSON(http.StatusBadRequest, gin.H{"error": "custom_path must be relative to site base"})
				return
			}
			cleanRel := filepath.Clean(customRel)
			if cleanRel == "." || cleanRel == "" || cleanRel == ".." || strings.HasPrefix(cleanRel, "../") {
				c.JSON(http.StatusBadRequest, gin.H{"error": "invalid custom_path"})
				return
			}
			target = filepath.Join(base, cleanRel)
			variant = "custom"
		} else {
			if variant == "" {
				variant = hosting.DocrootVariantForProfile(req.Profile)
			}
			if variant != "root" && variant != "public" {
				c.JSON(http.StatusBadRequest, gin.H{"error": "invalid variant"})
				return
			}
			target = base
			if variant == "public" {
				target = filepath.Join(base, "public")
			}
		}
		// Safety: only allow under base.
		if !strings.HasPrefix(target+string(os.PathSeparator), base+string(os.PathSeparator)) && target != base {
			c.JSON(http.StatusBadRequest, gin.H{"error": "target escapes base"})
			return
		}

		// Ensure directory exists and is a directory.
		if st, statErr := os.Stat(target); statErr != nil {
			if os.IsNotExist(statErr) && (variant == "public" || variant == "root") {
				if mk := os.MkdirAll(target, 0o755); mk != nil {
					c.JSON(http.StatusBadRequest, gin.H{"error": mk.Error()})
					return
				}
			} else {
				c.JSON(http.StatusBadRequest, gin.H{"error": statErr.Error()})
				return
			}
		} else if !st.IsDir() {
			c.JSON(http.StatusBadRequest, gin.H{"error": "target is not a directory"})
			return
		}

		// Update PHP-FPM pool docroot if managed.
		ps := phpfpmSettings(cfg)
		phpV := strings.TrimSpace(meta.PHPVersion)
		if phpV == "" {
			phpV = "8.2"
		}
		var poolPrev []byte
		var poolHadPrev bool
		phpSocket := ""
		if sitecage.ManagePools(cfg) {
			poolOpts := sitecage.PoolOptions(cfg, meta, domain, target)
			sock, prev, had, perr := phpfpm.WritePool(ps, domain, phpV, target, poolOpts)
			poolPrev, poolHadPrev = prev, had
			if perr != nil {
				c.JSON(http.StatusInternalServerError, gin.H{"error": perr.Error()})
				return
			}
			phpSocket = sock
			if cfg.Hosting.PHPFPMreloadAfterPool {
				if rerr := phpfpm.Reload(phpV); rerr != nil {
					_ = phpfpm.RestorePoolConf(ps, domain, phpV, poolPrev, poolHadPrev)
					_ = phpfpm.Reload(phpV)
					c.JSON(http.StatusInternalServerError, gin.H{"error": rerr.Error()})
					return
				}
			}
		} else {
			phpSocket = nginx.EffectivePHPSocket(phpV, cfg.Hosting.PHPFPMsocket)
		}

		oldDoc := meta.DocumentRoot
		meta.DocumentRoot = target
		if req.Profile != "" {
			meta.AppProfile = hosting.NormalizeAppProfile(req.Profile)
		}
		if err := hosting.ApplyWebServer(cfg, domain, meta.DocumentRoot, meta, phpSocket); err != nil {
			// rollback pool if needed
			if cfg.Hosting.PHPFPMmanagePools {
				_ = phpfpm.RestorePoolConf(ps, domain, phpV, poolPrev, poolHadPrev)
				if cfg.Hosting.PHPFPMreloadAfterPool {
					_ = phpfpm.Reload(phpV)
				}
			}
			meta.DocumentRoot = oldDoc
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		if err := sites.WriteSiteMeta(cfg.Paths.WebRoot, domain, meta); err != nil {
			meta.DocumentRoot = oldDoc
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
			return
		}

		envChanged := []string{}
		if variant == "public" || hosting.DocrootIsPublicVariant(meta.DocumentRoot, base) {
			if keys, nerr := hosting.NormalizePublicDocrootEnv(base, domain); nerr == nil {
				envChanged = keys
			}
			_ = hosting.EnsureStoragePublicLink(base)
		}

		c.JSON(http.StatusOK, gin.H{
			"domain":        domain,
			"variant":       variant,
			"document_root": meta.DocumentRoot,
			"env_changed":   envChanged,
		})
	}
}

func detectDocumentRootBase(webRoot, domain string) string {
	// Global kural: document-root varyantları daima <domain>/public_html tabanı üzerinden hesaplanır.
	return filepath.Clean(filepath.Join(webRoot, domain, "public_html"))
}

func handleDeleteSite(cfg *config.Config, d *daemon.Daemon) gin.HandlerFunc {
	return func(c *gin.Context) {
		domain := c.Param("domain")
		meta, _ := sites.ReadSiteMeta(cfg.Paths.WebRoot, domain)
		ps := phpfpmSettings(cfg)

		nodeapp.RemoveSite(cfg, domain, "")
		_ = ssl.Delete(cfg, domain)
		hosting.RemoveWebServerForSiteDeletion(cfg, domain)
		if sitecage.FromHosting(cfg).Enabled {
			_ = sitecage.Remove(sitecage.FromHosting(cfg), domain)
		}
		if sitecage.ManagePools(cfg) {
			if meta != nil {
				_ = phpfpm.RemovePool(ps, domain, meta.PHPVersion)
				if cfg.Hosting.PHPFPMreloadAfterPool {
					_ = phpfpm.Reload(meta.PHPVersion)
				}
			} else {
				for _, ver := range phpfpm.RemovePoolBestEffortAllVersions(ps, domain) {
					if cfg.Hosting.PHPFPMreloadAfterPool {
						_ = phpfpm.Reload(ver)
					}
				}
			}
		}
		if err := sites.Remove(cfg.Paths.WebRoot, domain); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		c.JSON(http.StatusOK, gin.H{"message": "Site deleted", "domain": domain})
	}
}

func handleListSites(cfg *config.Config, d *daemon.Daemon) gin.HandlerFunc {
	return func(c *gin.Context) {
		names, err := sites.ListDomains(cfg.Paths.WebRoot)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
			return
		}
		list := make([]gin.H, 0, len(names))
		for _, n := range names {
			list = append(list, gin.H{"domain": n})
		}
		c.JSON(http.StatusOK, gin.H{"sites": list})
	}
}

func handleSiteLogs(cfg *config.Config, d *daemon.Daemon) gin.HandlerFunc {
	return func(c *gin.Context) {
		domain := strings.ToLower(strings.TrimSpace(c.Param("domain")))
		if domain == "" || strings.Contains(domain, "..") || !nginx.DomainSafe(domain) {
			c.JSON(http.StatusBadRequest, gin.H{"error": "invalid domain"})
			return
		}
		limit := 200
		if q := strings.TrimSpace(c.Query("lines")); q != "" {
			if qn, err := strconv.Atoi(q); err == nil {
				if qn < 20 {
					qn = 20
				}
				if qn > 1000 {
					qn = 1000
				}
				limit = qn
			}
		}

		nginxAccess := filepath.Join(cfg.Paths.LogDir, domain+"_access.log")
		nginxError := filepath.Join(cfg.Paths.LogDir, domain+"_error.log")
		apacheAccess := filepath.Join("/var/log/apache2", "panelze-"+domain+"-access.log")
		apacheError := filepath.Join("/var/log/apache2", "panelze-"+domain+"-error.log")

		entries := []gin.H{}
		for _, item := range []struct {
			name string
			path string
		}{
			{name: "nginx_access", path: nginxAccess},
			{name: "nginx_error", path: nginxError},
			{name: "apache_access", path: apacheAccess},
			{name: "apache_error", path: apacheError},
		} {
			content, exists, err := tailFile(item.path, limit, 256*1024)
			entries = append(entries, gin.H{
				"type":    item.name,
				"path":    item.path,
				"exists":  exists,
				"content": content,
				"error":   err,
			})
		}

		c.JSON(http.StatusOK, gin.H{
			"domain": domain,
			"logs":   entries,
		})
	}
}

func tailFile(path string, lines int, maxBytes int64) (string, bool, string) {
	st, err := os.Stat(path)
	if err != nil {
		if os.IsNotExist(err) {
			return "", false, ""
		}
		if isPermissionDenied(err) {
			content, eerr := tailFileElevated(path, lines)
			if eerr == nil {
				return content, true, ""
			}
			if errors.Is(eerr, os.ErrNotExist) {
				return "", false, ""
			}
			return "", false, eerr.Error()
		}
		return "", false, err.Error()
	}
	if st.IsDir() {
		return "", false, "is a directory"
	}
	f, err := os.Open(path)
	if err != nil {
		if isPermissionDenied(err) {
			content, eerr := tailFileElevated(path, lines)
			if eerr == nil {
				return content, true, ""
			}
			if errors.Is(eerr, os.ErrNotExist) {
				return "", false, ""
			}
			return "", true, eerr.Error()
		}
		return "", true, err.Error()
	}
	defer f.Close()

	size := st.Size()
	start := int64(0)
	if size > maxBytes {
		start = size - maxBytes
	}
	if _, err := f.Seek(start, 0); err != nil {
		return "", true, err.Error()
	}
	b, err := io.ReadAll(f)
	if err != nil {
		return "", true, err.Error()
	}
	s := string(b)
	if start > 0 {
		if i := strings.IndexByte(s, '\n'); i >= 0 && i+1 < len(s) {
			s = s[i+1:]
		}
	}
	all := strings.Split(strings.ReplaceAll(s, "\r\n", "\n"), "\n")
	for len(all) > 0 && strings.TrimSpace(all[len(all)-1]) == "" {
		all = all[:len(all)-1]
	}
	if len(all) > lines {
		all = all[len(all)-lines:]
	}
	return strings.Join(all, "\n"), true, ""
}

func isPermissionDenied(err error) bool {
	if err == nil {
		return false
	}
	if errors.Is(err, os.ErrPermission) {
		return true
	}
	var pe *os.PathError
	if errors.As(err, &pe) {
		return errors.Is(pe.Err, syscall.EACCES) || errors.Is(pe.Err, syscall.EPERM)
	}
	return strings.Contains(strings.ToLower(err.Error()), "permission denied")
}

func tailFileElevated(path string, lines int) (string, error) {
	if lines < 20 {
		lines = 20
	}
	if lines > 5000 {
		lines = 5000
	}
	cmd := exec.Command("sudo", "-n", "/usr/local/sbin/panelze-security", "log-tail", path, strconv.Itoa(lines))
	out, err := cmd.CombinedOutput()
	if err != nil {
		msg := strings.TrimSpace(string(out))
		if strings.Contains(strings.ToLower(msg), "not found") {
			return "", os.ErrNotExist
		}
		if msg == "" {
			msg = err.Error()
		}
		return "", fmt.Errorf("elevated log read failed: %s", msg)
	}
	return string(out), nil
}

func handleIssueSSL(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		var req struct {
			Domain       string `json:"domain" binding:"required"`
			Hostname     string `json:"hostname"`
			ParentDomain string `json:"parent_domain"`
			PathSegment  string `json:"path_segment"`
			Email        string `json:"email"`
		}
		if err := c.ShouldBindJSON(&req); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		parent := strings.ToLower(strings.TrimSpace(req.ParentDomain))
		seg := strings.TrimSpace(req.PathSegment)
		certName := strings.ToLower(strings.TrimSpace(req.Domain))
		if h := strings.ToLower(strings.TrimSpace(req.Hostname)); h != "" {
			certName = h
		}
		var meta *sites.SiteMeta
		var isSub bool
		if parent != "" && seg != "" {
			subMeta, err := sites.ReadSubdomainMeta(cfg.Paths.WebRoot, parent, seg)
			if err != nil || subMeta == nil {
				c.JSON(http.StatusNotFound, gin.H{"error": "subdomain not found"})
				return
			}
			meta = subMeta
			isSub = true
			if certName == "" && strings.TrimSpace(meta.Hostname) != "" {
				certName = strings.ToLower(strings.TrimSpace(meta.Hostname))
			}
		} else {
			var err error
			meta, err = sites.ReadSiteMeta(cfg.Paths.WebRoot, req.Domain)
			if err != nil || meta == nil {
				c.JSON(http.StatusNotFound, gin.H{"error": "site not found"})
				return
			}
			if certName == "" {
				certName = strings.ToLower(strings.TrimSpace(req.Domain))
			}
		}
		httpDoc := hosting.ResolveHTTPDocRoot(meta.DocumentRoot)
		if isSub {
			_ = hosting.FinalizeSubdomainWebStack(cfg, parent, seg, certName)
			if refreshed, rerr := sites.ReadSubdomainMeta(cfg.Paths.WebRoot, parent, seg); rerr == nil && refreshed != nil {
				meta = refreshed
				httpDoc = hosting.ResolveHTTPDocRoot(meta.DocumentRoot)
			}
		}
		acmeDir := filepath.Join(httpDoc, ".well-known", "acme-challenge")
		if err := os.MkdirAll(acmeDir, 0o755); err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "acme challenge dir: " + err.Error()})
			return
		}
		var vhostErr error
		if isSub {
			vhostErr = hosting.ApplySubdomainVhost(cfg, parent, certName, meta.DocumentRoot, meta)
		} else {
			ps := phpfpmSettings(cfg)
			phpSocket := ""
			if cfg.Hosting.PHPFPMmanagePools {
				phpSocket = ps.SocketForDomain(certName)
			} else {
				phpSocket = nginx.EffectivePHPSocket(meta.PHPVersion, cfg.Hosting.PHPFPMsocket)
			}
			vhostErr = hosting.ApplyWebServer(cfg, certName, meta.DocumentRoot, meta, phpSocket)
		}
		if vhostErr != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": vhostErr.Error()})
			return
		}
		if err := ssl.Issue(cfg, certName, httpDoc, req.Email); err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
			return
		}
		meta.SSLEnabled = true
		force := true
		meta.ForceHTTPS = &force
		var applyErr error
		if isSub {
			applyErr = hosting.ApplySSLVhost(cfg, parent, seg, certName, meta)
			if applyErr == nil {
				applyErr = sites.WriteSubdomainMeta(cfg.Paths.WebRoot, parent, seg, meta)
			}
		} else {
			applyErr = hosting.ApplySSLVhost(cfg, "", "", certName, meta)
			if applyErr == nil {
				applyErr = sites.WriteSiteMeta(cfg.Paths.WebRoot, req.Domain, meta)
			}
		}
		if applyErr != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": applyErr.Error()})
			return
		}
		c.JSON(http.StatusOK, gin.H{"message": "SSL certificate issued", "domain": certName, "ssl_enabled": true})
	}
}

func handleRenewSSL(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		var req struct {
			Domain       string `json:"domain" binding:"required"`
			ParentDomain string `json:"parent_domain"`
			PathSegment  string `json:"path_segment"`
		}
		if err := c.ShouldBindJSON(&req); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		certName := strings.ToLower(strings.TrimSpace(req.Domain))
		parent := strings.ToLower(strings.TrimSpace(req.ParentDomain))
		seg := strings.TrimSpace(req.PathSegment)
		if err := ssl.Renew(cfg, certName); err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
			return
		}
		var meta *sites.SiteMeta
		var err error
		if parent != "" && seg != "" {
			meta, err = sites.ReadSubdomainMeta(cfg.Paths.WebRoot, parent, seg)
		} else {
			meta, err = sites.ReadSiteMeta(cfg.Paths.WebRoot, certName)
		}
		if err != nil || meta == nil {
			c.JSON(http.StatusOK, gin.H{"message": "SSL certificate renewed", "domain": certName})
			return
		}
		meta.SSLEnabled = true
		if err := hosting.ApplySSLVhost(cfg, parent, seg, certName, meta); err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
			return
		}
		if parent != "" && seg != "" {
			_ = sites.WriteSubdomainMeta(cfg.Paths.WebRoot, parent, seg, meta)
		} else {
			_ = sites.WriteSiteMeta(cfg.Paths.WebRoot, certName, meta)
		}
		c.JSON(http.StatusOK, gin.H{"message": "SSL certificate renewed", "domain": certName})
	}
}

func handleRevokeSSL(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		var req struct {
			Domain       string `json:"domain" binding:"required"`
			ParentDomain string `json:"parent_domain"`
			PathSegment  string `json:"path_segment"`
		}
		if err := c.ShouldBindJSON(&req); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		certName := strings.ToLower(strings.TrimSpace(req.Domain))
		parent := strings.ToLower(strings.TrimSpace(req.ParentDomain))
		seg := strings.TrimSpace(req.PathSegment)
		_ = ssl.Delete(cfg, certName)
		var meta *sites.SiteMeta
		var err error
		if parent != "" && seg != "" {
			meta, err = sites.ReadSubdomainMeta(cfg.Paths.WebRoot, parent, seg)
		} else {
			meta, err = sites.ReadSiteMeta(cfg.Paths.WebRoot, certName)
		}
		if err == nil && meta != nil {
			meta.SSLEnabled = false
			meta.ForceHTTPS = nil
			if parent != "" && seg != "" {
				_ = sites.WriteSubdomainMeta(cfg.Paths.WebRoot, parent, seg, meta)
			} else {
				_ = sites.WriteSiteMeta(cfg.Paths.WebRoot, certName, meta)
			}
			if err := hosting.ApplySSLVhost(cfg, parent, seg, certName, meta); err != nil {
				c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
				return
			}
		}
		c.JSON(http.StatusOK, gin.H{"message": "SSL removed", "domain": certName})
	}
}

func handleManualSSL(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		var req struct {
			Domain     string `json:"domain" binding:"required"`
			CertPEM    string `json:"certificate" binding:"required"`
			PrivateKey string `json:"private_key" binding:"required"`
		}
		if err := c.ShouldBindJSON(&req); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		meta, err := sites.ReadSiteMeta(cfg.Paths.WebRoot, req.Domain)
		if err != nil || meta == nil {
			c.JSON(http.StatusNotFound, gin.H{"error": "site not found"})
			return
		}
		if err := ssl.UploadManual(cfg, req.Domain, req.CertPEM, req.PrivateKey); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		meta.SSLEnabled = true
		force := true
		meta.ForceHTTPS = &force
		phpSock := ""
		if cfg.Hosting.PHPFPMmanagePools {
			phpSock = phpfpmSettings(cfg).SocketForDomain(req.Domain)
		} else {
			phpSock = nginx.EffectivePHPSocket(meta.PHPVersion, cfg.Hosting.PHPFPMsocket)
		}
		if err := hosting.ApplyWebServer(cfg, req.Domain, meta.DocumentRoot, meta, phpSock); err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
			return
		}
		if err := sites.WriteSiteMeta(cfg.Paths.WebRoot, req.Domain, meta); err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
			return
		}
		c.JSON(http.StatusOK, gin.H{"message": "manual ssl uploaded", "domain": req.Domain, "ssl_enabled": true})
	}
}

func handleAddSubdomain(cfg *config.Config, d *daemon.Daemon) gin.HandlerFunc {
	return func(c *gin.Context) {
		parent := strings.TrimSpace(c.Param("domain"))
		var req struct {
			Hostname    string `json:"hostname" binding:"required"`
			PathSegment string `json:"path_segment" binding:"required"`
			PHPVersion  string `json:"php_version"`
		}
		if err := c.ShouldBindJSON(&req); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		parentMeta, _ := sites.ReadSiteMeta(cfg.Paths.WebRoot, parent)
		phpV := strings.TrimSpace(req.PHPVersion)
		if phpV == "" && parentMeta != nil {
			phpV = parentMeta.PHPVersion
		}
		if phpV == "" {
			phpV = "8.2"
		}
		st := sites.NormalizeServerType("")
		if parentMeta != nil && parentMeta.ServerType != "" {
			st = sites.NormalizeServerType(parentMeta.ServerType)
		}
		docRoot, err := sites.ProvisionSubdomain(cfg.Paths.WebRoot, parent, req.Hostname, req.PathSegment, phpV, st)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		subMeta := &sites.SiteMeta{
			Hostname:     strings.ToLower(strings.TrimSpace(req.Hostname)),
			PHPVersion:   phpV,
			DocumentRoot: docRoot,
			ServerType:   st,
		}
		if err := hosting.ApplySubdomainVhost(cfg, parent, req.Hostname, docRoot, subMeta); err != nil {
			_ = hosting.RemoveSubdomainVhost(cfg, req.Hostname, subMeta)
			_, _ = sites.RemoveSubdomain(cfg.Paths.WebRoot, parent, req.PathSegment)
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
			return
		}
		if finErr := hosting.FinalizeSubdomainWebStack(cfg, parent, req.PathSegment, req.Hostname); finErr != nil {
			logrus.Warnf("subdomain web finalize (%s/%s): %v", parent, req.PathSegment, finErr)
		}
		finMeta, _ := sites.ReadSubdomainMeta(cfg.Paths.WebRoot, parent, req.PathSegment)
		resp := gin.H{
			"message":       "subdomain created",
			"document_root": docRoot,
			"hostname":      req.Hostname,
			"path_segment":  req.PathSegment,
		}
		if finMeta != nil && strings.TrimSpace(finMeta.PHPVersion) != "" {
			resp["php_version"] = finMeta.PHPVersion
		}
		c.JSON(http.StatusCreated, resp)
	}
}

func handleSyncSubdomainWeb(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		parent := strings.ToLower(strings.TrimSpace(c.Param("domain")))
		seg := strings.TrimSpace(c.Param("segment"))
		if parent == "" || seg == "" || strings.Contains(seg, "/") || strings.Contains(seg, "..") {
			c.JSON(http.StatusBadRequest, gin.H{"error": "invalid subdomain"})
			return
		}
		subMeta, err := sites.ReadSubdomainMeta(cfg.Paths.WebRoot, parent, seg)
		if err != nil || subMeta == nil {
			c.JSON(http.StatusNotFound, gin.H{"error": "subdomain not found"})
			return
		}
		host := strings.ToLower(strings.TrimSpace(subMeta.Hostname))
		if host == "" {
			c.JSON(http.StatusBadRequest, gin.H{"error": "subdomain hostname missing"})
			return
		}
		if err := hosting.FinalizeSubdomainWebStack(cfg, parent, seg, host); err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
			return
		}
		updated, _ := sites.ReadSubdomainMeta(cfg.Paths.WebRoot, parent, seg)
		resp := gin.H{
			"message":      "subdomain web stack synced",
			"hostname":     host,
			"path_segment": seg,
		}
		if updated != nil {
			resp["php_version"] = updated.PHPVersion
			resp["document_root"] = updated.DocumentRoot
			resp["app_profile"] = updated.AppProfile
		}
		c.JSON(http.StatusOK, resp)
	}
}

func handleRemoveSubdomain(cfg *config.Config, d *daemon.Daemon) gin.HandlerFunc {
	return func(c *gin.Context) {
		parent := strings.TrimSpace(c.Param("domain"))
		var req struct {
			PathSegment string `json:"path_segment" binding:"required"`
		}
		if err := c.ShouldBindJSON(&req); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		subMeta, _ := sites.ReadSubdomainMeta(cfg.Paths.WebRoot, parent, req.PathSegment)
		if subMeta != nil && strings.TrimSpace(subMeta.Hostname) != "" {
			h := strings.TrimSpace(subMeta.Hostname)
			_ = ssl.Delete(cfg, h)
			_ = hosting.RemoveSubdomainVhost(cfg, h, subMeta)
		}
		_, err := sites.RemoveSubdomain(cfg.Paths.WebRoot, parent, req.PathSegment)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		c.JSON(http.StatusOK, gin.H{"message": "subdomain removed"})
	}
}

func handleAddSiteAlias(cfg *config.Config, d *daemon.Daemon) gin.HandlerFunc {
	return func(c *gin.Context) {
		parent := strings.TrimSpace(c.Param("domain"))
		var req struct {
			Hostname string `json:"hostname" binding:"required"`
		}
		if err := c.ShouldBindJSON(&req); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		if err := sites.AppendAlias(cfg.Paths.WebRoot, parent, req.Hostname); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		meta, err := sites.ReadSiteMeta(cfg.Paths.WebRoot, parent)
		if err != nil || meta == nil {
			c.JSON(http.StatusNotFound, gin.H{"error": "site not found"})
			return
		}
		phpSock := ""
		if cfg.Hosting.PHPFPMmanagePools {
			phpSock = phpfpmSettings(cfg).SocketForDomain(parent)
		} else {
			phpSock = nginx.EffectivePHPSocket(meta.PHPVersion, cfg.Hosting.PHPFPMsocket)
		}
		if err := hosting.ApplyWebServer(cfg, parent, meta.DocumentRoot, meta, phpSock); err != nil {
			_ = sites.RemoveAlias(cfg.Paths.WebRoot, parent, req.Hostname)
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
			return
		}
		c.JSON(http.StatusOK, gin.H{"message": "alias added", "hostname": req.Hostname})
	}
}

func handleRemoveSiteAlias(cfg *config.Config, d *daemon.Daemon) gin.HandlerFunc {
	return func(c *gin.Context) {
		parent := strings.TrimSpace(c.Param("domain"))
		var req struct {
			Hostname string `json:"hostname" binding:"required"`
		}
		if err := c.ShouldBindJSON(&req); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		if err := sites.RemoveAlias(cfg.Paths.WebRoot, parent, req.Hostname); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		meta, err := sites.ReadSiteMeta(cfg.Paths.WebRoot, parent)
		if err != nil || meta == nil {
			c.JSON(http.StatusOK, gin.H{"message": "alias removed"})
			return
		}
		phpSock := ""
		if cfg.Hosting.PHPFPMmanagePools {
			phpSock = phpfpmSettings(cfg).SocketForDomain(parent)
		} else {
			phpSock = nginx.EffectivePHPSocket(meta.PHPVersion, cfg.Hosting.PHPFPMsocket)
		}
		if err := hosting.ApplyWebServer(cfg, parent, meta.DocumentRoot, meta, phpSock); err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
			return
		}
		c.JSON(http.StatusOK, gin.H{"message": "alias removed"})
	}
}
