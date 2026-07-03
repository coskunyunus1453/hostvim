<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Admin\DnsSettingsController;
use App\Http\Controllers\Admin\ManagedBackupController;
use App\Http\Controllers\Admin\OutboundMailSettingsController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\PhpSettingsController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\StackController;
use App\Http\Controllers\Admin\TerminalSettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ServerMysqlSettingsController;
use App\Http\Controllers\Admin\WebServerSettingsController;
use App\Http\Controllers\Admin\WhmcsModuleController;
use App\Http\Controllers\Admin\Billing\AdminBillingController;
use App\Http\Controllers\Admin\Billing\BillingSettingsController;
use App\Http\Controllers\Api\Billing\InvoiceController;
use App\Http\Controllers\Api\Billing\OrderController;
use App\Http\Controllers\Api\Billing\DomainRegisterController;
use App\Http\Controllers\Api\Billing\PaytrCallbackController;
use App\Http\Controllers\Api\Billing\IyzicoCallbackController;
use App\Http\Controllers\Api\AiAdvisorController;
use App\Http\Controllers\Api\AiAssistantController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BackupController;
use App\Http\Controllers\Api\BackupGoogleDriveController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\BrandingController;
use App\Http\Controllers\Api\CronDiscoveryController;
use App\Http\Controllers\Api\CronJobController;
use App\Http\Controllers\Api\DatabaseController;
use App\Http\Controllers\Api\DeploymentController;
use App\Http\Controllers\Api\DnsRecordController;
use App\Http\Controllers\Api\DocumentRootController;
use App\Http\Controllers\Api\DomainController;
use App\Http\Controllers\Api\DomainPortfolioController;
use App\Http\Controllers\Api\DomainApacheVhostController;
use App\Http\Controllers\Api\DomainOlsVhostController;
use App\Http\Controllers\Api\DomainNginxVhostController;
use App\Http\Controllers\Api\EmailAccountController;
use App\Http\Controllers\Api\FileManagerController;
use App\Http\Controllers\Api\HostingTargetsController;
use App\Http\Controllers\Api\Internal\PhpMyAdminSignonConsumeController;
use App\Http\Controllers\Api\Internal\WebmailSignonConsumeController;
use App\Http\Controllers\Api\FtpController;
use App\Http\Controllers\Api\Integrations\StoreCustomerController;
use App\Http\Controllers\Api\Integrations\StoreSettingsSyncController;
use App\Http\Controllers\Api\Integrations\StoreIntegrationController;
use App\Http\Controllers\Api\Integrations\WhmcsProvisioningController;
use App\Http\Controllers\Api\Integrations\WhmcsResourcesController;
use App\Http\Controllers\Api\InstallerController;
use App\Http\Controllers\Api\LicenseController;
use App\Http\Controllers\Api\MonitoringController;
use App\Http\Controllers\Api\NodeAppController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PanelUpdateController;
use App\Http\Controllers\Api\PerformanceController;
use App\Http\Controllers\Api\PhpFunctionsController;
use App\Http\Controllers\Api\PluginStoreController;
use App\Http\Controllers\Api\RedirectController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SecurityController;
use App\Http\Controllers\Api\CuriousController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\SiteStackController;
use App\Http\Controllers\Api\SslController;
use App\Http\Controllers\Api\SystemController;
use App\Http\Controllers\Api\TerminalController;
use App\Http\Controllers\Api\TwoFactorController;
use App\Http\Controllers\Api\UiLinksController;
use App\Http\Controllers\Api\Vendor\BillingController as VendorBillingController;
use App\Http\Controllers\Api\Vendor\FeatureController as VendorFeatureController;
use App\Http\Controllers\Api\Vendor\LicenseController as VendorLicenseController;
use App\Http\Controllers\Api\Vendor\NodeController as VendorNodeController;
use App\Http\Controllers\Api\Vendor\OpsController as VendorOpsController;
use App\Http\Controllers\Api\Vendor\PlanController as VendorPlanController;
use App\Http\Controllers\Api\Vendor\SecurityController as VendorSecurityController;
use App\Http\Controllers\Api\Vendor\SupportController as VendorSupportController;
use App\Http\Controllers\Api\Vendor\TenantController as VendorTenantController;
use App\Http\Controllers\Reseller\ResellerRoleController;
use App\Http\Controllers\Reseller\ResellerWhiteLabelController;
use App\Services\EngineApiService;
use Illuminate\Support\Facades\Route;

Route::post('internal/webmail-signon/consume', [WebmailSignonConsumeController::class, 'consume'])
    ->middleware('throttle:60,1');
Route::post('internal/phpmyadmin-signon/consume', [PhpMyAdminSignonConsumeController::class, 'consume'])
    ->middleware('throttle:60,1');

Route::get('branding', [BrandingController::class, 'showPublic']);
Route::get('branding/files/{filename}', [BrandingController::class, 'serveFile'])
    ->where('filename', '[A-Za-z0-9._-]+');
Route::get('branding/wl/{userId}/{filename}', [BrandingController::class, 'serveWlFile'])
    ->whereNumber('userId')
    ->where('filename', '[A-Za-z0-9._-]+')
    ->middleware('throttle:120,1');

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('sso/whmcs-consume', [AuthController::class, 'consumeWhmcsSso'])->middleware('throttle:sso-consume');

    Route::middleware(['auth:sanctum', 'abilities:access:customer-panel'])->group(function () {
        Route::get('2fa/status', [TwoFactorController::class, 'status']);
        Route::post('2fa/setup', [TwoFactorController::class, 'setup'])->middleware('throttle:20,1');
        Route::post('2fa/verify', [TwoFactorController::class, 'verify'])->middleware('throttle:12,1');
        Route::post('2fa/backup-codes/regenerate', [TwoFactorController::class, 'regenerateBackupCodes'])->middleware('throttle:6,1');
        Route::post('2fa/disable', [TwoFactorController::class, 'disable'])->middleware('throttle:10,1');

        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh'])->middleware('throttle:15,1');
    });
});

Route::middleware(['auth:sanctum', 'abilities:access:customer-panel', 'require_password_change'])->group(function () {
    Route::patch('user/profile', [ProfileController::class, 'update']);
    Route::post('user/password', [ProfileController::class, 'password'])->middleware('throttle:10,1');
    Route::post('user/onboarding/complete', [ProfileController::class, 'completeOnboarding']);

    Route::middleware('ability:dashboard:read')->group(function () {
        Route::get('dashboard', [SystemController::class, 'dashboard']);
        Route::get('config/ui-links', [UiLinksController::class, 'show']);
        Route::get('license', [LicenseController::class, 'status']);
    });

    Route::middleware('ability:sites:read')->group(function () {
        Route::get('sites/list', [SiteController::class, 'list']);
    });
    Route::middleware('ability:sites:write')->group(function () {
        Route::post('sites/create', [SiteController::class, 'create']);
        Route::post('sites/delete', [SiteController::class, 'delete']);
        Route::post('sites/subdomain/add', [SiteController::class, 'addSubdomain']);
        Route::post('sites/subdomain/remove', [SiteController::class, 'removeSubdomain']);
        Route::post('sites/domain-alias/add', [SiteController::class, 'addDomainAlias']);
        Route::post('sites/domain-alias/remove', [SiteController::class, 'removeDomainAlias']);
    });

    Route::middleware('ability:domains:read')->group(function () {
        Route::get('hosting/targets', [HostingTargetsController::class, 'index']);
        Route::get('domains/options', [DomainController::class, 'options']);
        Route::get('domains/switchable-server-types', [DomainController::class, 'switchableServerTypes']);
        Route::get('domains', [DomainController::class, 'index']);
        Route::get('domains/stack-alerts', [SiteStackController::class, 'alerts']);
        Route::get('domains/{domain}', [DomainController::class, 'show']);
        Route::get('domains/{domain}/logs', [DomainController::class, 'logs']);
        Route::get('domains/{domain}/traffic', [DomainController::class, 'traffic']);
        Route::get('domains/{domain}/stack-scan', [SiteStackController::class, 'scan']);
        Route::get('domains/{domain}/performance', [PerformanceController::class, 'show']);
        Route::get('domains/{domain}/php-functions', [PhpFunctionsController::class, 'show']);
        Route::get('domains/{domain}/redirects', [RedirectController::class, 'index']);
    });
    Route::middleware('ability:domains:write')->group(function () {
        Route::post('domains', [DomainController::class, 'store']);
        Route::post('domains/stack-alerts/{alert}/dismiss', [SiteStackController::class, 'dismissAlert']);
        Route::delete('domains/{domain}', [DomainController::class, 'destroy']);
        Route::post('domains/{domain}/php', [DomainController::class, 'switchPhp']);
        Route::post('domains/{domain}/status', [DomainController::class, 'setStatus']);
        Route::post('domains/{domain}/reprovision', [DomainController::class, 'reprovision']);
        Route::post('domains/{domain}/server', [DomainController::class, 'switchServer']);
        Route::post('domains/{domain}/subdomains', [DomainController::class, 'storeSubdomain']);
        Route::delete('domains/{domain}/subdomains', [DomainController::class, 'destroySubdomain']);
        Route::post('domains/{domain}/aliases', [DomainController::class, 'storeAlias']);
        Route::delete('domains/{domain}/aliases', [DomainController::class, 'destroyAlias']);
        Route::post('domains/{domain}/document-root', [DocumentRootController::class, 'update']);
        Route::post('domains/{domain}/stack-fix', [SiteStackController::class, 'fix']);
        Route::post('domains/{domain}/performance', [PerformanceController::class, 'update']);
        Route::post('domains/{domain}/php-functions', [PhpFunctionsController::class, 'update']);
        Route::put('domains/{domain}/redirects', [RedirectController::class, 'update']);
        Route::get('domains/{domain}/nginx-vhost', [DomainNginxVhostController::class, 'show']);
        Route::put('domains/{domain}/nginx-vhost', [DomainNginxVhostController::class, 'update']);
        Route::post('domains/{domain}/nginx-vhost/revert', [DomainNginxVhostController::class, 'revert']);
        Route::get('domains/{domain}/apache-vhost', [DomainApacheVhostController::class, 'show']);
        Route::put('domains/{domain}/apache-vhost', [DomainApacheVhostController::class, 'update']);
        Route::post('domains/{domain}/apache-vhost/revert', [DomainApacheVhostController::class, 'revert']);
        Route::get('domains/{domain}/ols-vhost', [DomainOlsVhostController::class, 'show']);
        Route::put('domains/{domain}/ols-vhost', [DomainOlsVhostController::class, 'update']);
        Route::post('domains/{domain}/ols-vhost/revert', [DomainOlsVhostController::class, 'revert']);
    });

    Route::middleware('ability:databases:read')->group(function () {
        Route::get('databases', [DatabaseController::class, 'index']);
        Route::get('databases/import-meta', [DatabaseController::class, 'importMeta']);
        Route::get('databases/{database}/import/{import}', [DatabaseController::class, 'importStatus']);
        Route::get('databases/{database}/export', [DatabaseController::class, 'export'])->middleware('throttle:api');
        Route::post('databases/{database}/phpmyadmin-login', [DatabaseController::class, 'phpmyadminLogin']);
    });
    Route::middleware('ability:databases:write')->group(function () {
        Route::post('databases', [DatabaseController::class, 'store']);
        Route::patch('databases/{database}', [DatabaseController::class, 'update']);
        Route::delete('databases/{database}', [DatabaseController::class, 'destroy']);
        Route::post('databases/{database}/rotate-password', [DatabaseController::class, 'rotatePassword']);
        Route::post('databases/{database}/import', [DatabaseController::class, 'import'])->middleware('throttle:databases-import');
    });

    Route::prefix('domains/{domain}/files')->group(function () {
        Route::middleware('ability:files:read')->group(function () {
            Route::get('/', [FileManagerController::class, 'index'])->middleware('throttle:files-read');
            Route::get('tree', [FileManagerController::class, 'tree'])->middleware('throttle:files-read');
            Route::get('search', [FileManagerController::class, 'search'])->middleware('throttle:files-read');
            Route::get('read', [FileManagerController::class, 'read'])->middleware('throttle:files-read');
            Route::post('read', [FileManagerController::class, 'read'])->middleware('throttle:files-read');
            Route::get('download', [FileManagerController::class, 'download'])->middleware('throttle:files-read');
            Route::get('trash', [FileManagerController::class, 'trashIndex'])->middleware('throttle:files-read');
        });
        Route::middleware('ability:files:write')->group(function () {
            Route::post('mkdir', [FileManagerController::class, 'mkdir'])->middleware('throttle:files-write');
            Route::delete('/', [FileManagerController::class, 'destroy'])->middleware('throttle:files-write');
            Route::post('write', [FileManagerController::class, 'write'])->middleware('throttle:files-write');
            Route::post('create', [FileManagerController::class, 'create'])->middleware('throttle:files-write');
            Route::post('upload', [FileManagerController::class, 'upload'])->middleware('throttle:files-upload');
            Route::post('rename', [FileManagerController::class, 'rename'])->middleware('throttle:files-write');
            Route::post('move', [FileManagerController::class, 'move'])->middleware('throttle:files-write');
            Route::post('copy', [FileManagerController::class, 'copy'])->middleware('throttle:files-write');
            Route::post('chmod', [FileManagerController::class, 'chmod'])->middleware('throttle:files-write');
            Route::post('zip', [FileManagerController::class, 'zip'])->middleware('throttle:files-write');
            Route::post('zip-bulk', [FileManagerController::class, 'zipBulk'])->middleware('throttle:files-write');
            Route::post('unzip', [FileManagerController::class, 'unzip'])->middleware('throttle:files-write');
            Route::post('trash/move', [FileManagerController::class, 'trashMove'])->middleware('throttle:files-write');
            Route::post('trash/move-bulk', [FileManagerController::class, 'trashMoveBulk'])->middleware('throttle:files-write');
            Route::post('trash/restore', [FileManagerController::class, 'trashRestore'])->middleware('throttle:files-write');
            Route::delete('trash/item', [FileManagerController::class, 'trashDestroy'])->middleware('throttle:files-write');
            Route::delete('trash/empty', [FileManagerController::class, 'trashEmpty'])->middleware('throttle:files-write');
        });
    });

    Route::middleware('ability:backups:read')->group(function () {
        Route::get('backups', [BackupController::class, 'index']);
        Route::get('backups/engine/snapshot', [BackupController::class, 'engineSnapshot']);
        Route::get('backups/destinations', [BackupController::class, 'destinations']);
        Route::get('backups/schedules', [BackupController::class, 'schedules']);
        Route::get('backups/{backup}/download', [BackupController::class, 'download']);
    });
    Route::middleware(['ability:backups:read', 'pro.feature:backups_pro'])->group(function () {
        Route::get('backups/google-drive/status', [BackupGoogleDriveController::class, 'status']);
        Route::get('backups/destinations/{backupDestination}/remote-files', [BackupGoogleDriveController::class, 'listFiles']);
    });
    Route::middleware('ability:backups:write')->group(function () {
        Route::post('backups', [BackupController::class, 'store'])->middleware('throttle:backups-write');
        Route::post('backups/restore-upload', [BackupController::class, 'uploadRestore'])->middleware('throttle:backups-write');
        Route::delete('backups/{backup}', [BackupController::class, 'destroy'])->middleware('throttle:backups-write');
        Route::post('backups/{backup}/restore', [BackupController::class, 'restore'])->middleware('throttle:backups-write');
        Route::post('backups/{backup}/sync', [BackupController::class, 'sync'])->middleware('throttle:backups-write');
        Route::post('backups/{backup}/retry', [BackupController::class, 'retry'])->middleware('throttle:backups-write');
        Route::post('backups/destinations', [BackupController::class, 'storeDestination'])->middleware('throttle:backups-write');
        Route::patch('backups/destinations/{backupDestination}', [BackupController::class, 'updateDestination'])->middleware('throttle:backups-write');
        Route::delete('backups/destinations/{backupDestination}', [BackupController::class, 'destroyDestination'])->middleware('throttle:backups-write');
        Route::post('backups/schedules', [BackupController::class, 'storeSchedule'])->middleware('throttle:backups-write');
        Route::patch('backups/schedules/{backupSchedule}', [BackupController::class, 'updateSchedule'])->middleware('throttle:backups-write');
        Route::delete('backups/schedules/{backupSchedule}', [BackupController::class, 'destroySchedule'])->middleware('throttle:backups-write');
        Route::post('backups/schedules/{backupSchedule}/run', [BackupController::class, 'runSchedule'])->middleware('throttle:backups-write');
    });
    Route::middleware(['ability:backups:write', 'pro.feature:backups_pro'])->group(function () {
        Route::post('backups/restore-remote', [BackupController::class, 'restoreRemote'])->middleware('throttle:backups-write');
        Route::get('backups/google-drive/auth-url', [BackupGoogleDriveController::class, 'authUrl']);
        Route::post('backups/google-drive/complete', [BackupGoogleDriveController::class, 'complete']);
        Route::delete('backups/google-drive/disconnect', [BackupGoogleDriveController::class, 'disconnect']);
    });

    Route::middleware('ability:ftp:read')->get('domains/{domain}/ftp', [FtpController::class, 'index']);
    Route::middleware('ability:ftp:write')->group(function () {
        Route::post('domains/{domain}/ftp', [FtpController::class, 'store']);
        Route::delete('ftp/{ftpAccount}', [FtpController::class, 'destroy']);
    });

    Route::middleware('ability:email:read')->get('domains/{domain}/email', [EmailAccountController::class, 'index']);
    Route::middleware('ability:email:read')->post('email/{emailAccount}/webmail-login', [EmailAccountController::class, 'webmailLogin']);
    Route::middleware('ability:email:write')->group(function () {
        Route::post('domains/{domain}/email', [EmailAccountController::class, 'store']);
        Route::post('domains/{domain}/email/ensure-dns', [EmailAccountController::class, 'ensureDns']);
        Route::post('domains/{domain}/email/forwarders', [EmailAccountController::class, 'storeForwarder']);
        Route::patch('email/{emailAccount}', [EmailAccountController::class, 'update']);
        Route::delete('email/{emailAccount}', [EmailAccountController::class, 'destroy']);
        Route::delete('email/forwarders/{emailForwarder}', [EmailAccountController::class, 'destroyForwarder']);
    });

    Route::middleware('ability:dns:read')->get('domains/{domain}/dns', [DnsRecordController::class, 'index']);
    Route::middleware('ability:dns:read')->get('domains/{domain}/dns/zone', [DnsRecordController::class, 'exportZone']);
    Route::middleware('ability:dns:write')->group(function () {
        Route::post('domains/{domain}/dns', [DnsRecordController::class, 'store']);
        Route::post('domains/{domain}/dns/bootstrap', [DnsRecordController::class, 'bootstrapDefaults']);
        Route::delete('dns/{dnsRecord}', [DnsRecordController::class, 'destroy']);
    });

    Route::middleware('ability:ssl:read')->get('ssl', [SslController::class, 'index']);
    Route::middleware('ability:ssl:write')->group(function () {
        Route::post('domains/{domain}/ssl/issue', [SslController::class, 'issue']);
        Route::post('domains/{domain}/ssl/renew', [SslController::class, 'renew']);
        Route::post('domains/{domain}/ssl/revoke', [SslController::class, 'revoke']);
        Route::post('domains/{domain}/ssl/manual', [SslController::class, 'manual']);
        Route::patch('domains/{domain}/ssl/settings', [SslController::class, 'updateSettings']);
    });

    Route::middleware('ability:cron:read')->group(function () {
        Route::get('cron/summary', [CronJobController::class, 'summary']);
        Route::get('cron', [CronJobController::class, 'index']);
        Route::get('domains/{domain}/cron/discover', [CronDiscoveryController::class, 'discover']);
    });
    Route::middleware('ability:cron:write')->group(function () {
        Route::post('cron', [CronJobController::class, 'store']);
        Route::patch('cron/{cronJob}', [CronJobController::class, 'update']);
        Route::delete('cron/{cronJob}', [CronJobController::class, 'destroy']);
        Route::post('cron/{cronJob}/run-now', [CronJobController::class, 'runNow']);
    });
    Route::middleware('ability:cron:read')->get('cron/{cronJob}/runs', [CronJobController::class, 'runs']);

    Route::middleware('ability:monitoring:read')->get('monitoring/summary', [MonitoringController::class, 'userSummary']);
    Route::middleware('ability:monitoring:read')->get('monitoring/health', [MonitoringController::class, 'health']);
    Route::middleware('ability:monitoring:read')->get('monitoring/health/sites', [MonitoringController::class, 'healthSites']);
    Route::middleware(['ability:monitoring:server', 'pro.feature:monitoring_advanced'])
        ->get('monitoring/server', [MonitoringController::class, 'server']);

    Route::middleware('ability:dashboard:read')->group(function () {
        Route::get('notifications/feed', [NotificationController::class, 'feed']);
        Route::post('notifications/dismiss', [NotificationController::class, 'dismiss']);
    });

    Route::middleware(['ability:dashboard:read', 'pro.feature:ai_advisor'])->group(function () {
        Route::get('ai/cron-backup', [AiAdvisorController::class, 'cronBackup']);
        Route::get('ai/monitoring', [AiAdvisorController::class, 'monitoring']);
        Route::get('ai/access', [AiAdvisorController::class, 'access']);

        Route::prefix('ai-assistant')->group(function () {
            Route::get('settings', [AiAssistantController::class, 'settings']);
            Route::put('settings', [AiAssistantController::class, 'updateSettings']);
            Route::post('settings/test', [AiAssistantController::class, 'testProvider']);
            Route::get('usage', [AiAssistantController::class, 'usage']);
            Route::get('sessions', [AiAssistantController::class, 'sessions']);
            Route::post('sessions', [AiAssistantController::class, 'createSession']);
            Route::delete('sessions/{aiChatSession}', [AiAssistantController::class, 'destroySession']);
            Route::get('sessions/{aiChatSession}/messages', [AiAssistantController::class, 'messages']);
            Route::post('chat', [AiAssistantController::class, 'chat'])->middleware('throttle:30,1');
            Route::post('execute-actions', [AiAssistantController::class, 'executeActions'])
                ->middleware(['ability:tools:run', 'throttle:10,1']);
        });
    });
    Route::middleware(['ability:files:write', 'pro.feature:ai_advisor'])->post('ai-assistant/apply-fix', [AiAssistantController::class, 'applyFix']);
    Route::middleware(['ability:files:read', 'pro.feature:ai_advisor'])->post('ai-assistant/read-file', [AiAssistantController::class, 'readFile']);
    Route::middleware(['ability:files:read', 'pro.feature:ai_advisor'])->post('domains/{domain}/ai/file-editor', [AiAdvisorController::class, 'fileEditor']);
    Route::middleware(['ability:tools:run', 'pro.feature:ai_advisor'])->get('domains/{domain}/ai/deploy', [AiAdvisorController::class, 'deploy']);
    Route::middleware(['ability:dashboard:read', 'pro.feature:ai_advisor'])->get('domains/{domain}/ai/slow-site', [AiAdvisorController::class, 'slowSite']);

    Route::middleware(['security.center', 'ability:security:read'])->get('security/overview', [SecurityController::class, 'overview']);
    Route::middleware(['security.center', 'ability:security:read'])->get('security/advisor', [SecurityController::class, 'advisor']);
    Route::middleware(['security.center', 'ability:security:read'])->get('security/rate-limit/profile', [SecurityController::class, 'getRateLimitProfile']);
    Route::middleware(['security.center', 'ability:security:read'])->get('security/modsecurity/site-rules', [SecurityController::class, 'getModSecuritySiteRules']);
    Route::middleware(['security.center', 'ability:security:read'])->get('security/intel/policy', [SecurityController::class, 'intelPolicy']);
    Route::middleware(['security.center', 'ability:security:read'])->get('security/intel/status', [SecurityController::class, 'intelStatus']);
    Route::middleware(['security.center', 'ability:security:read'])->get('security/fim/status', [SecurityController::class, 'fimStatus']);
    Route::middleware(['security.center', 'ability:security:read'])->get('security/ssh/hardening', [SecurityController::class, 'sshHardening']);
    Route::middleware(['security.center', 'ability:security:read'])->get('security/ddos/sysctl', [SecurityController::class, 'ddosSysctl']);
    Route::middleware(['security.center', 'ability:security:read'])->get('security/alerts', [SecurityController::class, 'alerts']);

    Route::middleware(['ability:curious:read', 'pro.feature:curious_tools'])->prefix('curious')->group(function () {
        Route::middleware('throttle:curious-speed')->group(function () {
            Route::get('speed/ping', [CuriousController::class, 'ping']);
            Route::post('speed/download/prepare', [CuriousController::class, 'prepareDownload']);
            Route::get('speed/download/{token}', [CuriousController::class, 'download'])->where('token', '[a-zA-Z0-9]{20,64}');
            Route::post('speed/upload', [CuriousController::class, 'upload']);
            Route::post('speed/cleanup', [CuriousController::class, 'cleanup']);
            Route::get('speed/history', [CuriousController::class, 'speedHistory']);
        });
        Route::post('speed/complete', [CuriousController::class, 'completeSpeed'])
            ->middleware('throttle:curious-speed-complete');
        Route::post('seo/analyze', [CuriousController::class, 'analyzeSeo'])
            ->middleware('throttle:curious-seo');
    });

    Route::middleware(['security.center', 'role:admin', 'ability:security:write'])->group(function () {
        Route::post('security/firewall', [SecurityController::class, 'firewall']);
        Route::post('security/fail2ban/toggle', [SecurityController::class, 'toggleFail2ban']);
        Route::post('security/fail2ban/install', [SecurityController::class, 'installFail2ban']);
        Route::post('security/fail2ban/jail', [SecurityController::class, 'updateFail2banJail']);
        Route::post('security/modsecurity/toggle', [SecurityController::class, 'toggleModSecurity']);
        Route::post('security/modsecurity/install', [SecurityController::class, 'installModSecurity']);
        Route::post('security/clamav/toggle', [SecurityController::class, 'toggleClamav']);
        Route::post('security/clamav/install', [SecurityController::class, 'installClamav']);
        Route::post('security/clamav/scan', [SecurityController::class, 'scanClamav']);
        Route::post('security/clamav/quarantine', [SecurityController::class, 'quarantineClamav']);
        Route::post('security/clamav/maldet-scan', [SecurityController::class, 'scanMaldet']);
        Route::post('security/mail/reconcile', [SecurityController::class, 'reconcileMailState']);
        Route::post('security/rate-limit/profile', [SecurityController::class, 'setRateLimitProfile']);
        Route::post('security/modsecurity/site-rule', [SecurityController::class, 'addModSecuritySiteRule']);
        Route::delete('security/modsecurity/site-rule', [SecurityController::class, 'removeModSecuritySiteRule']);
        Route::post('security/intel/policy', [SecurityController::class, 'updateIntelPolicy']);
        Route::post('security/fim/baseline', [SecurityController::class, 'createFimBaseline']);
        Route::post('security/fim/scan', [SecurityController::class, 'runFimScan']);
        Route::post('security/ssh/hardening', [SecurityController::class, 'applySshHardening']);
        Route::post('security/ddos/harden', [SecurityController::class, 'applyDdosHardening']);
        Route::post('security/bootstrap-defaults', [SecurityController::class, 'bootstrapDefaults']);
    });

    Route::middleware('ability:installer:read')->get('installer/apps', [InstallerController::class, 'apps']);
    Route::middleware('ability:installer:read')->post('installer/diagnostics', [InstallerController::class, 'diagnostics']);
    Route::middleware('ability:installer:read')->get('installer/runs', [InstallerController::class, 'runs']);
    Route::middleware('ability:installer:read')->get('installer/runs/{installerRun}', [InstallerController::class, 'runShow']);
    Route::middleware('ability:installer:write')->post('domains/{domain}/installer', [InstallerController::class, 'install']);

    Route::middleware('ability:tools:run')->group(function () {
        Route::get('domains/{domain}/node-app', [NodeAppController::class, 'show']);
        Route::post('domains/{domain}/node-app/detect', [NodeAppController::class, 'detect']);
        Route::put('domains/{domain}/node-app', [NodeAppController::class, 'update']);
        Route::post('domains/{domain}/node-app/auto-configure', [NodeAppController::class, 'autoConfigure']);
        Route::post('domains/{domain}/node-app/start', [NodeAppController::class, 'start']);
        Route::post('domains/{domain}/node-app/stop', [NodeAppController::class, 'stop']);
        Route::post('domains/{domain}/node-app/restart', [NodeAppController::class, 'restart']);
        Route::post('domains/{domain}/node-app/install', [NodeAppController::class, 'install']);
        Route::post('domains/{domain}/node-app/build', [NodeAppController::class, 'build']);
        Route::post('domains/{domain}/node-app/heal', [NodeAppController::class, 'heal']);
    });
    Route::middleware('ability:tools:run')->group(function () {
        Route::get('domains/{domain}/deployment', [DeploymentController::class, 'show']);
        Route::put('domains/{domain}/deployment', [DeploymentController::class, 'update'])->middleware('throttle:deploy-run');
        Route::post('domains/{domain}/deployment/run', [DeploymentController::class, 'run'])->middleware('throttle:deploy-run');
        Route::post('domains/{domain}/deployment/rollback', [DeploymentController::class, 'rollback'])->middleware('throttle:deploy-run');
        Route::get('domains/{domain}/deployment/runs', [DeploymentController::class, 'runs']);
    });

    Route::middleware('ability:dashboard:read')->get('plugins/store', [PluginStoreController::class, 'index']);
    Route::middleware('ability:dashboard:read')->get('plugins/migrations/runs', [PluginStoreController::class, 'runs']);
    Route::middleware('ability:tools:run')->group(function () {
        Route::post('plugins/{pluginModule}/install', [PluginStoreController::class, 'install'])->middleware('throttle:plugins-write');
        Route::post('plugins/{pluginModule}/activate', [PluginStoreController::class, 'activate'])->middleware('throttle:plugins-write');
        Route::post('plugins/{pluginModule}/deactivate', [PluginStoreController::class, 'deactivate'])->middleware('throttle:plugins-write');
        Route::post('plugins/{pluginModule}/migrations/discover', [PluginStoreController::class, 'discover'])->middleware('throttle:plugins-write');
        Route::post('plugins/{pluginModule}/migrations/preflight', [PluginStoreController::class, 'preflight'])->middleware('throttle:plugins-write');
        Route::post('plugins/{pluginModule}/migrations/start', [PluginStoreController::class, 'startMigration'])->middleware('throttle:plugins-write');
    });

    Route::middleware('role:admin')->group(function (): void {
        Route::post('license/validate', [LicenseController::class, 'validateWithKey']);
        Route::post('license/activate', [LicenseController::class, 'activate']);
        Route::post('license/clear', [LicenseController::class, 'clearStored']);
    });

    Route::middleware('ability:billing:read')->group(function () {
        Route::get('billing/packages', [BillingController::class, 'packages']);
        Route::get('billing/subscriptions', [BillingController::class, 'subscriptions']);
        Route::get('billing/license', [BillingController::class, 'licenseSummary']);
        Route::get('billing/orders', [OrderController::class, 'index']);
        Route::get('billing/orders/{order}', [OrderController::class, 'show']);
        Route::get('billing/invoices', [InvoiceController::class, 'index']);
        Route::get('billing/invoices/{invoice}', [InvoiceController::class, 'show']);
        Route::get('billing/domains/tlds', [DomainRegisterController::class, 'tlds']);
        Route::get('domain-portfolio/registrars', [DomainPortfolioController::class, 'registrars']);
        Route::get('domain-portfolio', [DomainPortfolioController::class, 'index']);
    });
    Route::middleware(['ability:billing:write', 'pro.feature:stripe_billing', 'throttle:10,1'])
        ->post('billing/checkout', [BillingController::class, 'checkout']);
    Route::middleware(['ability:billing:write', 'throttle:20,1'])->group(function () {
        Route::post('domain-portfolio/transfers', [DomainPortfolioController::class, 'requestTransfer'])->middleware('throttle:10,1');
        Route::patch('domain-portfolio/registrations/{registration}', [DomainPortfolioController::class, 'updateRegistration']);
        Route::post('billing/domains/check', [DomainRegisterController::class, 'check']);
        Route::post('billing/orders', [OrderController::class, 'store']);
        Route::post('billing/invoices/{invoice}/pay', [InvoiceController::class, 'pay']);
    });

    Route::middleware(['role:admin|vendor_admin|vendor_support|vendor_finance|vendor_devops', 'require_admin_2fa'])->post('terminal/session', [TerminalController::class, 'session']);

    Route::middleware(['role:admin|vendor_admin|vendor_support|vendor_finance|vendor_devops', 'require_admin_2fa'])->prefix('system')->group(function () {
        Route::get('stats', [SystemController::class, 'stats']);
        Route::get('services', [SystemController::class, 'services']);
        Route::get('panel/health', [SystemController::class, 'panelHealth']);
        Route::post('panel/repair', [SystemController::class, 'panelRepair']);
        Route::get('panel/update/status', [PanelUpdateController::class, 'status']);
        Route::post('panel/update/check', [PanelUpdateController::class, 'check']);
        Route::post('panel/update/dismiss', [PanelUpdateController::class, 'dismiss']);
        Route::post('panel/update/apply', [PanelUpdateController::class, 'apply']);
        Route::get('panel/update/runs', [PanelUpdateController::class, 'runs']);
        Route::get('panel/update/runs/{panelUpdateRun}', [PanelUpdateController::class, 'showRun']);
        Route::get('processes', [SystemController::class, 'processes']);
        Route::post('processes/kill', [SystemController::class, 'killProcess']);
        Route::post('services/{name}', [SystemController::class, 'serviceAction']);
        Route::post('reboot', [SystemController::class, 'reboot']);
        Route::get('server-settings', [SystemController::class, 'serverSettings']);
        Route::patch('server-settings', [SystemController::class, 'updateServerSettings']);
        Route::post('network/refresh', [SystemController::class, 'refreshNetwork']);
        Route::post('network/addresses', [SystemController::class, 'addNetworkAddress']);
        Route::delete('network/addresses', [SystemController::class, 'removeNetworkAddress']);
        Route::post('nginx/reload', function (Request $request, EngineApiService $engine) {
            if (! $request->user()?->isAdmin()) {
                abort(403);
            }

            return response()->json($engine->reloadNginx());
        });
    });

    Route::middleware(['role:admin', 'require_admin_2fa'])->prefix('admin')->group(function () {
        Route::post('settings/branding', [BrandingController::class, 'update'])->middleware('throttle:15,1');
        Route::get('settings/branding', [BrandingController::class, 'config']);
        Route::put('settings/branding', [BrandingController::class, 'updateConfig']);
        Route::get('settings/branding/diagnostics', [BrandingController::class, 'diagnostics']);
        Route::get('abilities/registry', [RoleController::class, 'registry']);
        Route::apiResource('roles', RoleController::class)->except(['show']);
        Route::get('stack/modules', [StackController::class, 'modules']);
        Route::post('stack/install', [StackController::class, 'install'])->middleware('throttle:6,1');
        Route::get('stack/runs', [StackController::class, 'runs']);
        Route::get('stack/runs/{stackInstallRun}', [StackController::class, 'showRun']);
        Route::post('stack/runs/{stackInstallRun}/cancel', [StackController::class, 'cancelRun']);
        Route::post('stack/runs/{stackInstallRun}/retry', [StackController::class, 'retryRun']);
        Route::get('settings/dns', [DnsSettingsController::class, 'show']);
        Route::put('settings/dns', [DnsSettingsController::class, 'update']);
        Route::get('settings/mail', [OutboundMailSettingsController::class, 'show']);
        Route::get('settings/server-mysql', [ServerMysqlSettingsController::class, 'show'])->middleware('throttle:60,1');
        Route::put('settings/mail', [OutboundMailSettingsController::class, 'update']);
        Route::post('settings/mail/test', [OutboundMailSettingsController::class, 'test']);
        Route::post('settings/mail/diagnostics', [OutboundMailSettingsController::class, 'diagnostics']);
        Route::post('settings/mail/wizard-checks', [OutboundMailSettingsController::class, 'wizardChecks']);
        Route::post('settings/mail/wizard-apply-dns', [OutboundMailSettingsController::class, 'wizardApplyDns']);
        Route::post('settings/mail/setup-stack', [OutboundMailSettingsController::class, 'setupMailStack']);
        Route::get('settings/terminal', [TerminalSettingsController::class, 'show']);
        Route::put('settings/terminal', [TerminalSettingsController::class, 'update']);
        // Merkezi (şirket Drive havuzu) otomatik yedekleme yönetimi.
        Route::get('settings/managed-backup', [ManagedBackupController::class, 'status']);
        Route::put('settings/managed-backup', [ManagedBackupController::class, 'updateSettings']);
        Route::get('settings/managed-backup/auth-url', [ManagedBackupController::class, 'authUrl']);
        Route::post('settings/managed-backup/run-now', [ManagedBackupController::class, 'runNow'])->middleware('throttle:20,1');
        Route::delete('settings/managed-backup/accounts/{backupDestination}', [ManagedBackupController::class, 'disconnect']);
        Route::get('users', [UserController::class, 'index']);
        Route::post('users', [UserController::class, 'store'])->middleware('throttle:20,1');
        Route::get('users/{user}', [UserController::class, 'show']);
        Route::put('users/{user}', [UserController::class, 'update']);
        Route::patch('users/{user}', [UserController::class, 'update']);
        Route::post('users/{user}/suspend', [UserController::class, 'suspend'])->middleware('throttle:30,1');
        Route::post('users/{user}/activate', [UserController::class, 'activate'])->middleware('throttle:30,1');
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('throttle:10,1');
        Route::post('users/{user}/impersonate', [UserController::class, 'impersonate'])->middleware('throttle:15,1');
        Route::apiResource('packages', PackageController::class)->except(['show']);
        Route::get('integrations/whmcs/module-zip', [WhmcsModuleController::class, 'downloadModuleZip']);

        // Faturalama & otomasyon yönetimi
        Route::get('billing/stats', [AdminBillingController::class, 'stats']);
        Route::get('billing/orders', [AdminBillingController::class, 'orders']);
        Route::post('billing/orders/{order}/cancel', [AdminBillingController::class, 'cancelOrder']);
        Route::get('billing/invoices', [AdminBillingController::class, 'invoices']);
        Route::get('billing/invoices/{invoice}', [AdminBillingController::class, 'showInvoice']);
        Route::post('billing/invoices', [AdminBillingController::class, 'storeInvoice'])->middleware('throttle:30,1');
        Route::post('billing/invoices/{invoice}/mark-paid', [AdminBillingController::class, 'markPaid']);
        Route::post('billing/invoices/{invoice}/cancel', [AdminBillingController::class, 'cancelInvoice']);
        Route::get('billing/services', [AdminBillingController::class, 'services']);
        Route::post('billing/services/{subscription}/suspend', [AdminBillingController::class, 'suspendService']);
        Route::post('billing/services/{subscription}/unsuspend', [AdminBillingController::class, 'unsuspendService']);
        Route::post('billing/services/{subscription}/terminate', [AdminBillingController::class, 'terminateService']);
        Route::get('settings/billing', [BillingSettingsController::class, 'show']);
        Route::put('settings/billing', [BillingSettingsController::class, 'update']);
        Route::post('settings/billing/test-registrar', [BillingSettingsController::class, 'testRegistrar']);
    });

    Route::prefix('admin')->middleware(['role:admin', 'require_admin_2fa', 'ability:webserver:read'])->group(function () {
        Route::get('settings/webserver', [WebServerSettingsController::class, 'show']);
        Route::get('settings/webserver/services', [WebServerSettingsController::class, 'services']);
        Route::get('settings/webserver/apache-modules', [WebServerSettingsController::class, 'apacheModules']);
        Route::get('settings/webserver/nginx-config', [WebServerSettingsController::class, 'getNginxConfig']);
    });

    Route::prefix('admin')->middleware(['role:admin', 'require_admin_2fa', 'ability:webserver:write'])->group(function () {
        Route::put('settings/webserver', [WebServerSettingsController::class, 'update']);
        Route::post('settings/webserver/apache-modules/{module}', [WebServerSettingsController::class, 'setApacheModule']);
        Route::put('settings/webserver/nginx-config', [WebServerSettingsController::class, 'updateNginxConfig']);
        Route::post('settings/webserver/panelkafes/apply-all', [WebServerSettingsController::class, 'applyPanelKafesAll']);
        Route::post('settings/webserver/panelkafes/apply', [WebServerSettingsController::class, 'applyPanelKafesSite']);
    });

    Route::prefix('admin')->middleware(['role:admin', 'require_admin_2fa', 'ability:php:read'])->group(function () {
        Route::get('settings/php/versions', [PhpSettingsController::class, 'versions']);
        Route::get('settings/php/{version}/ini', [PhpSettingsController::class, 'ini']);
        Route::get('settings/php/{version}/modules', [PhpSettingsController::class, 'modules']);
    });

    Route::prefix('admin')->middleware(['role:admin', 'require_admin_2fa', 'ability:php:write'])->group(function () {
        Route::put('settings/php/{version}/ini', [PhpSettingsController::class, 'updateIni']);
        Route::patch('settings/php/{version}/modules', [PhpSettingsController::class, 'updateModules']);
        Route::post('settings/php/sync-nginx-upload-limit', [PhpSettingsController::class, 'syncNginxUploadLimit']);
        Route::get('settings/php/sync-nginx-upload-limit/{runId}', [PhpSettingsController::class, 'syncNginxUploadLimitStatus']);
    });

    Route::middleware('role:reseller|admin|vendor_admin')->prefix('reseller')->group(function () {
        Route::middleware('ability:reseller:users')->group(function () {
            Route::get('users', [UserController::class, 'index']);
            Route::post('users', [UserController::class, 'store'])->middleware('throttle:20,1');
        });
        Route::middleware('ability:reseller:packages')->get('packages', [PackageController::class, 'resellerIndex']);
        Route::middleware('ability:reseller:roles')->group(function () {
            Route::get('abilities/registry', [ResellerRoleController::class, 'abilityRegistry']);
            Route::get('roles', [ResellerRoleController::class, 'index']);
            Route::post('roles', [ResellerRoleController::class, 'store'])->middleware('throttle:20,1');
            Route::put('roles/{role}', [ResellerRoleController::class, 'update'])->middleware('throttle:20,1');
            Route::delete('roles/{role}', [ResellerRoleController::class, 'destroy']);
        });
        Route::middleware('ability:reseller:white_label')->group(function () {
            Route::get('white-label', [ResellerWhiteLabelController::class, 'show']);
            Route::post('white-label', [ResellerWhiteLabelController::class, 'update'])->middleware('throttle:10,1');
        });
    });

    if ((bool) config('panelze.vendor_enabled', false)) {
        Route::prefix('vendor')
            ->middleware(['vendor_host', 'role:vendor_admin|vendor_support|vendor_finance|vendor_devops', 'require_admin_2fa', 'throttle:vendor-api'])
            ->group(function () {
                Route::middleware('ability:vendor:read')->group(function () {
                    Route::get('tenants', [VendorTenantController::class, 'index']);
                    Route::get('plans', [VendorPlanController::class, 'index']);
                    Route::get('features', [VendorFeatureController::class, 'index']);
                    Route::get('licenses', [VendorLicenseController::class, 'index']);
                    Route::get('nodes', [VendorNodeController::class, 'index']);
                    Route::get('ops/customers/{tenant}', [VendorOpsController::class, 'customer360']);
                    Route::get('ops/licenses/{license}/timeline', [VendorOpsController::class, 'licenseTimeline']);
                });
                Route::middleware('ability:vendor:write')->group(function () {
                    Route::post('tenants', [VendorTenantController::class, 'store']);
                    Route::post('plans', [VendorPlanController::class, 'store']);
                    Route::post('plans/{plan}/features', [VendorPlanController::class, 'setFeature']);
                    Route::post('features', [VendorFeatureController::class, 'store']);
                    Route::post('licenses', [VendorLicenseController::class, 'store']);
                    Route::post('licenses/{license}/status', [VendorLicenseController::class, 'setStatus']);
                });
                Route::middleware('ability:vendor:billing')->group(function () {
                    Route::get('billing/subscriptions', [VendorBillingController::class, 'subscriptions']);
                    Route::post('billing/subscriptions', [VendorBillingController::class, 'upsertSubscription']);
                    Route::get('billing/invoices', [VendorBillingController::class, 'invoices']);
                    Route::get('billing/payments', [VendorBillingController::class, 'payments']);
                });
                Route::middleware('ability:vendor:support')->group(function () {
                    Route::get('support/tickets', [VendorSupportController::class, 'index']);
                    Route::post('support/tickets', [VendorSupportController::class, 'store']);
                    Route::get('support/tickets/{ticket}', [VendorSupportController::class, 'show']);
                    Route::post('support/tickets/{ticket}/status', [VendorSupportController::class, 'setStatus']);
                    Route::post('support/tickets/{ticket}/messages', [VendorSupportController::class, 'addMessage']);
                });
                Route::middleware('ability:vendor:audit')->group(function () {
                    Route::get('security/audit', [VendorSecurityController::class, 'auditFeed']);
                    Route::get('security/audit/export', [VendorSecurityController::class, 'auditExport']);
                    Route::get('security/siem', [VendorSecurityController::class, 'siemConfig']);
                    Route::post('security/siem', [VendorSecurityController::class, 'saveSiemConfig']);
                    Route::post('security/siem/test', [VendorSecurityController::class, 'testSiem']);
                });
            });
    }
});

Route::post('billing/webhook', [BillingController::class, 'webhook'])
    ->middleware('throttle:webhooks');
Route::post('billing/paytr/callback', PaytrCallbackController::class)
    ->middleware('throttle:webhooks');
Route::match(['get', 'post'], 'billing/iyzico/callback', IyzicoCallbackController::class)
    ->middleware('throttle:webhooks');
Route::post('deployment/webhook/{domain}', [DeploymentController::class, 'webhook'])
    ->middleware(['throttle:webhooks', 'throttle:deploy-run']);
if ((bool) config('panelze.vendor_enabled', false)) {
    Route::post('vendor/license/verify', [VendorLicenseController::class, 'verify'])->middleware('throttle:vendor-node');
    Route::post('vendor/node/activate', [VendorNodeController::class, 'activate'])->middleware('throttle:vendor-node');
    Route::post('vendor/node/heartbeat', [VendorNodeController::class, 'heartbeat'])->middleware('throttle:vendor-node');
    Route::post('vendor/billing/webhook', [VendorBillingController::class, 'webhook'])->middleware('throttle:webhooks');
}

Route::get('health', fn () => response()->json([
    'status' => 'ok',
    'panel' => 'panelze',
    'time' => now()->toIso8601String(),
]));

Route::prefix('integrations/store')
    ->middleware(['store.integration', 'throttle:store-integration'])
    ->group(function () {
        Route::get('test', [StoreIntegrationController::class, 'test']);
        Route::get('packages', [StoreIntegrationController::class, 'packages']);
        Route::get('domains/tlds', [StoreIntegrationController::class, 'domainTlds']);
        Route::get('domains/check', [StoreIntegrationController::class, 'domainCheck']);
        Route::get('fulfill/status', [StoreIntegrationController::class, 'fulfillStatus']);
        Route::post('fulfill', [StoreIntegrationController::class, 'fulfill']);
        Route::post('domains/registered', [StoreIntegrationController::class, 'markDomainRegistered']);

        Route::post('customer/link', [StoreCustomerController::class, 'linkByEmail']);
        Route::get('customer/summary', [StoreCustomerController::class, 'summary']);
        Route::get('customer/domains', [StoreCustomerController::class, 'domains']);
        Route::get('customer/hosting', [StoreCustomerController::class, 'hosting']);
        Route::get('customer/invoices', [StoreCustomerController::class, 'invoices']);
        Route::get('customer/invoices/{invoiceId}', [StoreCustomerController::class, 'invoiceShow']);
        Route::post('customer/invoices/{invoiceId}/pay', [StoreCustomerController::class, 'invoicePay']);
        Route::get('customer/profile', [StoreCustomerController::class, 'profile']);
        Route::patch('customer/profile', [StoreCustomerController::class, 'updateProfile']);
        Route::post('customer/password', [StoreCustomerController::class, 'updatePassword']);
        Route::post('customer/domains/transfers', [StoreCustomerController::class, 'requestTransfer']);
        Route::patch('customer/domains/registrations/{registrationId}', [StoreCustomerController::class, 'updateRegistration']);
        Route::post('customer/ownership/transfer', [StoreCustomerController::class, 'transferOwnership']);
        Route::post('customer/panel-sso', [StoreCustomerController::class, 'panelSso']);

        Route::post('settings/sync', [StoreSettingsSyncController::class, 'sync']);
    });

Route::prefix('integrations/whmcs')
    ->middleware(['whmcs.integration', 'throttle:whmcs-integration'])
    ->group(function () {
        Route::get('test', [WhmcsProvisioningController::class, 'test']);
        Route::get('packages', [WhmcsProvisioningController::class, 'packages']);
        Route::post('provision', [WhmcsProvisioningController::class, 'provision']);
        Route::post('suspend', [WhmcsProvisioningController::class, 'suspend']);
        Route::post('unsuspend', [WhmcsProvisioningController::class, 'unsuspend']);
        Route::post('terminate', [WhmcsProvisioningController::class, 'terminate']);
        Route::post('change-password', [WhmcsProvisioningController::class, 'changePassword']);
        Route::post('change-package', [WhmcsProvisioningController::class, 'changePackage']);
        Route::post('change-domain', [WhmcsProvisioningController::class, 'changeDomain']);
        Route::post('service/renew', [WhmcsProvisioningController::class, 'serviceRenew']);
        Route::post('site/update', [WhmcsProvisioningController::class, 'updateSite']);
        Route::get('usage/accounts', [WhmcsResourcesController::class, 'usageAccounts']);
        Route::get('usage/domain', [WhmcsResourcesController::class, 'usageDomain']);
        Route::post('email/create', [WhmcsResourcesController::class, 'emailCreate']);
        Route::post('email/delete', [WhmcsResourcesController::class, 'emailDelete']);
        Route::post('ftp/create', [WhmcsResourcesController::class, 'ftpCreate']);
        Route::post('ftp/delete', [WhmcsResourcesController::class, 'ftpDelete']);
        Route::post('database/create', [WhmcsResourcesController::class, 'databaseCreate']);
        Route::post('database/delete', [WhmcsResourcesController::class, 'databaseDelete']);
        Route::get('cron/list', [WhmcsResourcesController::class, 'cronList']);
        Route::post('cron/create', [WhmcsResourcesController::class, 'cronCreate']);
        Route::post('cron/delete', [WhmcsResourcesController::class, 'cronDelete']);
        Route::post('sso/mint', [WhmcsResourcesController::class, 'ssoMint']);
        Route::post('sso/mint-admin', [WhmcsResourcesController::class, 'ssoMintAdmin']);
        Route::get('dns/list', [WhmcsResourcesController::class, 'dnsList']);
        Route::post('dns/create', [WhmcsResourcesController::class, 'dnsCreate']);
        Route::post('dns/import', [WhmcsResourcesController::class, 'dnsImport']);
        Route::post('dns/import-zone', [WhmcsResourcesController::class, 'dnsImportZone']);
        Route::post('dns/delete', [WhmcsResourcesController::class, 'dnsDelete']);
        Route::post('email/forwarder/create', [WhmcsResourcesController::class, 'emailForwarderCreate']);
        Route::post('email/forwarder/delete', [WhmcsResourcesController::class, 'emailForwarderDelete']);
        Route::post('database/rotate-password', [WhmcsResourcesController::class, 'databaseRotatePassword']);
        Route::post('ssl/issue', [WhmcsResourcesController::class, 'sslIssue']);
        Route::post('ssl/renew', [WhmcsResourcesController::class, 'sslRenew']);
        Route::post('backup/queue', [WhmcsResourcesController::class, 'backupQueue']);
    });
