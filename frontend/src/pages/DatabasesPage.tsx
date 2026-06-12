import { useCallback, useEffect, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api, { apiBaseUrl } from '../services/api'
import { useAuthStore } from '../store/authStore'
import { tokenHasAbility } from '../lib/abilities'
import i18n from '../i18n'
import {
  Database,
  Plus,
  Search,
  Trash2,
  ExternalLink,
  KeyRound,
  Pencil,
  Copy,
  Download,
  Upload,
  Info,
  Sparkles,
  Crown,
} from 'lucide-react'
import toast from 'react-hot-toast'
import { safeExternalHttpUrl } from '../lib/urlSafety'
import { isHostingSuperAdmin } from '../lib/authRoles'

type ServerMysqlApi = {
  provision: {
    enabled: boolean
    host: string
    port: number
    username: string
    password: string
  }
  panel_app_database: {
    host: string
    port: number
    database: string
    username: string
    password: string
  }
  phpmyadmin_url: string
  hints: {
    provision_use: string
    root_socket: string
    ssh_secret_file: string
  }
}

type DbRow = {
  id: number
  name: string
  type: string
  username: string
  host: string
  grant_host?: string | null
  size_mb?: number | null
  status: string
}

const GRANT_HOST_OPTIONS = ['localhost', '127.0.0.1', '%'] as const

function grantHostSelectOptions(current?: string | null): string[] {
  const g = current?.trim()
  const base: string[] = [...GRANT_HOST_OPTIONS]
  if (g && !base.includes(g)) {
    base.push(g)
  }
  return base
}

function legacyCopyText(value: string): boolean {
  try {
    const ta = document.createElement('textarea')
    ta.value = value
    ta.setAttribute('readonly', 'true')
    ta.style.position = 'fixed'
    ta.style.top = '-9999px'
    ta.style.left = '-9999px'
    document.body.appendChild(ta)
    ta.select()
    ta.setSelectionRange(0, ta.value.length)
    const ok = document.execCommand('copy')
    document.body.removeChild(ta)
    return ok
  } catch {
    return false
  }
}

/** HTTP / eski tarayıcı: navigator.clipboard bazen yok; execCommand fallback. */
async function copyPlaintextWithToasts(text: string, messages: { ok: string; fail: string }): Promise<void> {
  try {
    const clip = typeof navigator !== 'undefined' ? navigator.clipboard : undefined
    if (clip?.writeText && window.isSecureContext) {
      await clip.writeText(text)
    } else {
      const ok = legacyCopyText(text)
      if (!ok) throw new Error('copy-failed')
    }
    toast.success(messages.ok)
  } catch {
    const ok = legacyCopyText(text)
    if (ok) {
      toast.success(messages.ok)
      return
    }
    toast.error(messages.fail)
  }
}

export default function DatabasesPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const qc = useQueryClient()
  const user = useAuthStore((s) => s.user)
  const isSuperAdmin = isHostingSuperAdmin(user)
  const isAdmin = user?.roles?.some((r) => r.name === 'admin') ?? false
  const abilities = useAuthStore((s) => s.user?.abilities)
  const canImportDb = tokenHasAbility(abilities, 'databases:write')
  const [search, setSearch] = useState('')
  const [showAdd, setShowAdd] = useState(false)
  const [createType, setCreateType] = useState('mysql')
  const [editCredentialsDb, setEditCredentialsDb] = useState<DbRow | null>(null)
  const [importDb, setImportDb] = useState<DbRow | null>(null)
  const [passwordReveal, setPasswordReveal] = useState<{
    value: string
    source: 'rotate' | 'edit'
    expiresAt: number
  } | null>(null)
  const [nowTs, setNowTs] = useState<number>(Date.now())

  const [showServerProvPassword, setShowServerProvPassword] = useState(false)
  const [showServerPanelPassword, setShowServerPanelPassword] = useState(false)
  const [selectedDbId, setSelectedDbId] = useState<number | null>(null)

  const databasesQ = useQuery({
    queryKey: ['databases', 'paginated'],
    queryFn: async () => (await api.get('/databases')).data,
  })

  const uiLinksQ = useQuery({
    queryKey: ['config-ui-links'],
    queryFn: async () =>
      (await api.get('/config/ui-links')).data as {
        phpmyadmin_url?: string
        adminer_url?: string
        features?: { phpmyadmin_auto_login?: boolean }
      },
  })

  const serverMysqlQ = useQuery({
    queryKey: ['admin', 'settings', 'server-mysql'],
    enabled: isSuperAdmin,
    queryFn: async () => (await api.get('/admin/settings/server-mysql')).data as ServerMysqlApi,
  })

  const domainsQ = useQuery({
    queryKey: ['domains', 'paginated'],
    queryFn: async () => (await api.get('/domains')).data,
  })

  const phpmyadminUrl = uiLinksQ.data?.phpmyadmin_url?.trim() ?? ''
  const adminerUrl = uiLinksQ.data?.adminer_url?.trim() ?? ''
  const pmaAutoLogin = uiLinksQ.data?.features?.phpmyadmin_auto_login === true

  const createM = useMutation({
    mutationFn: async (payload: {
      name: string
      type: string
      domain_id?: number
      grant_host?: string
    }) => {
      const { data } = await api.post('/databases', payload)
      return data as { password_plain?: string }
    },
    onSuccess: (data) => {
      const msg = data.password_plain
        ? `${t('databases.created')} — ${t('databases.password_once')}: ${data.password_plain}`
        : t('databases.created')
      toast.success(msg, { duration: data.password_plain ? 25_000 : 4000 })
      qc.invalidateQueries({ queryKey: ['databases'] })
      setShowAdd(false)
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const rotateM = useMutation({
    mutationFn: async (id: number) => {
      const { data } = await api.post(`/databases/${id}/rotate-password`)
      return data as { password_plain?: string }
    },
    onSuccess: (data) => {
      toast.success(t('databases.password_rotated'))
      if (data.password_plain) {
        setPasswordReveal({
          value: data.password_plain,
          source: 'rotate',
          expiresAt: Date.now() + 30_000,
        })
        void copyPlaintextWithToasts(data.password_plain, {
          ok: t('databases.password_copied'),
          fail: t('databases.copy_failed'),
        })
      }
      qc.invalidateQueries({ queryKey: ['databases'] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const pmaSsoM = useMutation({
    mutationFn: async (id: number) => {
      const { data } = await api.post(`/databases/${id}/phpmyadmin-login`)
      return data as { signon_url?: string; message?: string }
    },
    onSuccess: (data) => {
      const raw = String(data.signon_url ?? '').trim()
      const target = safeExternalHttpUrl(raw) ?? raw
      if (!target) {
        toast.error(t('databases.ui_url_missing'))
        return
      }
      window.open(target, '_blank', 'noopener,noreferrer')
      toast.success(data.message ?? t('databases.phpmyadmin_sso_opening'))
    },
    onError: (err: unknown) => {
      const ax = err as {
        response?: { data?: { message?: string; code?: string } }
      }
      const msg = ax.response?.data?.message ?? String(err)
      toast.error(msg)
      if (ax.response?.data?.code === 'pro_license_required' && isAdmin) {
        navigate('/admin/license')
      }
    },
  })

  const patchCredentialsM = useMutation({
    mutationFn: async (payload: {
      id: number
      password?: string
      grant_host?: string
    }) => {
      const { data } = await api.patch(`/databases/${payload.id}`, payload)
      return data as { password_plain?: string; sync_reminder?: string }
    },
    onSuccess: (data) => {
      toast.success(t('databases.updated'))
      if (data.sync_reminder?.trim()) {
        toast(data.sync_reminder.trim(), { duration: 14_000 })
      }
      if (data.password_plain) {
        setPasswordReveal({
          value: data.password_plain,
          source: 'edit',
          expiresAt: Date.now() + 30_000,
        })
        void copyPlaintextWithToasts(data.password_plain, {
          ok: t('databases.password_copied'),
          fail: t('databases.copy_failed'),
        })
      }
      qc.invalidateQueries({ queryKey: ['databases'] })
      setEditCredentialsDb(null)
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const deleteM = useMutation({
    mutationFn: async (id: number) => api.delete(`/databases/${id}`),
    onSuccess: () => {
      toast.success(t('databases.deleted'))
      qc.invalidateQueries({ queryKey: ['databases'] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  type ImportMeta = {
    confirm_phrase: string
    max_import_mb: number
    mysql_tools_enabled: boolean
    postgres_tools_enabled: boolean
  }

  type ImportStatusPayload = {
    import_id: number
    status: string
    progress: number
    phase: string
    message?: string | null
    error_message?: string | null
  }

  type ImportUiProgress = {
    mode: 'upload' | 'server'
    uploadPct: number
    serverPct: number
    phase: string
    detail: string
  }

  const [importBusy, setImportBusy] = useState(false)
  const [importUi, setImportUi] = useState<ImportUiProgress | null>(null)
  const importPollCancel = useRef(false)

  const importMetaQ = useQuery({
    queryKey: ['databases-import-meta'],
    queryFn: async () => {
      const { data } = await api.get<ImportMeta>('/databases/import-meta')
      return data
    },
    enabled: !!importDb,
    staleTime: 60_000,
  })

  const normalizeImportConfirm = (value: string) =>
    value
      .trim()
      .toLocaleUpperCase('tr-TR')
      .replace(/İ/g, 'I')
      .replace(/Ü/g, 'U')
      .replace(/Ö/g, 'O')
      .replace(/Ş/g, 'S')
      .replace(/Ğ/g, 'G')
      .replace(/Ç/g, 'C')

  const importConfirmMatches = (given: string, expected: string) => {
    const g = normalizeImportConfirm(given)
    const phrases = [
      normalizeImportConfirm(expected),
      normalizeImportConfirm(t('databases.import_confirm_expected')),
      'TUMVERISILINECEK',
      'REPLACEALLDATA',
      'PANELZE_REPLACE_DB',
    ]
    return phrases.includes(g)
  }

  const phaseLabel = useCallback(
    (phase: string, fallback?: string | null) => {
      const key = `databases.import_phase_${phase}` as const
      const translated = t(key)
      if (translated !== key) return translated
      return fallback?.trim() || phase
    },
    [t],
  )

  const combinedImportPct = (ui: ImportUiProgress) =>
    ui.mode === 'upload' ? Math.min(40, ui.uploadPct) : 40 + Math.round(ui.serverPct * 0.6)

  const pollImportUntilDone = async (dbId: number, importId: number) => {
    importPollCancel.current = false
    for (let i = 0; i < 7200; i++) {
      if (importPollCancel.current) return
      await new Promise((r) => setTimeout(r, 1000))
      const { data } = await api.get<ImportStatusPayload>(`/databases/${dbId}/import/${importId}`)
      const pct = Math.max(0, Math.min(100, Number(data.progress) || 0))
      setImportUi({
        mode: 'server',
        uploadPct: 40,
        serverPct: pct,
        phase: data.phase || data.status,
        detail: phaseLabel(data.phase, data.message),
      })
      if (data.status === 'completed') {
        return data
      }
      if (data.status === 'failed') {
        throw new Error(data.error_message || data.message || t('databases.import_failed'))
      }
    }
    throw new Error(t('databases.import_failed'))
  }

  const runDatabaseImport = async (db: DbRow, file: File, confirmation: string) => {
    setImportBusy(true)
    setImportUi({
      mode: 'upload',
      uploadPct: 0,
      serverPct: 0,
      phase: 'upload',
      detail: t('databases.import_progress_upload'),
    })
    try {
      const fd = new FormData()
      fd.append('sql_file', file)
      fd.append('confirmation', confirmation)
      const sizeHint = file.size > 0 ? file.size : 0
      const { data } = await api.post<{ import_id?: number; message?: string }>(
        `/databases/${db.id}/import`,
        fd,
        {
          timeout: 600_000,
          onUploadProgress: (ev) => {
            const total = ev.total && ev.total > 0 ? ev.total : sizeHint
            const pct = total > 0 ? Math.min(40, Math.round((ev.loaded / total) * 40)) : 5
            setImportUi((prev) =>
              prev
                ? {
                    ...prev,
                    mode: 'upload',
                    uploadPct: pct,
                    detail: t('databases.import_progress_upload'),
                  }
                : prev,
            )
          },
        },
      )
      const importId = data.import_id
      if (!importId) {
        toast.success(data.message ?? t('databases.imported'))
        qc.invalidateQueries({ queryKey: ['databases'] })
        setImportDb(null)
        return
      }
      setImportUi({
        mode: 'server',
        uploadPct: 40,
        serverPct: 2,
        phase: 'queued',
        detail: phaseLabel('queued'),
      })
      const final = await pollImportUntilDone(db.id, importId)
      toast.success(final?.message ?? t('databases.imported'))
      qc.invalidateQueries({ queryKey: ['databases'] })
      setImportDb(null)
    } catch (err: unknown) {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? (err instanceof Error ? err.message : String(err)))
    } finally {
      setImportBusy(false)
      setImportUi(null)
    }
  }

  useEffect(() => {
    importPollCancel.current = true
    if (!importDb) {
      setImportUi(null)
      setImportBusy(false)
    }
  }, [importDb])

  const parseApiErrorText = (txt: string, fallback: string): string => {
    const trimmed = txt.trim()
    if (!trimmed) return fallback
    if (trimmed.startsWith('{')) {
      try {
        const j = JSON.parse(trimmed) as { message?: string }
        if (j.message) return String(j.message)
      } catch {
        /* ignore */
      }
    }
    const title = trimmed.match(/<title>([^<]+)<\/title>/i)?.[1]?.trim()
    if (title && !/^server error$/i.test(title)) return title
    return trimmed.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 280) || fallback
  }

  const runExport = async (db: DbRow) => {
    const token = useAuthStore.getState().token
    const locale = (i18n.language || 'en').split('-')[0]
    const pending = toast.loading(t('databases.export_progress'))
    try {
      const res = await fetch(`${apiBaseUrl}/databases/${db.id}/export`, {
        headers: {
          ...(token ? { Authorization: `Bearer ${token}` } : {}),
          Accept: 'application/sql, application/json, */*',
          'X-Locale': locale,
        },
      })
      if (!res.ok) {
        const ct = res.headers.get('content-type') || ''
        let msg = t('databases.export_failed')
        if (ct.includes('json')) {
          const j = (await res.json().catch(() => ({}))) as { message?: string }
          if (j.message) msg = String(j.message)
        } else {
          const txt = await res.text().catch(() => '')
          msg = parseApiErrorText(txt, msg)
        }
        toast.error(msg, { id: pending })
        return
      }
      const blob = await res.blob()
      const cd = res.headers.get('content-disposition')
      let fn = `${db.name.replace(/[^\w.-]+/g, '_')}.sql`
      const m = cd?.match(/filename\*?=(?:UTF-8'')?["']?([^"';]+)/i) ?? cd?.match(/filename="([^"]+)"/i)
      if (m?.[1]) fn = decodeURIComponent(m[1].trim())
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = fn
      a.click()
      URL.revokeObjectURL(url)
      toast.success(t('databases.export_done'), { id: pending })
    } catch {
      toast.error(t('databases.export_failed'), { id: pending })
    }
  }

  const list: DbRow[] = databasesQ.data?.data ?? []
  const total = (databasesQ.data?.total as number | undefined) ?? list.length
  const domainOptions: { id: number; name: string }[] = domainsQ.data?.data ?? []
  const filtered = list.filter((db) => db.name.toLowerCase().includes(search.toLowerCase()))
  const selectedDb = selectedDbId != null ? filtered.find((d) => d.id === selectedDbId) : null

  const showPmaProRequired = () => {
    toast.error(t('databases.phpmyadmin_sso_pro_hint'), { duration: 7000 })
    if (isAdmin) {
      navigate('/admin/license')
    }
  }

  const openPhpMyAdminSso = (db: DbRow) => {
    if (!pmaAutoLogin) {
      showPmaProRequired()
      return
    }
    pmaSsoM.mutate(db.id)
  }

  const openDbWebUi = (db: DbRow) => {
    const php = safeExternalHttpUrl(phpmyadminUrl)
    const adm = safeExternalHttpUrl(adminerUrl)
    if (db.type === 'mysql') {
      if (!php) {
        toast.error(
          t('databases.ui_url_missing') +
            ' PHPMYADMIN_URL (.env → panelze.ui.phpmyadmin_url)',
        )
        return
      }
      const u = new URL(php)
      u.searchParams.set('db', db.name)
      u.searchParams.set('pma_username', db.username)
      window.open(u.toString(), '_blank', 'noopener,noreferrer')
      return
    }
    if (db.type === 'postgresql') {
      if (!adm) {
        toast.error(t('databases.ui_url_missing') + ' ADMINER_URL')
        return
      }
      const u = new URL(adm)
      u.searchParams.set('username', db.username)
      u.searchParams.set('db', db.name)
      window.open(u.toString(), '_blank', 'noopener,noreferrer')
      return
    }
    toast.error(t('databases.no_web_ui_for_type'))
  }

  const copyText = (text: string, okMsg: string) =>
    copyPlaintextWithToasts(text, { ok: okMsg, fail: t('databases.copy_failed') })

  useEffect(() => {
    if (!passwordReveal) return
    const tick = window.setInterval(() => {
      const now = Date.now()
      setNowTs(now)
      if (now >= passwordReveal.expiresAt) {
        setPasswordReveal(null)
      }
    }, 1000)
    return () => window.clearInterval(tick)
  }, [passwordReveal])

  const secondsLeft = passwordReveal
    ? Math.max(0, Math.ceil((passwordReveal.expiresAt - nowTs) / 1000))
    : 0

  const pmaMaintenanceUrl =
    serverMysqlQ.data &&
    (safeExternalHttpUrl(serverMysqlQ.data.phpmyadmin_url?.trim()) || safeExternalHttpUrl(phpmyadminUrl))

  return (
    <div className="space-y-6">
      <div
        className={
          isSuperAdmin
            ? 'grid grid-cols-1 items-start gap-6 xl:grid-cols-[minmax(0,1fr)_17.5rem]'
            : ''
        }
      >
        <div className="min-w-0 space-y-6">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                {t('databases.title')}
              </h1>
              <p className="text-gray-500 dark:text-gray-400 mt-1">
                {total} {t('nav.databases').toLowerCase()}
              </p>
            </div>
            <button
              type="button"
              className="btn-primary flex items-center gap-2"
              onClick={() => {
                setCreateType('mysql')
                setShowAdd(true)
              }}
            >
              <Plus className="h-4 w-4" />
              {t('databases.add')}
            </button>
          </div>

          <div className="card">
            <div className="p-4 border-b border-gray-200 dark:border-panel-border space-y-3">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                <input
                  type="text"
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  placeholder={t('common.search')}
                  className="input pl-10"
                />
              </div>
              {selectedDb?.type === 'mysql' && phpmyadminUrl && (
                <div className="flex flex-wrap items-center gap-2 rounded-lg border border-violet-200 dark:border-violet-800/60 bg-violet-50/80 dark:bg-violet-950/30 px-3 py-2.5">
                  <p className="text-sm text-violet-900 dark:text-violet-100 flex-1 min-w-[12rem]">
                    {t('databases.phpmyadmin_sso_selected_bar', { name: selectedDb.name })}
                  </p>
                  {pmaAutoLogin ? (
                    <button
                      type="button"
                      className="inline-flex items-center gap-1.5 rounded-lg bg-violet-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-violet-700 disabled:opacity-60"
                      onClick={() => openPhpMyAdminSso(selectedDb)}
                      disabled={pmaSsoM.isPending}
                    >
                      <Sparkles className="h-4 w-4" />
                      {pmaSsoM.isPending
                        ? t('databases.phpmyadmin_sso_opening')
                        : t('databases.phpmyadmin_sso_btn')}
                    </button>
                  ) : (
                    <button
                      type="button"
                      className="inline-flex items-center gap-1.5 rounded-lg border border-violet-300 dark:border-violet-700 px-3 py-1.5 text-sm font-medium text-violet-800 dark:text-violet-200 hover:bg-violet-100/80 dark:hover:bg-violet-900/40"
                      onClick={showPmaProRequired}
                    >
                      <Crown className="h-4 w-4" />
                      {t('databases.phpmyadmin_sso_pro_badge')}
                    </button>
                  )}
                  <button
                    type="button"
                    className="text-xs text-gray-600 dark:text-gray-400 hover:underline"
                    title={t('databases.phpmyadmin_manual')}
                    onClick={() => openDbWebUi(selectedDb)}
                  >
                    {t('databases.phpmyadmin_manual')}
                  </button>
                </div>
              )}
            </div>

            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b border-gray-200 dark:border-panel-border">
                    <th className="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      {t('databases.name')}
                    </th>
                    <th className="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      {t('databases.type')}
                    </th>
                    <th className="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      {t('databases.username')}
                    </th>
                    <th className="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      {t('databases.grant_host')}
                    </th>
                    <th className="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      {t('databases.size')}
                    </th>
                    <th className="text-right px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      {t('common.actions')}
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200 dark:divide-panel-border">
                  {databasesQ.isLoading && (
                    <tr>
                      <td colSpan={6} className="px-6 py-8 text-center text-gray-500">
                        {t('common.loading')}
                      </td>
                    </tr>
                  )}
                  {!databasesQ.isLoading &&
                    filtered.map((db) => (
                      <tr
                        key={db.id}
                        role="button"
                        tabIndex={0}
                        className={`cursor-pointer transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/50 ${
                          selectedDbId === db.id
                            ? 'bg-violet-50/90 dark:bg-violet-950/25 ring-1 ring-inset ring-violet-300/80 dark:ring-violet-700/50'
                            : ''
                        }`}
                        onClick={() => setSelectedDbId(db.id)}
                        onKeyDown={(e) => {
                          if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault()
                            setSelectedDbId(db.id)
                          }
                        }}
                      >
                        <td className="px-6 py-4">
                          <div className="flex items-center gap-3">
                            <Database className="h-5 w-5 text-green-500" />
                            <span className="font-medium text-gray-900 dark:text-white">
                              {db.name}
                            </span>
                            <button
                              type="button"
                              className="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500"
                              title={t('databases.copy_name')}
                              onClick={() => copyText(db.name, t('databases.name_copied'))}
                            >
                              <Copy className="h-3.5 w-3.5" />
                            </button>
                          </div>
                        </td>
                        <td className="px-6 py-4">
                          <span
                            className={`px-2.5 py-1 text-xs font-medium rounded-full ${
                              db.type === 'mysql'
                                ? 'bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-400'
                                : 'bg-secondary-50 dark:bg-secondary-900/20 text-secondary-700 dark:text-secondary-400'
                            }`}
                          >
                            {db.type === 'mysql' ? 'MySQL' : 'PostgreSQL'}
                          </span>
                        </td>
                        <td className="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 font-mono">
                          <div className="inline-flex items-center gap-2">
                            <span>{db.username}</span>
                            <button
                              type="button"
                              className="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500"
                              title={t('databases.copy_username')}
                              onClick={() => copyText(db.username, t('databases.username_copied'))}
                            >
                              <Copy className="h-3.5 w-3.5" />
                            </button>
                          </div>
                        </td>
                        <td className="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 font-mono">
                          {db.type === 'mysql' ? db.grant_host || 'localhost' : '—'}
                        </td>
                        <td className="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                          {db.size_mb != null ? `${db.size_mb} MB` : '—'}
                        </td>
                        <td className="px-6 py-4 text-right" onClick={(e) => e.stopPropagation()}>
                          <div className="flex items-center justify-end gap-2">
                            {(db.type === 'mysql' || db.type === 'postgresql') && (
                              <button
                                type="button"
                                className="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500"
                                title={t('databases.edit_credentials')}
                                onClick={() => setEditCredentialsDb(db)}
                              >
                                <Pencil className="h-4 w-4" />
                              </button>
                            )}
                            {(db.type === 'mysql' || db.type === 'postgresql') && (
                              <button
                                type="button"
                                className="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500"
                                title={t('databases.export_sql')}
                                onClick={() => runExport(db)}
                              >
                                <Download className="h-4 w-4" />
                              </button>
                            )}
                            {canImportDb && (db.type === 'mysql' || db.type === 'postgresql') && (
                              <button
                                type="button"
                                className="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500"
                                title={t('databases.import_sql')}
                                onClick={() => setImportDb(db)}
                              >
                                <Upload className="h-4 w-4" />
                              </button>
                            )}
                            {(db.type === 'mysql' || db.type === 'postgresql') && (
                              <button
                                type="button"
                                className="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500"
                                title={t('databases.rotate_password')}
                                onClick={() => {
                                  if (window.confirm(t('databases.rotate_password') + '?')) {
                                    rotateM.mutate(db.id)
                                  }
                                }}
                                disabled={rotateM.isPending}
                              >
                                <KeyRound className="h-4 w-4" />
                              </button>
                            )}
                            {db.type === 'mysql' && phpmyadminUrl && (
                              <>
                                {pmaAutoLogin ? (
                                  <button
                                    type="button"
                                    className="inline-flex items-center gap-1 rounded-lg bg-violet-600 px-2 py-1 text-xs font-medium text-white hover:bg-violet-700 disabled:opacity-60"
                                    title={t('databases.phpmyadmin_sso_btn')}
                                    onClick={() => openPhpMyAdminSso(db)}
                                    disabled={pmaSsoM.isPending}
                                  >
                                    <Sparkles className="h-3.5 w-3.5 shrink-0" />
                                    <span className="hidden lg:inline">
                                      {t('databases.phpmyadmin_sso_short')}
                                    </span>
                                  </button>
                                ) : (
                                  <button
                                    type="button"
                                    className="inline-flex items-center gap-0.5 rounded-lg border border-amber-300/80 dark:border-amber-700/60 px-1.5 py-1 text-xs text-amber-800 dark:text-amber-200 hover:bg-amber-50 dark:hover:bg-amber-950/40"
                                    title={t('databases.phpmyadmin_sso_pro_hint')}
                                    onClick={showPmaProRequired}
                                  >
                                    <Crown className="h-3.5 w-3.5" />
                                    <span className="sr-only lg:not-sr-only lg:inline">
                                      {t('databases.phpmyadmin_sso_pro_badge')}
                                    </span>
                                  </button>
                                )}
                                <button
                                  type="button"
                                  className="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500"
                                  title={t('databases.phpmyadmin_manual')}
                                  onClick={() => openDbWebUi(db)}
                                >
                                  <ExternalLink className="h-4 w-4" />
                                </button>
                              </>
                            )}

                            {db.type === 'postgresql' && adminerUrl && (
                              <button
                                type="button"
                                className="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500"
                                title={t('databases.adminer')}
                                onClick={() => openDbWebUi(db)}
                              >
                                <ExternalLink className="h-4 w-4" />
                              </button>
                            )}
                            <button
                              type="button"
                              className="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-gray-500 hover:text-red-600"
                              onClick={() => {
                                if (window.confirm(t('common.confirm_delete'))) {
                                  deleteM.mutate(db.id)
                                }
                              }}
                              disabled={deleteM.isPending}
                            >
                              <Trash2 className="h-4 w-4" />
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))}
                </tbody>
              </table>
            </div>

            {!databasesQ.isLoading && filtered.length === 0 && (
              <div className="text-center py-12 text-gray-500 dark:text-gray-400">
                {t('common.no_data')}
              </div>
            )}
          </div>
        </div>

        {isSuperAdmin && (
          <aside
            className="xl:sticky xl:top-4 xl:self-start"
            aria-label={t('databases.server_mysql_card_title')}
          >
            <div className="card border-l-4 border-l-amber-500/80 p-3 text-xs dark:border-l-amber-600/70">
              <div className="flex items-start gap-2">
                <Info className="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400 mt-0.5" />
                <div className="min-w-0 flex-1 space-y-2">
                  <div>
                    <h2 className="text-sm font-semibold leading-tight text-gray-900 dark:text-white">
                      {t('databases.server_mysql_card_title')}
                    </h2>
                    <p className="mt-1 text-[11px] leading-snug text-gray-500 dark:text-gray-400">
                      {t('databases.server_mysql_card_subtitle')}
                    </p>
                  </div>

                  {serverMysqlQ.isLoading && (
                    <p className="text-gray-500 dark:text-gray-400">{t('common.loading')}</p>
                  )}
                  {serverMysqlQ.isError && (
                    <p className="text-red-600 dark:text-red-400">{t('databases.server_mysql_load_error')}</p>
                  )}
                  {serverMysqlQ.data && (
                    <>
                      <ul className="space-y-1 text-[11px] leading-snug text-gray-600 dark:text-gray-400">
                        <li>{serverMysqlQ.data.hints.provision_use}</li>
                        <li>{serverMysqlQ.data.hints.root_socket}</li>
                        <li>{serverMysqlQ.data.hints.ssh_secret_file}</li>
                      </ul>

                      {!serverMysqlQ.data.provision.enabled ? (
                        <p className="rounded-md bg-amber-50 px-2 py-1.5 text-[11px] text-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
                          {t('databases.server_mysql_disabled')}
                        </p>
                      ) : (
                        <div className="space-y-2 rounded-md border border-gray-200/80 bg-gray-50/60 p-2 dark:border-gray-700 dark:bg-gray-900/30">
                          {pmaMaintenanceUrl ? (
                            <a
                              href={pmaMaintenanceUrl}
                              target="_blank"
                              rel="noopener noreferrer"
                              className="inline-flex w-full items-center justify-center gap-1 rounded-md bg-primary-600 px-2 py-1.5 text-[11px] font-medium text-white hover:bg-primary-700"
                            >
                              <ExternalLink className="h-3 w-3" />
                              {t('databases.server_mysql_open_pma')}
                            </a>
                          ) : null}

                          <dl className="space-y-1.5 font-mono text-[11px] text-gray-800 dark:text-gray-200">
                            <div>
                              <dt className="text-[10px] font-medium uppercase tracking-wide text-gray-500">
                                {t('databases.host')}
                              </dt>
                              <dd className="mt-0.5 flex items-center gap-1">
                                <span className="min-w-0 flex-1 break-all">
                                  {serverMysqlQ.data.provision.host}:{serverMysqlQ.data.provision.port}
                                </span>
                                <button
                                  type="button"
                                  className="shrink-0 rounded p-0.5 text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700"
                                  onClick={() =>
                                    copyText(
                                      `${serverMysqlQ.data!.provision.host}:${serverMysqlQ.data!.provision.port}`,
                                      t('files.ctx_copy_ok'),
                                    )
                                  }
                                  aria-label={t('common.copy')}
                                >
                                  <Copy className="h-3 w-3" />
                                </button>
                              </dd>
                            </div>
                            <div>
                              <dt className="text-[10px] font-medium uppercase tracking-wide text-gray-500">
                                {t('databases.username')}
                              </dt>
                              <dd className="mt-0.5 flex items-center gap-1">
                                <span className="min-w-0 flex-1 break-all">
                                  {serverMysqlQ.data.provision.username}
                                </span>
                                <button
                                  type="button"
                                  className="shrink-0 rounded p-0.5 text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700"
                                  onClick={() =>
                                    copyText(serverMysqlQ.data!.provision.username, t('files.ctx_copy_ok'))
                                  }
                                  aria-label={t('common.copy')}
                                >
                                  <Copy className="h-3 w-3" />
                                </button>
                              </dd>
                            </div>
                            <div>
                              <dt className="text-[10px] font-medium uppercase tracking-wide text-gray-500">
                                {t('databases.server_mysql_password')}
                              </dt>
                              <dd className="mt-0.5">
                                <div className="flex flex-wrap items-center gap-1">
                                  <span className="min-w-0 flex-1 break-all">
                                    {showServerProvPassword
                                      ? serverMysqlQ.data.provision.password || '—'
                                      : '••••••••'}
                                  </span>
                                  <button
                                    type="button"
                                    className="text-[10px] text-primary-600 hover:underline dark:text-primary-400"
                                    onClick={() => setShowServerProvPassword((v) => !v)}
                                  >
                                    {showServerProvPassword
                                      ? t('databases.server_mysql_hide_password')
                                      : t('databases.server_mysql_show_password')}
                                  </button>
                                  <button
                                    type="button"
                                    className="rounded p-0.5 text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700"
                                    onClick={() =>
                                      copyText(
                                        serverMysqlQ.data!.provision.password,
                                        t('databases.password_copied'),
                                      )
                                    }
                                    disabled={!serverMysqlQ.data.provision.password}
                                    aria-label={t('common.copy')}
                                  >
                                    <Copy className="h-3 w-3" />
                                  </button>
                                </div>
                              </dd>
                            </div>
                          </dl>

                          <details className="rounded border border-gray-200/70 bg-white/50 p-1.5 dark:border-gray-700 dark:bg-black/20">
                            <summary className="cursor-pointer text-[11px] font-medium text-gray-700 dark:text-gray-300">
                              {t('databases.server_mysql_panel_section')}
                            </summary>
                            <p className="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                              {t('databases.server_mysql_panel_hint')}
                            </p>
                            <dl className="mt-1.5 space-y-1 font-mono text-[10px] text-gray-800 dark:text-gray-200">
                              <div className="flex justify-between gap-1">
                                <dt className="text-gray-500">DB</dt>
                                <dd className="truncate text-right">
                                  {serverMysqlQ.data.panel_app_database.database}
                                </dd>
                              </div>
                              <div className="flex justify-between gap-1">
                                <dt className="text-gray-500">{t('databases.username')}</dt>
                                <dd className="truncate text-right">
                                  {serverMysqlQ.data.panel_app_database.username}
                                </dd>
                              </div>
                              <div className="flex justify-between gap-1">
                                <dt className="text-gray-500">{t('databases.host')}</dt>
                                <dd>
                                  {serverMysqlQ.data.panel_app_database.host}:
                                  {serverMysqlQ.data.panel_app_database.port}
                                </dd>
                              </div>
                              <div className="flex items-center justify-between gap-1">
                                <dt className="text-gray-500">{t('databases.server_mysql_password')}</dt>
                                <dd className="flex items-center gap-1">
                                  <button
                                    type="button"
                                    className="text-[10px] text-primary-600 hover:underline dark:text-primary-400"
                                    onClick={() => setShowServerPanelPassword((v) => !v)}
                                  >
                                    {showServerPanelPassword
                                      ? t('databases.server_mysql_hide_password')
                                      : t('databases.server_mysql_show_password')}
                                  </button>
                                  <button
                                    type="button"
                                    className="rounded p-0.5 hover:bg-gray-200 dark:hover:bg-gray-700"
                                    onClick={() =>
                                      copyText(
                                        serverMysqlQ.data!.panel_app_database.password,
                                        t('databases.password_copied'),
                                      )
                                    }
                                    aria-label={t('common.copy')}
                                  >
                                    <Copy className="h-3 w-3" />
                                  </button>
                                </dd>
                              </div>
                              <dd className="text-right break-all">
                                {showServerPanelPassword
                                  ? serverMysqlQ.data.panel_app_database.password || '—'
                                  : '••••••••'}
                              </dd>
                            </dl>
                          </details>
                        </div>
                      )}
                    </>
                  )}
                </div>
              </div>
            </div>
          </aside>
        )}
      </div>

      {showAdd && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="card max-w-md w-full p-6 space-y-4 bg-white dark:bg-gray-900">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
              {t('databases.new_title')}
            </h2>
            <form
              className="space-y-3"
              onSubmit={(ev) => {
                ev.preventDefault()
                const fd = new FormData(ev.currentTarget)
                const domainRaw = String(fd.get('domain_id') || '')
                const type = String(fd.get('type') || 'mysql')
                const gh = String(fd.get('grant_host') || '').trim()
                createM.mutate({
                  name: String(fd.get('name') || '').trim(),
                  type,
                  domain_id: domainRaw ? Number(domainRaw) : undefined,
                  ...(type === 'mysql' && gh ? { grant_host: gh } : {}),
                })
              }}
            >
              <div>
                <label className="label">{t('databases.name')}</label>
                <input name="name" className="input w-full" required placeholder="wordpress" />
              </div>
              <div>
                <label className="label">{t('databases.type')}</label>
                <select
                  name="type"
                  className="input w-full"
                  value={createType}
                  onChange={(e) => setCreateType(e.target.value)}
                >
                  <option value="mysql">MySQL</option>
                  <option value="postgresql">PostgreSQL</option>
                </select>
              </div>
              <div>
                <label className="label">{t('databases.optional_domain')}</label>
                <select name="domain_id" className="input w-full" defaultValue="">
                  <option value="">—</option>
                  {domainOptions.map((d) => (
                    <option key={d.id} value={d.id}>
                      {d.name}
                    </option>
                  ))}
                </select>
              </div>
              {createType === 'mysql' && (
                <div>
                  <label className="label">{t('databases.grant_host')}</label>
                  <select name="grant_host" className="input w-full" defaultValue="localhost">
                    {GRANT_HOST_OPTIONS.map((h) => (
                      <option key={h} value={h}>
                        {h}
                      </option>
                    ))}
                  </select>
                  <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {t('databases.grant_host_hint')}
                  </p>
                </div>
              )}
              <div className="flex gap-2 justify-end pt-2">
                <button
                  type="button"
                  className="btn-secondary"
                  onClick={() => setShowAdd(false)}
                >
                  {t('common.cancel')}
                </button>
                <button type="submit" className="btn-primary" disabled={createM.isPending}>
                  {t('common.create')}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {importDb && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="card max-w-lg w-full p-6 space-y-4 bg-white dark:bg-gray-900 max-h-[90vh] overflow-y-auto">
            <h2 className="text-lg font-semibold text-red-700 dark:text-red-400">
              {t('databases.import_warning_title')}
            </h2>
            <p className="text-sm text-gray-600 dark:text-gray-400">{t('databases.import_warning_body')}</p>
            <p className="text-sm font-medium text-gray-800 dark:text-gray-200">
              {t('databases.import_confirm_hint', {
                phrase: importMetaQ.data?.confirm_phrase ?? t('databases.import_confirm_expected'),
              })}
            </p>
            {importDb.type === 'mysql' && importMetaQ.data && !importMetaQ.data.mysql_tools_enabled && (
              <p className="text-sm text-amber-800 dark:text-amber-200 bg-amber-50 dark:bg-amber-900/25 border border-amber-200 dark:border-amber-800 rounded-lg px-3 py-2">
                {t('databases.import_tools_disabled')}
              </p>
            )}
            {importDb.type === 'postgresql' && importMetaQ.data && !importMetaQ.data.postgres_tools_enabled && (
              <p className="text-sm text-amber-800 dark:text-amber-200 bg-amber-50 dark:bg-amber-900/25 border border-amber-200 dark:border-amber-800 rounded-lg px-3 py-2">
                {t('databases.import_tools_disabled')}
              </p>
            )}
            {importUi && (
              <div className="space-y-2" role="status" aria-live="polite">
                <div className="flex justify-between text-xs text-gray-600 dark:text-gray-400">
                  <span>{importUi.detail}</span>
                  <span>{t('databases.import_progress_percent', { pct: combinedImportPct(importUi) })}</span>
                </div>
                <div className="h-2.5 w-full rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                  <div
                    className="h-full rounded-full bg-red-600 transition-[width] duration-300 ease-out"
                    style={{ width: `${combinedImportPct(importUi)}%` }}
                  />
                </div>
                {importUi.mode === 'server' && (
                  <p className="text-xs text-gray-500 dark:text-gray-400">{t('databases.import_progress_server')}</p>
                )}
              </div>
            )}
            <form
              className="space-y-3"
              onSubmit={(ev) => {
                ev.preventDefault()
                if (importBusy) return
                const fd = new FormData(ev.currentTarget)
                const file = (fd.get('sql_file') as File | null) ?? null
                const confirmation = String(fd.get('confirmation') || '').trim()
                if (!file || file.size === 0) {
                  toast.error(t('databases.import_choose_file'))
                  return
                }
                const maxMb = importMetaQ.data?.max_import_mb ?? 512
                if (file.size > maxMb * 1024 * 1024) {
                  toast.error(t('databases.import_file_too_large', { max: maxMb }))
                  return
                }
                const expected =
                  importMetaQ.data?.confirm_phrase ?? t('databases.import_confirm_expected')
                if (!importConfirmMatches(confirmation, expected)) {
                  toast.error(t('databases.import_confirm_mismatch'))
                  return
                }
                if (
                  (importDb.type === 'mysql' && importMetaQ.data && !importMetaQ.data.mysql_tools_enabled) ||
                  (importDb.type === 'postgresql' &&
                    importMetaQ.data &&
                    !importMetaQ.data.postgres_tools_enabled)
                ) {
                  toast.error(t('databases.import_tools_disabled'))
                  return
                }
                void runDatabaseImport(importDb, file, confirmation)
              }}
            >
              <div>
                <label className="label">{t('databases.import_choose_file')}</label>
                <input
                  name="sql_file"
                  type="file"
                  accept=".sql,text/plain,application/sql"
                  className="input w-full"
                  required
                  disabled={importBusy}
                />
                {importMetaQ.data?.max_import_mb ? (
                  <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {t('databases.import_max_size', { max: importMetaQ.data.max_import_mb })}
                  </p>
                ) : null}
              </div>
              <div>
                <label className="label">{t('databases.import_confirm_label')}</label>
                <input
                  name="confirmation"
                  type="text"
                  className="input w-full font-mono"
                  autoComplete="off"
                  required
                  disabled={importBusy}
                />
              </div>
              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  className="btn-secondary"
                  onClick={() => setImportDb(null)}
                  disabled={importBusy}
                >
                  {t('common.cancel')}
                </button>
                <button
                  type="submit"
                  className="btn-primary bg-red-600 hover:bg-red-700 border-red-600"
                  disabled={importBusy}
                >
                  {importBusy ? t('databases.import_started') : t('databases.import_sql')}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {passwordReveal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="card max-w-md w-full p-6 space-y-4 bg-white dark:bg-gray-900">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
              {t('databases.password_temp_title')}
            </h2>
            <p className="text-sm text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg px-3 py-2">
              {t('databases.password_temp_desc', { seconds: secondsLeft })}
            </p>
            <div className="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 font-mono text-sm text-gray-900 dark:text-gray-100 break-all">
              {passwordReveal.value}
            </div>
            <div className="flex justify-end gap-2">
              <button
                type="button"
                className="btn-secondary"
                onClick={() =>
                  void copyPlaintextWithToasts(passwordReveal.value, {
                    ok: t('databases.password_copied'),
                    fail: t('databases.copy_failed'),
                  })
                }
              >
                {t('common.copy')}
              </button>
              <button
                type="button"
                className="btn-primary"
                onClick={() => setPasswordReveal(null)}
              >
                {t('common.close')}
              </button>
            </div>
          </div>
        </div>
      )}

      {editCredentialsDb && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="card max-w-lg w-full p-6 space-y-4 bg-white dark:bg-gray-900">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
              {t('databases.edit_credentials')}
            </h2>
            <p className="text-sm text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg px-3 py-2">
              {t('databases.edit_credentials_warning')}
            </p>
            <form
              className="space-y-3"
              onSubmit={(ev) => {
                ev.preventDefault()
                const fd = new FormData(ev.currentTarget)
                const payload = {
                  id: editCredentialsDb.id,
                  password: String(fd.get('password') || '').trim(),
                  grant_host: editCredentialsDb.type === 'mysql'
                    ? String(fd.get('grant_host') || '').trim()
                    : undefined,
                }
                patchCredentialsM.mutate(payload)
              }}
            >
              <div>
                <label className="label">{t('databases.db_name_readonly')}</label>
                <div className="input w-full font-mono bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-200 cursor-default">
                  {editCredentialsDb.name}
                </div>
                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  {t('databases.db_name_readonly_hint')}
                </p>
              </div>
              <div>
                <label className="label">{t('databases.username_readonly')}</label>
                <div className="input w-full font-mono bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-200 cursor-default">
                  {editCredentialsDb.username}
                </div>
                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  {t('databases.username_readonly_hint')}
                </p>
              </div>
              <p className="text-xs text-gray-600 dark:text-gray-400">{t('databases.credentials_modal_app_hint')}</p>
              <div>
                <label className="label">{t('databases.new_password_optional')}</label>
                <input name="password" type="text" className="input w-full font-mono" autoComplete="off" />
                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  {t('databases.new_password_optional_hint')}
                </p>
              </div>
              {editCredentialsDb.type === 'mysql' && (
                <div>
                  <label className="label">{t('databases.grant_host')}</label>
                  <select
                    name="grant_host"
                    className="input w-full"
                    defaultValue={editCredentialsDb.grant_host?.trim() || 'localhost'}
                  >
                    {grantHostSelectOptions(editCredentialsDb.grant_host).map((h) => (
                      <option key={h} value={h}>
                        {h}
                      </option>
                    ))}
                  </select>
                </div>
              )}
              <label className="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input name="ack" type="checkbox" required className="mt-1" />
                <span>{t('databases.edit_credentials_ack')}</span>
              </label>
              <div className="flex gap-2 justify-end pt-2">
                <button
                  type="button"
                  className="btn-secondary"
                  onClick={() => setEditCredentialsDb(null)}
                >
                  {t('common.cancel')}
                </button>
                <button type="submit" className="btn-primary" disabled={patchCredentialsM.isPending}>
                  {t('common.save')}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
