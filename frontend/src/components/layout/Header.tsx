import { useTranslation } from 'react-i18next'
import { useLocation, useNavigate } from 'react-router-dom'
import { useAuthStore } from '../../store/authStore'
import { useThemeStore } from '../../store/themeStore'
import { useUiModeStore } from '../../store/uiModeStore'
import { useNotificationsStore } from '../../store/notificationsStore'
import { authService } from '../../services/authService'
import { useMutation, useQuery } from '@tanstack/react-query'
import api from '../../services/api'
import toast from 'react-hot-toast'
import { isServerAdminUI } from '../../lib/authRoles'
import {
  Sun,
  Moon,
  LogOut,
  Settings,
  Bell,
  Globe,
  User,
  Menu,
  SlidersHorizontal,
  LayoutGrid,
  ShieldCheck,
  ChevronDown,
  ChevronRight,
  Crown,
  Home,
} from 'lucide-react'
import { useProFeatures } from '../../hooks/useProFeatures'
import { pollWhenVisible } from '../../lib/pollWhenVisible'
import clsx from 'clsx'
import { useEffect, useState } from 'react'

const languages = [
  { code: 'en', name: 'English', flag: '🇬🇧' },
  { code: 'tr', name: 'Türkçe', flag: '🇹🇷' },
  { code: 'de', name: 'Deutsch', flag: '🇩🇪' },
  { code: 'fr', name: 'Français', flag: '🇫🇷' },
  { code: 'es', name: 'Español', flag: '🇪🇸' },
]

const PAGE_TITLES: Array<[string, string]> = [
  ['/admin/server-settings', 'nav.server_settings'],
  ['/admin/php-settings', 'nav.php_settings'],
  ['/admin/managed-backup', 'nav.managed_backup'],
  ['/admin/dns-settings', 'nav.dns_settings'],
  ['/admin/webserver', 'nav.webserver_settings'],
  ['/admin/system', 'nav.system'],
  ['/admin/logs', 'nav.logs'],
  ['/admin/stack', 'nav.stack'],
  ['/admin/terminal', 'nav.terminal'],
  ['/admin/users', 'nav.users'],
  ['/admin/roles', 'nav.roles'],
  ['/admin/packages', 'nav.packages'],
  ['/admin/license', 'nav.license'],
  ['/reseller/branding', 'nav.reseller_branding'],
  ['/reseller', 'nav.reseller'],
  ['/dashboard', 'nav.dashboard'],
  ['/domains', 'nav.domains'],
  ['/dns', 'nav.dns'],
  ['/redirects', 'nav.redirects'],
  ['/databases', 'nav.databases'],
  ['/email', 'nav.email'],
  ['/files', 'nav.files'],
  ['/ftp', 'nav.ftp'],
  ['/ssl', 'nav.ssl'],
  ['/backups', 'nav.backups'],
  ['/cron', 'nav.cron'],
  ['/monitoring', 'nav.monitoring'],
  ['/security', 'nav.security'],
  ['/installer', 'nav.installer'],
  ['/node-apps', 'nav.node_apps'],
  ['/deploy', 'nav.deploy'],
  ['/plugins', 'nav.plugins_store'],
  ['/ai-advisor', 'nav.ai_advisor'],
  ['/curious', 'nav.curious'],
  ['/settings', 'nav.settings'],
]

export default function Header() {
  const { t, i18n } = useTranslation()
  const navigate = useNavigate()
  const location = useLocation()
  const { user, logout: logoutStore } = useAuthStore()
  const requiresPasswordChange = user?.force_password_change === true
  const serverUI = isServerAdminUI(user)
  const { licensePro, licenseValid, planCode, licenseExpiresAt } = useProFeatures()
  const showProBadge = licensePro && licenseValid
  const proBadgeTitle = planCode
    ? t('license.pro_active_title_plan', { plan: planCode })
    : t('license.pro_active_title')
  const { isDark, toggleTheme, openMobileSidebar } = useThemeStore()
  const { mode, setMode, advancedTipsSeen, markAdvancedTipsSeen } = useUiModeStore()
  const [showAdvancedTips, setShowAdvancedTips] = useState(false)
  const [showNotifMenu, setShowNotifMenu] = useState(false)
  const [showProfileMenu, setShowProfileMenu] = useState(false)
  const [showLanguageSubmenu, setShowLanguageSubmenu] = useState(false)
  const [panelCheckRunning, setPanelCheckRunning] = useState(false)
  const { items, markAllRead, clear, remove, mergeFromServer } = useNotificationsStore()

  const [levelFilter, setLevelFilter] = useState<'all' | 'error' | 'info' | 'success'>('all')
  const [unreadOnly, setUnreadOnly] = useState(false)
  const isSafeInternalPath = (p: string): boolean => /^\/[a-zA-Z0-9/_-]*$/.test(p)
  const feedQ = useQuery({
    queryKey: ['notifications-feed'],
    queryFn: async () => (await api.get('/notifications/feed')).data as { items: Array<{ id: string; title: string; message?: string; path?: string; level: 'info' | 'success' | 'error'; created_at?: string }> },
    refetchInterval: () => pollWhenVisible(60_000),
    staleTime: 45_000,
    enabled: !requiresPasswordChange,
    retry: false,
  })
  useEffect(() => {
    if (feedQ.data?.items) {
      mergeFromServer(feedQ.data.items)
    }
  }, [feedQ.data, mergeFromServer])

  const dismissM = useMutation({
    mutationFn: async (payload: { ids?: string[]; clear_all?: boolean }) =>
      api.post('/notifications/dismiss', payload),
    onSuccess: () => {
      void feedQ.refetch()
    },
  })

  const handleDismissOne = (id: string) => {
    remove(id)
    dismissM.mutate({ ids: [id] })
  }

  const handleClearAll = () => {
    const ids = items.map((i) => i.id)
    clear(ids)
    dismissM.mutate({ ids, clear_all: true })
  }

  const unread = items.filter((i) => !i.read).length
  const visibleItems = items.filter((i) => {
    if (levelFilter !== 'all' && i.level !== levelFilter) return false
    if (unreadOnly && i.read) return false
    return true
  })

  const handleLogout = async () => {
    try {
      await authService.logout()
    } catch {
      // ignore
    }
    logoutStore()
    navigate('/login')
  }

  const formatPanelCheck = (item: { id?: string; message?: string }) => {
    const id = item.id ?? 'unknown'
    const label = t(`panel_self_heal.check_labels.${id}`, { defaultValue: id })
    return item.message ? `${label}: ${item.message}` : label
  }

  const panelRepairM = useMutation({
    mutationFn: async () => {
      const { data } = await api.post('/system/panel/repair')
      return data as {
        ok?: boolean
        summary?: { failed?: number }
        message?: string
        checks?: Array<{ ok?: boolean; id?: string; message?: string }>
        repair_steps?: Array<{ ok?: boolean; id?: string; message?: string }>
      }
    },
    onSuccess: (data) => {
      const failed = Number(data?.summary?.failed ?? 0)
      if (failed === 0) {
        toast.success(t('panel_self_heal.repair_success'))
      } else {
        const checks = data?.checks ?? []
        const failedSteps = (data?.repair_steps ?? []).filter((s) => !s.ok)
        const remaining = checks
          .filter((c) => !c.ok)
          .slice(0, 3)
          .map(formatPanelCheck)
          .join('\n')
        const stepHint = failedSteps
          .slice(0, 2)
          .map(formatPanelCheck)
          .join('\n')
        const detail = [remaining, stepHint].filter(Boolean).join('\n')
        toast((detail || data?.message || t('panel_self_heal.repair_partial')) as string, {
          icon: '⚠️',
          duration: 10000,
        })
      }
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string; error?: string } } }
      toast.error(ax.response?.data?.message || ax.response?.data?.error || t('panel_self_heal.check_failed'))
    },
    onSettled: () => setPanelCheckRunning(false),
  })

  const panelCheckM = useMutation({
    mutationFn: async () => {
      const { data } = await api.get('/system/panel/health')
      return data as {
        ok?: boolean
        summary?: { failed?: number }
      }
    },
    onSuccess: (data) => {
      const failed = Number(data?.summary?.failed ?? 0)
      if (failed <= 0) {
        toast.success(t('panel_self_heal.check_ok'))
        setPanelCheckRunning(false)
        return
      }
      const confirmed = window.confirm(t('panel_self_heal.confirm_repair', { count: failed }))
      if (!confirmed) {
        setPanelCheckRunning(false)
        return
      }
      panelRepairM.mutate()
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string; error?: string } } }
      toast.error(ax.response?.data?.message || ax.response?.data?.error || t('panel_self_heal.check_failed'))
      setPanelCheckRunning(false)
    },
  })

  const changeLanguage = (code: string) => {
    i18n.changeLanguage(code)
    setShowLanguageSubmenu(false)
    setShowProfileMenu(false)
  }
  const toggleUiMode = () => {
    const next = mode === 'easy' ? 'advanced' : 'easy'
    setMode(next)
    if (next === 'easy') {
      setShowAdvancedTips(false)
    }
    toast.success(
      next === 'easy' ? t('ui_mode.switched_easy') : t('ui_mode.switched_advanced'),
      { duration: 1800 },
    )
  }

  useEffect(() => {
    if (mode === 'advanced' && !advancedTipsSeen) {
      setShowAdvancedTips(true)
    }
  }, [mode, advancedTipsSeen])

  const titleKey = PAGE_TITLES.find(([p]) => location.pathname === p || location.pathname.startsWith(`${p}/`))?.[1]
  const pageTitle = titleKey ? t(titleKey) : t('app.name')
  const inAdmin = location.pathname.startsWith('/admin')
  const userInitials = (user?.name ?? '?')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((p) => p[0]?.toLocaleUpperCase())
    .join('')
  const iconBtn =
    'relative inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white'

  return (
    <header className="relative z-20 flex h-16 shrink-0 items-center gap-3 border-b border-gray-200/80 bg-white px-3 dark:border-panel-border dark:bg-panel-bg sm:px-6">
      <button
        type="button"
        onClick={openMobileSidebar}
        className={clsx(iconBtn, 'md:hidden')}
        aria-label={t('nav.open_menu')}
      >
        <Menu className="h-5 w-5" />
      </button>

      <div className="min-w-0 flex-1">
        <nav className="hidden items-center gap-1.5 text-[11px] font-medium text-gray-400 sm:flex" aria-label="breadcrumb">
          <button type="button" onClick={() => navigate('/dashboard')} className="inline-flex items-center gap-1 hover:text-primary-600 dark:hover:text-primary-400">
            <Home className="h-3 w-3" />
            {t('app.name')}
          </button>
          {inAdmin && (
            <>
              <ChevronRight className="h-3 w-3" />
              <span>{t('theme.admin_area')}</span>
            </>
          )}
          {titleKey && titleKey !== 'nav.dashboard' && (
            <>
              <ChevronRight className="h-3 w-3" />
              <span className="text-gray-500 dark:text-gray-400">{pageTitle}</span>
            </>
          )}
        </nav>
        <p className="truncate text-[15px] font-semibold leading-tight text-gray-900 dark:text-white sm:text-base">{pageTitle}</p>
      </div>

      <div className="flex shrink-0 items-center gap-1 sm:gap-1.5">
        <div className="hidden items-center rounded-xl bg-gray-100 p-0.5 ring-1 ring-inset ring-gray-200 dark:bg-white/5 dark:ring-white/10 lg:inline-flex">
          {(['easy', 'advanced'] as const).map((m) => (
            <button
              key={m}
              type="button"
              onClick={() => { if (mode !== m) toggleUiMode() }}
              className={clsx(
                'inline-flex items-center gap-1.5 rounded-[10px] px-2.5 py-1.5 text-xs font-semibold transition-all',
                mode === m
                  ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-800 dark:text-white'
                  : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200',
              )}
              title={m === 'easy' ? t('ui_mode.switch_to_easy') : t('ui_mode.switch_to_advanced')}
            >
              {m === 'easy' ? <LayoutGrid className="h-3.5 w-3.5" /> : <SlidersHorizontal className="h-3.5 w-3.5" />}
              {m === 'easy' ? t('ui_mode.easy') : t('ui_mode.advanced')}
            </button>
          ))}
        </div>
        <button
          type="button"
          onClick={toggleUiMode}
          className={clsx(iconBtn, 'lg:hidden', mode === 'easy' ? 'text-emerald-600 dark:text-emerald-400' : 'text-secondary-600 dark:text-secondary-400')}
          title={mode === 'easy' ? t('ui_mode.switch_to_advanced') : t('ui_mode.switch_to_easy')}
          aria-label={mode === 'easy' ? t('ui_mode.easy') : t('ui_mode.advanced')}
        >
          {mode === 'easy' ? <LayoutGrid className="h-[18px] w-[18px]" /> : <SlidersHorizontal className="h-[18px] w-[18px]" />}
        </button>

        {showAdvancedTips && (
          <>
            <div className="absolute right-4 top-16 z-50 hidden w-80 rounded-2xl border border-gray-200 bg-white p-4 shadow-lift dark:border-panel-border dark:bg-panel-card md:block">
              <p className="text-sm font-semibold text-gray-900 dark:text-white">{t('ui_mode.advanced_tips_title')}</p>
              <ul className="mt-2 list-disc space-y-1 pl-4 text-xs text-gray-600 dark:text-gray-300">
                <li>{t('ui_mode.tip_1')}</li>
                <li>{t('ui_mode.tip_2')}</li>
                <li>{t('ui_mode.tip_3')}</li>
              </ul>
              <div className="mt-3 flex justify-end">
                <button
                  type="button"
                  className="btn-secondary py-1.5 text-xs"
                  onClick={() => {
                    markAdvancedTipsSeen()
                    setShowAdvancedTips(false)
                  }}
                >
                  {t('common.ok')}
                </button>
              </div>
            </div>

            <div className="fixed inset-0 z-50 bg-black/40 md:hidden" onClick={() => { markAdvancedTipsSeen(); setShowAdvancedTips(false) }}>
              <div
                className="absolute inset-x-0 bottom-0 rounded-t-2xl border-t border-gray-200 bg-white p-4 shadow-2xl dark:border-panel-border dark:bg-panel-card"
                onClick={(e) => e.stopPropagation()}
              >
                <div className="mx-auto mb-3 h-1.5 w-12 rounded-full bg-gray-300 dark:bg-gray-700" />
                <p className="text-sm font-semibold text-gray-900 dark:text-white">{t('ui_mode.advanced_tips_title')}</p>
                <ul className="mt-2 list-disc space-y-1 pl-4 text-xs text-gray-600 dark:text-gray-300">
                  <li>{t('ui_mode.tip_1')}</li>
                  <li>{t('ui_mode.tip_2')}</li>
                  <li>{t('ui_mode.tip_3')}</li>
                </ul>
                <button
                  type="button"
                  className="btn-primary mt-4 w-full"
                  onClick={() => {
                    markAdvancedTipsSeen()
                    setShowAdvancedTips(false)
                  }}
                >
                  {t('common.ok')}
                </button>
              </div>
            </div>
          </>
        )}

        {showProBadge && (
          <span
            className="hidden shrink-0 items-center gap-1 rounded-full bg-gradient-to-r from-amber-500 to-primary-600 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-white shadow-md shadow-primary-600/25 ring-1 ring-white/20 sm:inline-flex"
            title={
              licenseExpiresAt
                ? `${proBadgeTitle} · ${t('license.pro_expires', { date: new Date(licenseExpiresAt).toLocaleDateString() })}`
                : proBadgeTitle
            }
          >
            <Crown className="h-3.5 w-3.5 shrink-0" strokeWidth={2.5} />
            <span>{t('license.pro_badge')}</span>
          </span>
        )}

        <button
          onClick={toggleTheme}
          className={iconBtn}
          title={isDark ? t('settings.light_mode') : t('settings.dark_mode')}
        >
          {isDark ? <Sun className="h-[18px] w-[18px]" /> : <Moon className="h-[18px] w-[18px]" />}
        </button>

        {serverUI && (
          <button
            type="button"
            onClick={() => {
              if (panelCheckRunning || panelCheckM.isPending || panelRepairM.isPending) return
              setPanelCheckRunning(true)
              panelCheckM.mutate()
            }}
            className={clsx(iconBtn, 'hidden md:inline-flex')}
            title={t('panel_self_heal.title')}
            disabled={panelCheckRunning || panelCheckM.isPending || panelRepairM.isPending}
          >
            <ShieldCheck
              className={clsx(
                'h-[18px] w-[18px]',
                (panelCheckRunning || panelCheckM.isPending || panelRepairM.isPending) && 'animate-pulse',
              )}
            />
          </button>
        )}

        <div className="relative shrink-0">
          <button
            onClick={() => {
              const next = !showNotifMenu
              setShowNotifMenu(next)
              if (next) markAllRead()
            }}
            className={iconBtn}
            title="Bildirimler"
          >
            <Bell className="h-[18px] w-[18px]" />
            {unread > 0 && (
              <span className="absolute right-1.5 top-1.5 flex h-2.5 w-2.5">
                <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary-400 opacity-60" />
                <span className="relative inline-flex h-2.5 w-2.5 rounded-full bg-primary-500 ring-2 ring-white dark:ring-panel-bg" />
              </span>
            )}
          </button>
          {showNotifMenu && (
            <>
              <div
                className="fixed inset-0 z-40 bg-black/30 md:bg-transparent"
                aria-hidden
                onClick={() => setShowNotifMenu(false)}
              />
              <div className="fixed inset-x-3 bottom-3 z-50 max-h-[70vh] w-auto overflow-y-auto rounded-2xl border border-gray-200 bg-white shadow-lift dark:border-panel-border dark:bg-panel-card md:absolute md:inset-auto md:bottom-auto md:right-0 md:mt-2 md:max-h-[440px] md:w-96">
              <div className="sticky top-0 flex items-center justify-between border-b border-gray-100 bg-white/95 px-4 py-3 backdrop-blur dark:border-panel-border dark:bg-panel-card/95">
                <span className="text-sm font-semibold text-gray-900 dark:text-gray-100">Bildirimler</span>
                <div className="flex items-center gap-2">
                  <select
                    className="rounded-lg border border-gray-200 bg-transparent px-1.5 py-0.5 text-xs dark:border-gray-700"
                    value={levelFilter}
                    onChange={(e) => setLevelFilter(e.target.value as 'all' | 'error' | 'info' | 'success')}
                  >
                    <option value="all">Tümü</option>
                    <option value="error">Hata</option>
                    <option value="info">Bilgi</option>
                    <option value="success">Başarılı</option>
                  </select>
                  <label className="inline-flex items-center gap-1 text-[10px] text-gray-500">
                    <input type="checkbox" checked={unreadOnly} onChange={(e) => setUnreadOnly(e.target.checked)} />
                    Okunmamış
                  </label>
                  <button type="button" onClick={handleClearAll} className="text-xs text-gray-500 hover:text-red-600">Temizle</button>
                </div>
              </div>
              <div className="space-y-2 p-2">
                {visibleItems.length === 0 && (
                  <div className="px-3 py-8 text-center">
                    <Bell className="mx-auto h-6 w-6 text-gray-300 dark:text-gray-600" />
                    <p className="mt-2 text-xs text-gray-500">Yeni bildirim yok.</p>
                  </div>
                )}
                {visibleItems.map((n) => (
                  <div key={n.id} className="rounded-xl border border-gray-100 p-2.5 hover:bg-gray-50 dark:border-panel-border dark:hover:bg-white/[0.03]">
                    <div className="flex items-start gap-2">
                      <span className={`mt-1 inline-block h-2 w-2 rounded-full ${
                        n.level === 'error' ? 'bg-red-500' : n.level === 'success' ? 'bg-emerald-500' : 'bg-secondary-500'
                      }`} />
                      <div className="min-w-0 flex-1">
                        <button
                          type="button"
                          className="text-left text-xs font-medium text-gray-800 hover:underline dark:text-gray-100"
                          onClick={() => {
                            if (n.path && isSafeInternalPath(n.path)) {
                              navigate(n.path)
                              setShowNotifMenu(false)
                            }
                          }}
                        >
                          {n.title}
                        </button>
                        {n.message && <p className="break-words text-xs text-gray-500">{n.message}</p>}
                        <p className="mt-1 text-[10px] text-gray-400">{new Date(n.createdAt).toLocaleString()}</p>
                      </div>
                      <button type="button" onClick={() => handleDismissOne(n.id)} className="text-[10px] text-gray-400 hover:text-red-500">x</button>
                    </div>
                  </div>
                ))}
              </div>
              </div>
            </>
          )}
        </div>

        <div className="relative ml-1 shrink-0 border-l border-gray-200 pl-2 dark:border-panel-border sm:pl-3">
          <button
            onClick={() => setShowProfileMenu((v) => !v)}
            className="flex items-center gap-2 rounded-xl p-1 pr-1.5 transition-colors hover:bg-gray-100 dark:hover:bg-white/5"
            title={user?.name ?? t('nav.settings')}
          >
            <span className="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-amber-300 to-primary-600 text-xs font-bold text-white">
              {userInitials || <User className="h-4 w-4" />}
            </span>
            <span className="hidden max-w-[140px] text-left leading-tight xl:block">
              <span className="block truncate text-xs font-semibold text-gray-900 dark:text-white">{user?.name}</span>
              <span className="block truncate text-[11px] text-gray-500 dark:text-gray-400">{user?.email}</span>
            </span>
            <ChevronDown className="hidden h-3.5 w-3.5 text-gray-400 xl:block" />
          </button>
          {showProfileMenu && (
            <>
              <div
                className="fixed inset-0 z-40 bg-black/30 md:bg-transparent"
                onClick={() => {
                  setShowProfileMenu(false)
                  setShowLanguageSubmenu(false)
                }}
              />
              <div className="fixed inset-x-3 bottom-3 z-50 w-auto overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lift dark:border-panel-border dark:bg-panel-card md:absolute md:inset-auto md:bottom-auto md:right-0 md:mt-2 md:w-60">
                <div className="flex items-center gap-3 border-b border-gray-100 px-4 py-3 dark:border-panel-border">
                  <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-amber-300 to-primary-600 text-xs font-bold text-white">
                    {userInitials || '?'}
                  </span>
                  <span className="min-w-0 leading-tight">
                    <span className="block truncate text-sm font-semibold text-gray-900 dark:text-white">{user?.name}</span>
                    <span className="block truncate text-xs text-gray-500 dark:text-gray-400">{user?.email}</span>
                  </span>
                </div>
                {showProBadge && (
                  <div className="flex items-center gap-2 border-b border-amber-200/80 bg-gradient-to-r from-amber-50 to-primary-50 px-4 py-2.5 dark:border-amber-900/40 dark:from-amber-950/40 dark:to-primary-950/30">
                    <span className="inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-amber-500 to-primary-600 px-2 py-0.5 text-[10px] font-bold uppercase text-white">
                      <Crown className="h-3 w-3" />
                      {t('license.pro_badge')}
                    </span>
                    <span className="min-w-0 truncate text-xs text-gray-600 dark:text-gray-300">
                      {planCode ? t('license.pro_plan_label', { plan: planCode }) : t('license.pro_active_title')}
                    </span>
                  </div>
                )}
                <div className="p-1.5">
                  <button
                    type="button"
                    className="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-white/5"
                    onClick={() => {
                      setShowProfileMenu(false)
                      navigate('/settings')
                    }}
                  >
                    <Settings className="h-4 w-4 text-gray-400" />
                    {t('nav.settings')}
                  </button>
                  <button
                    type="button"
                    className="flex w-full items-center justify-between rounded-lg px-2.5 py-2 text-left text-sm text-gray-700 transition-colors hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-white/5"
                    onClick={() => setShowLanguageSubmenu((v) => !v)}
                  >
                    <span className="inline-flex items-center gap-2.5">
                      <Globe className="h-4 w-4 text-gray-400" />
                      {t('settings.language')}
                    </span>
                    {showLanguageSubmenu ? <ChevronDown className="h-3.5 w-3.5" /> : <ChevronRight className="h-3.5 w-3.5" />}
                  </button>
                  {showLanguageSubmenu && (
                    <div className="mb-1 ml-7 space-y-0.5">
                      {languages.map((lang) => (
                        <button
                          key={lang.code}
                          type="button"
                          onClick={() => changeLanguage(lang.code)}
                          className={clsx(
                            'flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-left text-xs transition-colors',
                            i18n.language === lang.code
                              ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300'
                              : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5',
                          )}
                        >
                          <span>{lang.flag}</span>
                          <span>{lang.name}</span>
                        </button>
                      ))}
                    </div>
                  )}
                </div>
                <div className="border-t border-gray-100 p-1.5 dark:border-panel-border">
                  <button
                    type="button"
                    className="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                    onClick={() => {
                      setShowProfileMenu(false)
                      void handleLogout()
                    }}
                  >
                    <LogOut className="h-4 w-4" />
                    {t('auth.logout')}
                  </button>
                </div>
              </div>
            </>
          )}
        </div>
      </div>
    </header>
  )
}
