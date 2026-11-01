import { useEffect, useMemo, useState } from 'react'
import { NavLink, useLocation, useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { useThemeStore } from '../../store/themeStore'
import { useUiModeStore } from '../../store/uiModeStore'
import { useAuthStore } from '../../store/authStore'
import { tokenHasAbility } from '../../lib/abilities'
import { useBranding } from '../../hooks/useBranding'
import { safeBrandingImageUrl } from '../../lib/urlSafety'
import {
  LayoutDashboard,
  Globe,
  Database,
  Mail,
  FolderOpen,
  Shield,
  Clock,
  HardDrive,
  Activity,
  Lock,
  Settings,
  Users,
  Package,
  Download,
  ChevronLeft,
  ChevronRight,
  Server,
  Network,
  Gauge,
  KeyRound,
  X,
  TerminalSquare,
  Layers,
  Tags,
  ServerCog,
  FileCode,
  FileText,
  ChevronDown,
  Rocket,
  Sparkles,
  Store,
  Palette,
  Cpu,
  ArrowRightLeft,
} from 'lucide-react'
import toast from 'react-hot-toast'
import { useProFeatures } from '../../hooks/useProFeatures'

type NavLeaf = {
  path: string
  icon: typeof LayoutDashboard
  label: string
  ability: string | null
  /** Landing modül anahtarı (Pro kilidi) */
  proModule?: string
}

type NavGroup = {
  id: string
  title: string
  items: NavLeaf[]
}

export default function Sidebar() {
  const { t } = useTranslation()
  const {
    sidebarCollapsed,
    toggleSidebar,
    mobileSidebarOpen,
    closeMobileSidebar,
  } = useThemeStore()
  const location = useLocation()
  const navigate = useNavigate()
  const user = useAuthStore((s) => s.user)
  const { isModuleEnabled } = useProFeatures()
  const mode = useUiModeStore((s) => s.mode)
  const abilities = user?.abilities
  const isAdmin = user?.roles?.some((r) => r.name === 'admin')
  const canWebserverSettings =
    tokenHasAbility(abilities, 'webserver:read') || tokenHasAbility(abilities, 'webserver:write')
  const canPhpSettings =
    tokenHasAbility(abilities, 'php:read') || tokenHasAbility(abilities, 'php:write')
  const { data: branding } = useBranding()
  const isAdminSection = location.pathname.startsWith('/admin')
  const headerLogoRaw =
    isAdminSection && branding?.logo_admin_url
      ? branding.logo_admin_url
      : branding?.logo_customer_url || branding?.logo_admin_url
  const headerLogo = safeBrandingImageUrl(headerLogoRaw)

  const navOk = (ability: string | null, path: string) => {
    if (path === '/reseller/branding') {
      return tokenHasAbility(abilities, 'reseller:white_label')
    }
    if (path === '/reseller') {
      return (
        tokenHasAbility(abilities, 'reseller:users') ||
        tokenHasAbility(abilities, 'reseller:packages') ||
        tokenHasAbility(abilities, 'reseller:roles')
      )
    }
    return tokenHasAbility(abilities, ability)
  }


  const customerGroups: NavGroup[] = [
    {
      id: 'overview',
      title: 'nav.group_overview',
      items: [{ path: '/dashboard', icon: LayoutDashboard, label: 'nav.dashboard', ability: 'dashboard:read' }],
    },
    {
      id: 'hosting',
      title: 'nav.group_hosting',
      items: [
        { path: '/domains', icon: Globe, label: 'nav.domains', ability: 'domains:read' },
        { path: '/dns', icon: Network, label: 'nav.dns', ability: 'dns:read' },
        { path: '/redirects', icon: ArrowRightLeft, label: 'nav.redirects', ability: 'domains:read' },
        { path: '/databases', icon: Database, label: 'nav.databases', ability: 'databases:read' },
        { path: '/email', icon: Mail, label: 'nav.email', ability: 'email:read' },
        { path: '/files', icon: FolderOpen, label: 'nav.files', ability: 'files:read' },
        { path: '/ftp', icon: HardDrive, label: 'nav.ftp', ability: 'ftp:read' },
        { path: '/ssl', icon: Lock, label: 'nav.ssl', ability: 'ssl:read' },
      ],
    },
    {
      id: 'operations',
      title: 'nav.group_operations',
      items: [
        { path: '/backups', icon: Shield, label: 'nav.backups', ability: 'backups:read' },
        { path: '/cron', icon: Clock, label: 'nav.cron', ability: 'cron:read' },
        { path: '/monitoring', icon: Activity, label: 'nav.monitoring', ability: 'monitoring:read', proModule: 'monitoring_advanced' },
        { path: '/security', icon: Shield, label: 'nav.security', ability: 'security:read' },
        { path: '/installer', icon: Download, label: 'nav.installer', ability: 'installer:read' },
        { path: '/node-apps', icon: Cpu, label: 'nav.node_apps', ability: 'tools:run' },
        { path: '/deploy', icon: Rocket, label: 'nav.deploy', ability: 'tools:run' },
        { path: '/plugins', icon: Store, label: 'nav.plugins_store', ability: 'dashboard:read' },
        { path: '/ai-advisor', icon: Sparkles, label: 'nav.ai_advisor', ability: 'dashboard:read', proModule: 'ai_advisor' },
        { path: '/curious', icon: Gauge, label: 'nav.curious', ability: 'curious:read', proModule: 'curious_tools' },
      ],
    },
    {
      id: 'account',
      title: 'nav.group_account',
      items: [
        { path: '/reseller', icon: Users, label: 'nav.reseller', ability: '__reseller__' },
        { path: '/reseller/branding', icon: Palette, label: 'nav.reseller_branding', ability: 'reseller:white_label' },
        { path: '/settings', icon: Settings, label: 'nav.settings', ability: null },
      ],
    },
  ]

  const adminSubmenus = [
    {
      id: 'admin-server',
      title: 'nav.admin_server',
      icon: Gauge,
      items: [
        { path: '/admin/system', icon: Gauge, label: 'nav.system', allow: isAdmin },
        { path: '/admin/server-settings', icon: Network, label: 'nav.server_settings', allow: isAdmin },
        { path: '/admin/webserver', icon: ServerCog, label: 'nav.webserver_settings', allow: isAdmin || canWebserverSettings },
        { path: '/admin/php-settings', icon: FileCode, label: 'nav.php_settings', allow: isAdmin || canPhpSettings },
        { path: '/admin/logs', icon: FileText, label: 'nav.logs', allow: isAdmin },
        { path: '/admin/stack', icon: Layers, label: 'nav.stack', allow: isAdmin },
        { path: '/admin/terminal', icon: TerminalSquare, label: 'nav.terminal', allow: isAdmin },
        { path: '/admin/dns-settings', icon: Globe, label: 'nav.dns_settings', allow: isAdmin },
        { path: '/admin/managed-backup', icon: HardDrive, label: 'nav.managed_backup', allow: isAdmin },
      ],
    },
    {
      id: 'admin-access',
      title: 'nav.admin_access',
      icon: Users,
      items: [
        { path: '/admin/users', icon: Users, label: 'nav.users', allow: isAdmin },
        { path: '/admin/roles', icon: Tags, label: 'nav.roles', allow: isAdmin },
        { path: '/admin/packages', icon: Package, label: 'nav.packages', allow: isAdmin },
        { path: '/admin/license', icon: KeyRound, label: 'nav.license', allow: isAdmin },
      ],
    },
  ]

  /** Kolay modda bile yönetici için görünür kalsın (nameserver / sunucu IP). */
  const easyAdminKeepPaths = new Set(['/admin/dns-settings', '/admin/server-settings'])

  /** Kolay modda «Erişim ve yönetim» altındaki öğeler (destek, faturalama, kullanıcılar) gizlenmez. */
  const easyAdminAccessKeepAll = true

  const [openAdminMenus, setOpenAdminMenus] = useState<Record<string, boolean>>({
    'admin-server': true,
    'admin-access': true,
  })

  const easyHiddenPaths = new Set([
    '/dns',
    '/redirects',
    '/ftp',
    '/monitoring',
    '/security',
    '/cron',
    '/node-apps',
    '/deploy',
    '/plugins',
    '/ai-advisor',
    '/curious',
    '/billing',
    '/invoices',
    '/domain-portfolio',
    '/reseller',
    '/reseller/branding',
  ])

  const visibleCustomerGroups = customerGroups
    .map((g) => {
      let items = g.items.filter((item) => {
        if (!navOk(item.ability, item.path)) return false
        if (mode === 'easy' && easyHiddenPaths.has(item.path)) return false
        return true
      })
      return { ...g, items }
    })
    .filter((g) => g.items.length > 0)

  const visibleAdminMenus = useMemo(
    () =>
      adminSubmenus
        .map((m) => ({ ...m, items: m.items.filter((i) => i.allow) }))
        .map((m) => ({
          ...m,
          items:
            mode === 'easy' && ! (easyAdminAccessKeepAll && m.id === 'admin-access')
              ? m.items.filter((i) => easyAdminKeepPaths.has(i.path))
              : m.items,
        }))
        .filter((m) => m.items.length > 0),
    [isAdmin, canWebserverSettings, canPhpSettings, mode],
  )

  useEffect(() => {
    closeMobileSidebar()
  }, [location.pathname, closeMobileSidebar])

  useEffect(() => {
    const path = location.pathname
    if (!path.startsWith('/admin')) return
    setOpenAdminMenus((prev) => {
      let next = prev
      for (const menu of adminSubmenus) {
        const active = menu.items.some((item) => path === item.path || path.startsWith(`${item.path}/`))
        if (active && !prev[menu.id]) {
          if (next === prev) next = { ...prev }
          next[menu.id] = true
        }
      }
      return next
    })
  }, [location.pathname])

  useEffect(() => {
    const mq = window.matchMedia('(min-width: 768px)')
    const onChange = () => {
      if (mq.matches) {
        closeMobileSidebar()
      }
    }
    mq.addEventListener('change', onChange)
    return () => mq.removeEventListener('change', onChange)
  }, [closeMobileSidebar])

  const showNavText = !sidebarCollapsed
  const hideWhenCollapsed = showNavText ? '' : 'max-md:inline md:hidden'
  const userInitials = (user?.name ?? '?')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((p) => p[0]?.toLocaleUpperCase())
    .join('')
  const itemClass = (isActive: boolean, locked = false) =>
    `group relative flex items-center gap-3 rounded-xl px-3 py-2 text-[13.5px] font-medium transition-all duration-150 ${
      locked
        ? 'text-ink-600 hover:bg-white/[0.03]'
        : isActive
          ? 'bg-gradient-to-r from-primary-500/20 via-primary-500/10 to-transparent text-white shadow-[inset_0_0_0_1px_rgba(249,115,22,0.18)]'
          : 'text-gray-400 hover:bg-white/[0.05] hover:text-gray-100'
    } ${showNavText ? '' : 'md:justify-center md:px-0'}`
  const activeBar = (
    <span className="absolute left-0 top-1/2 h-5 w-[3px] -translate-y-1/2 rounded-r-full bg-primary-500 shadow-[0_0_12px_rgba(249,115,22,0.8)]" />
  )

  return (
    <aside
      className={`fixed left-0 top-0 z-50 flex h-screen min-h-0 w-64 flex-col border-r border-white/5 bg-ink-950 text-gray-300 transition-transform duration-300 ease-out md:transition-[width] ${
        mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'
      } md:translate-x-0 ${sidebarCollapsed ? 'md:w-16' : 'md:w-64'}`}
    >
      <div
        className="pointer-events-none absolute inset-x-0 top-0 h-48 bg-[radial-gradient(120%_80%_at_0%_0%,rgba(249,115,22,0.16),transparent_60%)]"
        aria-hidden
      />
      <div className="relative flex h-16 w-full flex-none shrink-0 items-center gap-2 border-b border-white/5 px-3 sm:px-4">
        <div
          className={`min-w-0 flex-1 items-center gap-2.5 ${
            sidebarCollapsed ? 'hidden max-md:flex' : 'flex'
          }`}
        >
          {headerLogo ? (
            <img src={headerLogo} alt="" className="h-8 max-w-[150px] object-contain" />
          ) : (
            <>
              <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-primary-400 to-primary-600 text-white shadow-glow">
                <Server className="h-[18px] w-[18px]" strokeWidth={2.2} />
              </span>
              <span className="min-w-0 leading-tight">
                <span className="block truncate text-[15px] font-bold tracking-tight text-white">{t('app.name')}</span>
                <span className="block truncate text-[11px] font-medium text-gray-500">{t('theme.brand_tagline')}</span>
              </span>
            </>
          )}
        </div>
        <div className={`flex shrink-0 items-center gap-1 ${sidebarCollapsed ? 'md:mx-auto' : 'ml-auto'}`}>
          <button
            type="button"
            onClick={closeMobileSidebar}
            className="rounded-lg p-1.5 text-gray-400 hover:bg-white/10 hover:text-white md:hidden"
            aria-label={t('nav.close_menu')}
          >
            <X className="h-5 w-5" />
          </button>
          <button
            type="button"
            onClick={toggleSidebar}
            className="hidden rounded-lg p-1.5 text-gray-500 hover:bg-white/10 hover:text-white md:inline-flex"
            aria-label={t('nav.toggle_sidebar')}
          >
            {sidebarCollapsed ? (
              <ChevronRight className="h-5 w-5" />
            ) : (
              <ChevronLeft className="h-5 w-5" />
            )}
          </button>
        </div>
      </div>

      <nav className="relative min-h-0 flex-1 overflow-y-auto overflow-x-hidden px-3 py-4 overscroll-contain [scrollbar-width:thin]">
        {visibleCustomerGroups.map((group) => (
          <div key={group.id} className="mb-5">
            <p
              className={`mb-1.5 px-3 text-[10.5px] font-semibold uppercase tracking-[0.12em] text-gray-500 ${
                showNavText ? '' : 'max-md:block md:hidden'
              }`}
            >
              {t(group.title)}
            </p>
            <ul className="space-y-0.5">
              {group.items.map((item) => {
                const proLocked =
                  item.proModule != null && item.proModule !== '' && !isModuleEnabled(item.proModule)
                return (
                  <li key={item.path}>
                    <NavLink
                      to={item.path}
                      onClick={(e) => {
                        if (!proLocked) return
                        e.preventDefault()
                        if (isAdmin) {
                          navigate('/admin/license', { state: { from: item.path } })
                        } else {
                          toast.error(t('license.pro_locked_nav'))
                        }
                      }}
                      className={({ isActive }) => itemClass(isActive && !proLocked, proLocked)}
                      title={proLocked ? t('license.pro_locked_nav') : t(item.label)}
                    >
                      {({ isActive }) => (
                        <>
                          {isActive && !proLocked && activeBar}
                          <item.icon
                            className={`h-[18px] w-[18px] flex-shrink-0 transition-colors ${
                              isActive && !proLocked ? 'text-primary-400' : 'text-gray-500 group-hover:text-gray-300'
                            }`}
                          />
                          <span className={`flex-1 truncate ${hideWhenCollapsed}`}>{t(item.label)}</span>
                          {proLocked && (
                            <span className="inline-flex items-center gap-0.5 rounded-md bg-amber-400/10 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-amber-300 ring-1 ring-inset ring-amber-400/20">
                              <Lock className="h-3 w-3" />
                              {showNavText ? t('license.pro_badge') : ''}
                            </span>
                          )}
                        </>
                      )}
                    </NavLink>
                  </li>
                )
              })}
            </ul>
          </div>
        ))}

        {visibleAdminMenus.length > 0 && (
          <>
            <div className="mx-3 my-4 border-t border-white/5" />
            <p
              className={`mb-2 flex items-center gap-2 px-3 text-[10.5px] font-semibold uppercase tracking-[0.12em] text-gray-500 ${
                showNavText ? '' : 'max-md:flex md:hidden'
              }`}
            >
              <span className="h-1.5 w-1.5 rounded-full bg-primary-500" />
              {t('theme.admin_area')}
            </p>
            <div className="space-y-1.5">
              {visibleAdminMenus.map((menu) => {
                const isOpen = openAdminMenus[menu.id] ?? menu.id === 'admin-server'
                return (
                  <div key={menu.id} className="overflow-hidden rounded-xl bg-white/[0.02] ring-1 ring-inset ring-white/5">
                    <button
                      type="button"
                      onClick={() =>
                        setOpenAdminMenus((prev) => ({ ...prev, [menu.id]: !isOpen }))
                      }
                      className={`flex w-full items-center justify-between gap-3 px-3 py-2.5 text-[13px] font-semibold text-gray-300 hover:bg-white/[0.04] hover:text-white ${
                        showNavText ? '' : 'md:justify-center md:px-0'
                      }`}
                      title={t(menu.title)}
                    >
                      <span className="flex items-center gap-2.5">
                        <menu.icon className="h-4 w-4 text-gray-500" />
                        <span className={hideWhenCollapsed}>{t(menu.title)}</span>
                      </span>
                      <ChevronDown
                        className={`h-4 w-4 text-gray-500 transition-transform ${isOpen ? 'rotate-180' : ''} ${
                          showNavText ? '' : 'md:hidden'
                        }`}
                      />
                    </button>
                    {isOpen && (
                      <ul className="space-y-0.5 px-1.5 pb-1.5">
                        {menu.items.map((item) => (
                          <li key={item.path}>
                            <NavLink
                              to={item.path}
                              className={({ isActive }) => itemClass(isActive)}
                              title={t(item.label)}
                            >
                              {({ isActive }) => (
                                <>
                                  {isActive && activeBar}
                                  <item.icon
                                    className={`h-4 w-4 flex-shrink-0 ${
                                      isActive ? 'text-primary-400' : 'text-gray-500 group-hover:text-gray-300'
                                    }`}
                                  />
                                  <span className={`truncate ${hideWhenCollapsed}`}>{t(item.label)}</span>
                                </>
                              )}
                            </NavLink>
                          </li>
                        ))}
                      </ul>
                    )}
                  </div>
                )
              })}
            </div>
          </>
        )}
      </nav>

      {user && (
        <div className="relative flex-none border-t border-white/5 p-3">
          <NavLink
            to="/settings"
            className={`flex items-center gap-3 rounded-xl p-2 transition-colors hover:bg-white/[0.05] ${
              showNavText ? '' : 'md:justify-center md:p-1'
            }`}
            title={user.name}
          >
            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-amber-300 to-primary-600 text-[13px] font-bold text-white ring-2 ring-ink-800">
              {userInitials || '?'}
            </span>
            <span className={`min-w-0 flex-1 leading-tight ${hideWhenCollapsed}`}>
              <span className="block truncate text-[13px] font-semibold text-gray-100">{user.name}</span>
              <span className="block truncate text-[11px] text-gray-500">
                {mode === 'easy' ? t('ui_mode.easy') : t('ui_mode.advanced')}
                {user.email ? ` · ${user.email}` : ''}
              </span>
            </span>
            <Settings className={`h-4 w-4 shrink-0 text-gray-500 ${hideWhenCollapsed}`} />
          </NavLink>
        </div>
      )}
    </aside>
  )
}
