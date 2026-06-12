import { Routes, Route, Navigate } from 'react-router-dom'
import { Suspense, lazy, useEffect } from 'react'
import { useTranslation } from 'react-i18next'
import { useAuthStore } from './store/authStore'
import { useThemeStore } from './store/themeStore'
import { useUiModeStore } from './store/uiModeStore'
import Layout from './components/layout/Layout'
import ProFeatureGate from './components/ProFeatureGate'
import LoginPage from './pages/LoginPage'
import DashboardPage from './pages/DashboardPage'
import DnsPage from './pages/DnsPage'
import RedirectsPage from './pages/RedirectsPage'
import SslPage from './pages/SslPage'
import InstallerPage from './pages/InstallerPage'
import NodeAppPage from './pages/NodeAppPage'
import DeployPage from './pages/DeployPage'
import BillingPage from './pages/BillingPage'
import SettingsPage from './pages/SettingsPage'
import ResellerPage from './pages/ResellerPage'
import ResellerBrandingPage from './pages/ResellerBrandingPage'
import OnboardingPage from './pages/OnboardingPage'
import WhmcsSsoBootstrap from './components/WhmcsSsoBootstrap'

const DomainsPage = lazy(() => import('./pages/DomainsPage'))
const DatabasesPage = lazy(() => import('./pages/DatabasesPage'))
const FtpPage = lazy(() => import('./pages/FtpPage'))
const EmailPage = lazy(() => import('./pages/EmailPage'))
const BackupsPage = lazy(() => import('./pages/BackupsPage'))
const GoogleDriveCallbackPage = lazy(() => import('./pages/GoogleDriveCallbackPage'))
const CronPage = lazy(() => import('./pages/CronPage'))
const FileManagerPage = lazy(() => import('./pages/FileManagerPage'))
const MonitoringPage = lazy(() => import('./pages/MonitoringPage'))
const SecurityPage = lazy(() => import('./pages/SecurityPage'))
const TerminalPage = lazy(() => import('./pages/TerminalPage'))
const AdminSystemPage = lazy(() => import('./pages/AdminSystemPage'))
const AdminPhpSettingsPage = lazy(() => import('./pages/AdminPhpSettingsPage'))
const AdminUsersPage = lazy(() => import('./pages/AdminUsersPage'))
const AdminPackagesPage = lazy(() => import('./pages/AdminPackagesPage'))
const AdminWhmcsPage = lazy(() => import('./pages/AdminWhmcsPage'))
const AdminLicensePage = lazy(() => import('./pages/AdminLicensePage'))
const AdminStackPage = lazy(() => import('./pages/AdminStackPage'))
const AdminMailSettingsPage = lazy(() => import('./pages/AdminMailSettingsPage'))
const AdminDnsSettingsPage = lazy(() => import('./pages/AdminDnsSettingsPage'))
const AdminRolesPage = lazy(() => import('./pages/AdminRolesPage'))
const AdminWebServerSettingsPage = lazy(() => import('./pages/AdminWebServerSettingsPage'))
const AdminServerSettingsPage = lazy(() => import('./pages/AdminServerSettingsPage'))
const AdminLogsPage = lazy(() => import('./pages/AdminLogsPage'))
const PluginsStorePage = lazy(() => import('./pages/PluginsStorePage'))
const AiAdvisorPage = lazy(() => import('./pages/AiAdvisorPage'))
const CuriousPage = lazy(() => import('./pages/CuriousPage'))

function PageLoader() {
  const { t } = useTranslation()
  return (
    <div className="flex min-h-[40vh] items-center justify-center text-sm text-gray-500 dark:text-gray-400">
      {t('common.loading')}
    </div>
  )
}

function LazyPage({ children }: { children: React.ReactNode }) {
  return <Suspense fallback={<PageLoader />}>{children}</Suspense>
}

function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated)
  if (!isAuthenticated) return <Navigate to="/login" replace />
  return <>{children}</>
}

function AdvancedRoute({ children }: { children: React.ReactNode }) {
  const { t } = useTranslation()
  const { mode, setMode } = useUiModeStore()
  if (mode === 'advanced') return <>{children}</>
  return (
    <div className="max-w-2xl rounded-xl border border-amber-200 dark:border-amber-900/40 bg-amber-50/80 dark:bg-amber-950/20 p-5">
      <h2 className="text-lg font-semibold text-gray-900 dark:text-white">{t('ui_mode.advanced_required_title')}</h2>
      <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
        {t('ui_mode.advanced_required_desc')}
      </p>
      <button className="btn-primary mt-3" onClick={() => setMode('advanced')}>
        {t('ui_mode.switch_to_advanced')}
      </button>
    </div>
  )
}

function UnknownRoute() {
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated)
  return <Navigate to={isAuthenticated ? '/dashboard' : '/login'} replace />
}

export default function App() {
  const isDark = useThemeStore((s) => s.isDark)

  useEffect(() => {
    if (isDark) {
      document.documentElement.classList.add('dark')
    } else {
      document.documentElement.classList.remove('dark')
    }
  }, [isDark])

  return (
    <>
      <WhmcsSsoBootstrap />
      <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route
        path="/"
        element={
          <ProtectedRoute>
            <Layout />
          </ProtectedRoute>
        }
      >
        <Route index element={<Navigate to="/dashboard" replace />} />
        <Route path="dashboard" element={<DashboardPage />} />
        <Route path="domains" element={<LazyPage><DomainsPage /></LazyPage>} />
        <Route path="dns" element={<DnsPage />} />
        <Route path="redirects" element={<AdvancedRoute><RedirectsPage /></AdvancedRoute>} />
        <Route path="databases" element={<LazyPage><DatabasesPage /></LazyPage>} />
        <Route path="email" element={<LazyPage><EmailPage /></LazyPage>} />
        <Route path="files" element={<LazyPage><FileManagerPage /></LazyPage>} />
        <Route path="ftp" element={<LazyPage><FtpPage /></LazyPage>} />
        <Route path="ssl" element={<SslPage />} />
        <Route path="backups/google-callback" element={<LazyPage><GoogleDriveCallbackPage /></LazyPage>} />
        <Route path="backups" element={<LazyPage><BackupsPage /></LazyPage>} />
        <Route path="cron" element={<AdvancedRoute><LazyPage><CronPage /></LazyPage></AdvancedRoute>} />
        <Route path="monitoring" element={<AdvancedRoute><LazyPage><ProFeatureGate moduleKey="monitoring_advanced"><MonitoringPage /></ProFeatureGate></LazyPage></AdvancedRoute>} />
        <Route path="security" element={<AdvancedRoute><LazyPage><SecurityPage /></LazyPage></AdvancedRoute>} />
        <Route path="installer" element={<InstallerPage />} />
        <Route path="node-apps" element={<AdvancedRoute><NodeAppPage /></AdvancedRoute>} />
        <Route path="deploy" element={<AdvancedRoute><DeployPage /></AdvancedRoute>} />
        <Route path="billing" element={<AdvancedRoute><ProFeatureGate moduleKey="stripe_billing"><BillingPage /></ProFeatureGate></AdvancedRoute>} />
        <Route path="reseller" element={<AdvancedRoute><ResellerPage /></AdvancedRoute>} />
        <Route path="reseller/branding" element={<AdvancedRoute><ResellerBrandingPage /></AdvancedRoute>} />
        <Route path="onboarding" element={<OnboardingPage />} />
        <Route path="ai-advisor" element={<AdvancedRoute><LazyPage><ProFeatureGate moduleKey="ai_advisor"><AiAdvisorPage /></ProFeatureGate></LazyPage></AdvancedRoute>} />
        <Route path="curious" element={<LazyPage><ProFeatureGate moduleKey="curious_tools"><CuriousPage /></ProFeatureGate></LazyPage>} />
        <Route path="plugins" element={<AdvancedRoute><LazyPage><PluginsStorePage /></LazyPage></AdvancedRoute>} />
        <Route path="admin/users" element={<AdvancedRoute><LazyPage><AdminUsersPage /></LazyPage></AdvancedRoute>} />
        <Route path="admin/roles" element={<AdvancedRoute><LazyPage><AdminRolesPage /></LazyPage></AdvancedRoute>} />
        <Route path="admin/packages" element={<AdvancedRoute><LazyPage><AdminPackagesPage /></LazyPage></AdvancedRoute>} />
        <Route path="admin/whmcs" element={<AdvancedRoute><LazyPage><AdminWhmcsPage /></LazyPage></AdvancedRoute>} />
        <Route path="admin/system" element={<AdvancedRoute><LazyPage><AdminSystemPage /></LazyPage></AdvancedRoute>} />
        <Route path="admin/server-settings" element={<AdvancedRoute><LazyPage><AdminServerSettingsPage /></LazyPage></AdvancedRoute>} />
        <Route path="admin/license" element={<AdvancedRoute><LazyPage><AdminLicensePage /></LazyPage></AdvancedRoute>} />
        <Route path="admin/terminal" element={<AdvancedRoute><LazyPage><TerminalPage /></LazyPage></AdvancedRoute>} />
        <Route path="admin/stack" element={<AdvancedRoute><LazyPage><AdminStackPage /></LazyPage></AdvancedRoute>} />
        <Route path="admin/mail-settings" element={<AdvancedRoute><LazyPage><AdminMailSettingsPage /></LazyPage></AdvancedRoute>} />
        <Route path="admin/dns-settings" element={<AdvancedRoute><LazyPage><AdminDnsSettingsPage /></LazyPage></AdvancedRoute>} />
        <Route path="admin/webserver" element={<AdvancedRoute><LazyPage><AdminWebServerSettingsPage /></LazyPage></AdvancedRoute>} />
        <Route path="admin/php-settings" element={<AdvancedRoute><LazyPage><AdminPhpSettingsPage /></LazyPage></AdvancedRoute>} />
        <Route path="admin/logs" element={<AdvancedRoute><LazyPage><AdminLogsPage /></LazyPage></AdvancedRoute>} />
        <Route path="settings" element={<SettingsPage />} />
      </Route>
      <Route path="*" element={<UnknownRoute />} />
    </Routes>
    </>
  )
}
