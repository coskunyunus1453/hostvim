import { useEffect, useMemo, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useSearchParams } from 'react-router-dom'
import api from '../services/api'
import {
  Cloud,
  Download,
  ExternalLink,
  HardDrive,
  HelpCircle,
  Plus,
  RotateCcw,
  Shield,
  Trash2,
  Upload,
  Play,
  Server,
} from 'lucide-react'
import toast from 'react-hot-toast'
import clsx from 'clsx'
import { useAutoDomainId } from '../hooks/useAutoDomainId'
import { useAuthStore } from '../store/authStore'

type BackupRow = {
  id: number
  domain_id: number
  destination_id?: number | null
  type: string
  status: string
  created_at: string
  file_path?: string | null
  remote_path?: string | null
  remote_file_id?: string | null
  engine_backup_id?: string | null
  size_mb?: number | null
  domain?: { name: string }
  destination?: { id: number; name: string; driver: string }
}

type DestinationRow = {
  id: number
  name: string
  driver: 'local' | 's3' | 'ftp' | 'google_drive'
  is_default?: boolean
  is_active?: boolean
  config?: { email?: string }
}

type RemoteFile = {
  id: string
  name: string
  size?: number | null
  modified_at?: string | null
}

function formatBytes(mb?: number | null): string {
  if (mb == null || !Number.isFinite(mb)) return '—'
  if (mb < 1) return `${Math.round(mb * 1024)} KB`
  return `${mb.toFixed(mb >= 10 ? 0 : 1)} MB`
}

function statusClass(status: string): string {
  switch (status) {
    case 'completed':
      return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
    case 'running':
    case 'pending':
      return 'bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200'
    case 'failed':
      return 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
    default:
      return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'
  }
}

export default function BackupsPage() {
  const { t } = useTranslation()
  const isAdmin = useAuthStore((s) => s.user?.roles?.some((r) => r.name === 'admin'))
  const qc = useQueryClient()
  const { domainId: domainFilter, setDomainId: setDomainFilter, domainsQ } = useAutoDomainId({
    param: false,
  })
  const { domainId: uploadDomainId, setDomainId: setUploadDomainId } = useAutoDomainId({ param: false })
  const [searchParams, setSearchParams] = useSearchParams()
  const uploadRef = useRef<HTMLInputElement>(null)
  const [showAdd, setShowAdd] = useState(false)
  const [showDest, setShowDest] = useState(false)
  const [showSchedule, setShowSchedule] = useState(false)
  const [scheduleForm, setScheduleForm] = useState<{
    id: number | null
    domain_id: number | ''
    destination_id: number | ''
    type: 'full' | 'files' | 'database'
    schedule: string
    enabled: boolean
  }>({
    id: null,
    domain_id: '',
    destination_id: '',
    type: 'full',
    schedule: '0 3 * * *',
    enabled: true,
  })
  const [restoreTarget, setRestoreTarget] = useState<BackupRow | null>(null)
  const [showGdriveGuide, setShowGdriveGuide] = useState(false)

  const q = useQuery({
    queryKey: ['backups', domainFilter],
    queryFn: async () =>
      (await api.get('/backups', {
        params: domainFilter !== '' ? { domain_id: domainFilter } : undefined,
      })).data,
  })

  const gdriveQ = useQuery({
    queryKey: ['backups-gdrive'],
    queryFn: async () => (await api.get('/backups/google-drive/status')).data as {
      configured: boolean
      credential_source?: 'hub' | 'env' | null
      hub_expected?: boolean
      redirect_uri?: string | null
      hub_integrations_url?: string | null
      connected: boolean
      destination?: { id: number; name: string; email?: string }
    },
  })

  const destQ = useQuery({
    queryKey: ['backup-destinations'],
    queryFn: async () => (await api.get('/backups/destinations')).data as { destinations: DestinationRow[] },
  })

  const gdriveFilesQ = useQuery({
    queryKey: ['gdrive-files', gdriveQ.data?.destination?.id, domainFilter],
    enabled: !!gdriveQ.data?.connected && !!gdriveQ.data?.destination?.id,
    queryFn: async () => {
      const destId = gdriveQ.data!.destination!.id
      const domainName =
        domainFilter !== ''
          ? (domainsQ.data ?? []).find((d) => d.id === domainFilter)?.name
          : undefined
      const { data } = await api.get(`/backups/destinations/${destId}/remote-files`, {
        params: domainName ? { domain: domainName } : undefined,
      })
      return data as { files: RemoteFile[] }
    },
  })

  const scheduleQ = useQuery({
    queryKey: ['backup-schedules'],
    queryFn: async () => (await api.get('/backups/schedules')).data,
  })

  const resetScheduleForm = () => {
    setScheduleForm({
      id: null,
      domain_id: domainFilter !== '' ? domainFilter : '',
      destination_id: '',
      type: 'full',
      schedule: '0 3 * * *',
      enabled: true,
    })
  }

  const openScheduleEditor = (row?: Record<string, unknown>) => {
    if (row) {
      setScheduleForm({
        id: Number(row.id),
        domain_id: Number((row.domain_id as number) ?? (row.domain as { id?: number })?.id ?? ''),
        destination_id: row.destination_id != null ? Number(row.destination_id) : '',
        type: (String(row.type ?? 'full') as 'full' | 'files' | 'database') || 'full',
        schedule: String(row.schedule ?? '0 3 * * *'),
        enabled: row.enabled !== false,
      })
    } else {
      resetScheduleForm()
    }
    setShowSchedule(true)
  }

  const saveScheduleM = useMutation({
    mutationFn: async () => {
      const payload = {
        domain_id: Number(scheduleForm.domain_id),
        destination_id: scheduleForm.destination_id !== '' ? Number(scheduleForm.destination_id) : undefined,
        type: scheduleForm.type,
        schedule: scheduleForm.schedule.trim(),
        enabled: scheduleForm.enabled,
      }
      if (scheduleForm.id) {
        return api.patch(`/backups/schedules/${scheduleForm.id}`, payload)
      }
      return api.post('/backups/schedules', payload)
    },
    onSuccess: () => {
      toast.success(t('backups.schedule_saved'))
      qc.invalidateQueries({ queryKey: ['backup-schedules'] })
      setShowSchedule(false)
      resetScheduleForm()
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const deleteScheduleM = useMutation({
    mutationFn: async (id: number) => api.delete(`/backups/schedules/${id}`),
    onSuccess: () => {
      toast.success(t('backups.deleted'))
      qc.invalidateQueries({ queryKey: ['backup-schedules'] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const runScheduleM = useMutation({
    mutationFn: async (id: number) => api.post(`/backups/schedules/${id}/run`),
    onSuccess: () => {
      toast.success(t('backups.schedule_run_started'))
      qc.invalidateQueries({ queryKey: ['backups'] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  // Google OAuth callback (/backups/google-callback)
  useEffect(() => {
    const isCallback = window.location.pathname.includes('google-callback')
    const code = searchParams.get('code')
    const state = searchParams.get('state')
    const gdriveError = searchParams.get('gdrive_error')
    if (gdriveError) {
      toast.error(gdriveError)
      searchParams.delete('gdrive_error')
      setSearchParams(searchParams, { replace: true })
      window.history.replaceState({}, '', '/backups')
      return
    }
    if (!isCallback || !code || !state) return

    ;(async () => {
      try {
        await api.post('/backups/google-drive/complete', { code, state })
        toast.success(t('backups.google_connected'))
        qc.invalidateQueries({ queryKey: ['backups-gdrive'] })
        qc.invalidateQueries({ queryKey: ['backup-destinations'] })
      } catch (err: unknown) {
        const ax = err as { response?: { data?: { message?: string } } }
        toast.error(ax.response?.data?.message ?? String(err))
      } finally {
        searchParams.delete('code')
        searchParams.delete('state')
        searchParams.delete('scope')
        setSearchParams(searchParams, { replace: true })
        if (isCallback) {
          window.history.replaceState({}, '', '/backups')
        }
      }
    })()
  }, [searchParams, setSearchParams, qc, t])

  const createM = useMutation({
    mutationFn: async (payload: { domain_id: number; type: string; destination_id?: number }) =>
      api.post('/backups', payload),
    onSuccess: () => {
      toast.success(t('backups.queued'))
      qc.invalidateQueries({ queryKey: ['backups'] })
      setShowAdd(false)
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const deleteM = useMutation({
    mutationFn: async (id: number) => api.delete(`/backups/${id}`),
    onSuccess: () => {
      toast.success(t('backups.deleted'))
      qc.invalidateQueries({ queryKey: ['backups'] })
    },
  })

  const restoreM = useMutation({
    mutationFn: async (payload: { id: number; source?: 'engine' | 'remote' }) =>
      api.post(`/backups/${payload.id}/restore`, { source: payload.source ?? 'engine' }),
    onSuccess: () => {
      toast.success(t('backups.restore_started'))
      setRestoreTarget(null)
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const restoreRemoteM = useMutation({
    mutationFn: async (payload: { domain_id: number; destination_id: number; remote_file_id: string }) =>
      api.post('/backups/restore-remote', payload),
    onSuccess: () => toast.success(t('backups.restore_started')),
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const uploadRestoreM = useMutation({
    mutationFn: async (payload: { domain_id: number; file: File }) => {
      const fd = new FormData()
      fd.append('domain_id', String(payload.domain_id))
      fd.append('archive', payload.file)
      return api.post('/backups/restore-upload', fd)
    },
    onSuccess: () => toast.success(t('backups.restore_started')),
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const syncM = useMutation({
    mutationFn: async (id: number) => api.post(`/backups/${id}/sync`),
    onSuccess: () => {
      toast.success(t('backups.synced'))
      qc.invalidateQueries({ queryKey: ['backups'] })
      qc.invalidateQueries({ queryKey: ['gdrive-files'] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const connectGdriveM = useMutation({
    mutationFn: async () => {
      const { data } = await api.get('/backups/google-drive/auth-url')
      return data as { url: string }
    },
    onSuccess: (data) => {
      if (data?.url) {
        window.location.href = `${data.url}`
      }
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const disconnectGdriveM = useMutation({
    mutationFn: async () => api.delete('/backups/google-drive/disconnect'),
    onSuccess: () => {
      toast.success(t('backups.google_disconnected'))
      qc.invalidateQueries({ queryKey: ['backups-gdrive'] })
      qc.invalidateQueries({ queryKey: ['backup-destinations'] })
    },
  })

  const downloadBackup = async (id: number, filename: string) => {
    try {
      const res = await api.get(`/backups/${id}/download`, { responseType: 'blob' })
      const url = window.URL.createObjectURL(res.data)
      const a = document.createElement('a')
      a.href = url
      a.download = filename.endsWith('.tar.gz') ? filename : `${filename}.tar.gz`
      a.click()
      window.URL.revokeObjectURL(url)
    } catch (err: unknown) {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? t('backups.download_failed'))
    }
  }

  const rows: BackupRow[] = q.data?.data ?? []
  const destinations = destQ.data?.destinations ?? []
  const schedules = (scheduleQ.data?.schedules ?? []) as Array<Record<string, unknown>>
  const gdriveFiles = gdriveFilesQ.data?.files ?? []

  const defaultDest = useMemo(
    () => destinations.find((d) => d.is_default) ?? destinations.find((d) => d.driver === 'google_drive'),
    [destinations],
  )

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <Shield className="h-8 w-8 text-amber-500" />
          <div>
            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{t('nav.backups')}</h1>
            <p className="text-sm text-gray-500 dark:text-gray-400">{t('backups.subtitle')}</p>
          </div>
        </div>
        <button type="button" className="btn-primary flex items-center gap-2" onClick={() => setShowAdd(true)}>
          <Plus className="h-4 w-4" />
          {t('backups.new_backup')}
        </button>
      </div>

      {/* Domain filter + quick actions */}
      <div className="card p-4 flex flex-wrap gap-3 items-end">
        <div className="min-w-[220px]">
          <label className="label">{t('backups.filter_domain')}</label>
          <select
            className="input w-full"
            value={domainFilter}
            onChange={(e) => setDomainFilter(e.target.value ? Number(e.target.value) : '')}
          >
            <option value="">{t('backups.all_domains')}</option>
            {(domainsQ.data ?? []).map((d) => (
              <option key={d.id} value={d.id}>{d.name}</option>
            ))}
          </select>
        </div>
        <button type="button" className="btn-secondary" onClick={() => setShowDest(true)}>
          <Server className="h-4 w-4 inline mr-1" />
          {t('backups.destinations')}
        </button>
        <button type="button" className="btn-secondary" onClick={() => openScheduleEditor()}>
          <Play className="h-4 w-4 inline mr-1" />
          {t('backups.schedules')}
        </button>
      </div>

      {/* Google Drive card */}
      <div className="card p-5 border-l-4 border-l-blue-500">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div className="flex items-start gap-3">
            <Cloud className="h-7 w-7 text-blue-500 shrink-0 mt-0.5" />
            <div>
              <h2 className="font-semibold text-gray-900 dark:text-white">{t('backups.google_drive_title')}</h2>
              <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">{t('backups.google_drive_hint')}</p>
              {gdriveQ.data?.connected && gdriveQ.data.destination && (
                <p className="text-xs text-emerald-600 dark:text-emerald-400 mt-2">
                  {t('backups.google_connected_as', { email: gdriveQ.data.destination.email ?? gdriveQ.data.destination.name })}
                </p>
              )}
              {!gdriveQ.data?.configured && (
                <p className="text-xs text-amber-700 dark:text-amber-300 mt-2">
                  {gdriveQ.data?.hub_expected
                    ? t('backups.google_not_configured_hub')
                    : isAdmin
                      ? t('backups.google_not_configured_admin')
                      : t('backups.google_not_configured_customer')}
                </p>
              )}
              {gdriveQ.data?.configured && gdriveQ.data.credential_source === 'hub' && (
                <p className="text-xs text-emerald-700 dark:text-emerald-300 mt-2">{t('backups.google_configured_hub')}</p>
              )}
              {gdriveQ.data?.configured && !gdriveQ.data?.connected && (
                <p className="text-xs text-blue-700 dark:text-blue-300 mt-2">{t('backups.google_connect_hint')}</p>
              )}
            </div>
          </div>
          <div className="flex flex-wrap gap-2">
            <button
              type="button"
              className="btn-secondary text-sm inline-flex items-center gap-1.5"
              onClick={() => setShowGdriveGuide(true)}
            >
              <HelpCircle className="h-4 w-4" />
              {gdriveQ.data?.configured ? t('backups.google_connect_guide_btn') : t('backups.google_setup_guide_btn')}
            </button>
            {gdriveQ.data?.connected ? (
              <button type="button" className="btn-secondary text-sm" onClick={() => disconnectGdriveM.mutate()} disabled={disconnectGdriveM.isPending}>
                {t('backups.google_disconnect')}
              </button>
            ) : (
              <button
                type="button"
                className="btn-primary text-sm"
                disabled={!gdriveQ.data?.configured || connectGdriveM.isPending}
                onClick={() => connectGdriveM.mutate()}
              >
                {t('backups.google_connect')}
              </button>
            )}
          </div>
        </div>

        {gdriveQ.data?.connected && (
          <div className="mt-4">
            <h3 className="text-sm font-medium text-gray-800 dark:text-gray-200 mb-2">{t('backups.google_files')}</h3>
            {gdriveFilesQ.isLoading && <p className="text-sm text-gray-500">{t('common.loading')}</p>}
            {!gdriveFilesQ.isLoading && gdriveFiles.length === 0 && (
              <p className="text-sm text-gray-500">{t('backups.google_files_empty')}</p>
            )}
            <div className="space-y-2">
              {gdriveFiles.map((f) => (
                <div key={f.id} className="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2">
                  <div className="min-w-0">
                    <p className="font-mono text-sm truncate">{f.name}</p>
                    <p className="text-xs text-gray-500">
                      {f.modified_at ? new Date(f.modified_at).toLocaleString() : '—'}
                      {f.size != null ? ` · ${(f.size / 1048576).toFixed(1)} MB` : ''}
                    </p>
                  </div>
                  <button
                    type="button"
                    className="btn-secondary text-xs py-1.5"
                    disabled={restoreRemoteM.isPending || domainFilter === ''}
                    title={domainFilter === '' ? t('backups.select_domain_restore') : undefined}
                    onClick={() => {
                      if (domainFilter === '') {
                        toast.error(t('backups.select_domain_restore'))
                        return
                      }
                      if (!window.confirm(t('backups.restore_confirm', { name: f.name }))) return
                      restoreRemoteM.mutate({
                        domain_id: domainFilter as number,
                        destination_id: gdriveQ.data!.destination!.id,
                        remote_file_id: f.id,
                      })
                    }}
                  >
                    <RotateCcw className="h-3.5 w-3.5 inline mr-1" />
                    {t('backups.restore')}
                  </button>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>

      {/* Upload restore */}
      <div className="card p-5">
        <div className="flex items-start gap-3 mb-4">
          <Upload className="h-6 w-6 text-violet-500" />
          <div>
            <h2 className="font-semibold text-gray-900 dark:text-white">{t('backups.upload_restore_title')}</h2>
            <p className="text-sm text-gray-500">{t('backups.upload_restore_hint')}</p>
          </div>
        </div>
        <div className="flex flex-wrap gap-3 items-end">
          <div className="min-w-[220px]">
            <label className="label">{t('backups.restore_to_domain')}</label>
            <select
              className="input w-full"
              value={uploadDomainId}
              onChange={(e) => setUploadDomainId(e.target.value ? Number(e.target.value) : '')}
            >
              <option value="">{t('common.select')}</option>
              {(domainsQ.data ?? []).map((d) => (
                <option key={d.id} value={d.id}>{d.name}</option>
              ))}
            </select>
          </div>
          <input
            ref={uploadRef}
            type="file"
            accept=".tar.gz,.gz,application/gzip"
            className="hidden"
            onChange={(e) => {
              const file = e.target.files?.[0]
              if (!file || uploadDomainId === '') return
              if (!window.confirm(t('backups.restore_confirm', { name: file.name }))) {
                e.target.value = ''
                return
              }
              uploadRestoreM.mutate({ domain_id: uploadDomainId as number, file })
              e.target.value = ''
            }}
          />
          <button
            type="button"
            className="btn-primary"
            disabled={uploadDomainId === '' || uploadRestoreM.isPending}
            onClick={() => uploadRef.current?.click()}
          >
            <HardDrive className="h-4 w-4 inline mr-1" />
            {t('backups.upload_restore_btn')}
          </button>
        </div>
      </div>

      {/* Backup list */}
      <div className="card overflow-hidden">
        <div className="px-4 py-3 border-b border-gray-100 dark:border-gray-800">
          <h2 className="font-semibold text-gray-900 dark:text-white">{t('backups.list_title')}</h2>
        </div>
        {q.isLoading && <p className="p-6 text-center text-gray-500">{t('common.loading')}</p>}
        {!q.isLoading && rows.length === 0 && (
          <p className="p-6 text-center text-gray-500">{t('backups.empty')}</p>
        )}
        <div className="divide-y divide-gray-100 dark:divide-gray-800">
          {rows.map((b) => (
            <div key={b.id} className="p-4 flex flex-wrap items-center justify-between gap-3 hover:bg-gray-50/80 dark:hover:bg-gray-800/30">
              <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-2">
                  <span className="font-medium text-gray-900 dark:text-white">{b.domain?.name ?? b.domain_id}</span>
                  <span className={clsx('rounded-full px-2 py-0.5 text-xs font-medium', statusClass(b.status))}>{b.status}</span>
                  <span className="text-xs font-mono text-gray-500 uppercase">{b.type}</span>
                </div>
                <p className="text-xs text-gray-500 mt-1">
                  {new Date(b.created_at).toLocaleString()}
                  {b.size_mb != null ? ` · ${formatBytes(b.size_mb)}` : ''}
                  {b.destination ? ` · ${b.destination.name}` : ''}
                  {b.remote_file_id ? ` · Drive ✓` : b.remote_path ? ` · ☁` : ''}
                </p>
              </div>
              <div className="flex items-center gap-1">
                <button
                  type="button"
                  className="p-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 text-blue-600"
                  title={t('backups.download')}
                  onClick={() => downloadBackup(b.id, `${b.domain?.name ?? 'backup'}_${b.id}.tar.gz`)}
                >
                  <Download className="h-4 w-4" />
                </button>
                <button
                  type="button"
                  className="p-2 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-emerald-600"
                  title={t('backups.sync_cloud')}
                  disabled={syncM.isPending}
                  onClick={() => syncM.mutate(b.id)}
                >
                  <Cloud className="h-4 w-4" />
                </button>
                <button
                  type="button"
                  className="p-2 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 text-indigo-600"
                  title={t('backups.restore')}
                  disabled={!b.engine_backup_id?.trim() && !b.remote_file_id}
                  onClick={() => setRestoreTarget(b)}
                >
                  <RotateCcw className="h-4 w-4" />
                </button>
                <button
                  type="button"
                  className="p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600"
                  onClick={() => {
                    if (window.confirm(t('common.confirm_delete'))) deleteM.mutate(b.id)
                  }}
                >
                  <Trash2 className="h-4 w-4" />
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Google Drive setup / connect guide */}
      {showGdriveGuide && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="card max-w-lg w-full max-h-[90vh] overflow-y-auto p-6 space-y-5 bg-white dark:bg-gray-900">
            <div className="flex items-start justify-between gap-3">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white">{t('backups.google_setup_modal_title')}</h2>
              <button type="button" className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" onClick={() => setShowGdriveGuide(false)} aria-label={t('common.close')}>
                ×
              </button>
            </div>
            <p className="text-sm text-gray-600 dark:text-gray-400">{t('backups.google_setup_modal_intro')}</p>

            {isAdmin && !gdriveQ.data?.configured && gdriveQ.data?.hub_expected && (
              <section className="space-y-3">
                <h3 className="text-sm font-semibold text-gray-900 dark:text-white">{t('backups.google_setup_hub_section')}</h3>
                <ol className="list-decimal list-inside space-y-2 text-sm text-gray-700 dark:text-gray-300">
                  <li>
                    {t('backups.google_setup_hub_step1')}{' '}
                    {gdriveQ.data?.hub_integrations_url && (
                      <a href={gdriveQ.data.hub_integrations_url} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-0.5 text-blue-600 dark:text-blue-400 underline">
                        {t('backups.google_setup_hub_link')} <ExternalLink className="h-3 w-3" />
                      </a>
                    )}
                  </li>
                  <li>
                    {t('backups.google_setup_hub_step2')}{' '}
                    <a href="https://console.cloud.google.com/apis/library/drive.googleapis.com" target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-0.5 text-blue-600 dark:text-blue-400 underline">
                      {t('backups.google_setup_link_drive_api')} <ExternalLink className="h-3 w-3" />
                    </a>
                    {' · '}
                    <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-0.5 text-blue-600 dark:text-blue-400 underline">
                      {t('backups.google_setup_link_credentials')} <ExternalLink className="h-3 w-3" />
                    </a>
                  </li>
                  <li>
                    {t('backups.google_setup_hub_step3')}
                    {gdriveQ.data?.redirect_uri && (
                      <code className="mt-1 block rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-2 text-xs font-mono break-all dark:border-gray-700 dark:bg-gray-800">
                        {gdriveQ.data.redirect_uri}
                      </code>
                    )}
                  </li>
                  <li>{t('backups.google_setup_hub_step4')}</li>
                  <li>{t('backups.google_setup_hub_step5')}</li>
                </ol>
              </section>
            )}

            {isAdmin && !gdriveQ.data?.configured && !gdriveQ.data?.hub_expected && (
              <section className="space-y-3">
                <h3 className="text-sm font-semibold text-gray-900 dark:text-white">{t('backups.google_setup_admin_section')}</h3>
                <ol className="list-decimal list-inside space-y-2 text-sm text-gray-700 dark:text-gray-300">
                  <li>
                    {t('backups.google_setup_admin_step2')}{' '}
                    <a href="https://console.cloud.google.com/apis/library/drive.googleapis.com" target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-0.5 text-blue-600 dark:text-blue-400 underline">
                      {t('backups.google_setup_link_drive_api')} <ExternalLink className="h-3 w-3" />
                    </a>
                  </li>
                  <li>
                    {t('backups.google_setup_admin_step5')}
                    {gdriveQ.data?.redirect_uri && (
                      <code className="mt-1 block rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-2 text-xs font-mono break-all dark:border-gray-700 dark:bg-gray-800">
                        {gdriveQ.data.redirect_uri}
                      </code>
                    )}
                  </li>
                  <li>
                    {t('backups.google_setup_admin_step6')}
                    <pre className="mt-1 rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-2 text-xs font-mono dark:border-gray-700 dark:bg-gray-800 whitespace-pre-wrap">{`GOOGLE_DRIVE_CLIENT_ID=...\nGOOGLE_DRIVE_CLIENT_SECRET=...`}</pre>
                  </li>
                  <li>{t('backups.google_setup_admin_step7')}</li>
                </ol>
              </section>
            )}

            <section className="space-y-3 border-t border-gray-200 dark:border-gray-700 pt-4">
              <h3 className="text-sm font-semibold text-gray-900 dark:text-white">{t('backups.google_setup_customer_section')}</h3>
              <ol className="list-decimal list-inside space-y-2 text-sm text-gray-700 dark:text-gray-300">
                <li>{t('backups.google_setup_customer_step1')}</li>
                <li>{t('backups.google_setup_customer_step2')}</li>
                <li>{t('backups.google_setup_customer_step3')}</li>
                <li>{t('backups.google_setup_customer_step4')}</li>
              </ol>
              {!gdriveQ.data?.configured && (
                <p className="text-sm text-amber-700 dark:text-amber-300 rounded-lg border border-amber-200/80 bg-amber-50/80 px-3 py-2 dark:border-amber-800/50 dark:bg-amber-950/30">
                  {t('backups.google_setup_customer_not_ready')}
                </p>
              )}
            </section>

            <button type="button" className="btn-secondary w-full" onClick={() => setShowGdriveGuide(false)}>
              {t('common.close')}
            </button>
          </div>
        </div>
      )}

      {/* Restore modal */}
      {restoreTarget && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="card max-w-md w-full p-6 space-y-4 bg-white dark:bg-gray-900">
            <h2 className="text-lg font-semibold">{t('backups.restore_title')}</h2>
            <p className="text-sm text-gray-500">{restoreTarget.domain?.name} · {restoreTarget.type}</p>
            <div className="space-y-2">
              {restoreTarget.engine_backup_id?.trim() && (
                <button
                  type="button"
                  className="btn-primary w-full"
                  disabled={restoreM.isPending}
                  onClick={() => {
                    if (!window.confirm(t('backups.restore_confirm', { name: restoreTarget.domain?.name ?? '' }))) return
                    restoreM.mutate({ id: restoreTarget.id, source: 'engine' })
                  }}
                >
                  {t('backups.restore_from_server')}
                </button>
              )}
              {restoreTarget.remote_file_id && restoreTarget.destination_id && (
                <button
                  type="button"
                  className="btn-secondary w-full"
                  disabled={restoreRemoteM.isPending}
                  onClick={() => {
                    if (!window.confirm(t('backups.restore_confirm', { name: 'Google Drive' }))) return
                    restoreRemoteM.mutate({
                      domain_id: restoreTarget.domain_id,
                      destination_id: restoreTarget.destination_id!,
                      remote_file_id: restoreTarget.remote_file_id!,
                    })
                    setRestoreTarget(null)
                  }}
                >
                  {t('backups.restore_from_drive')}
                </button>
              )}
            </div>
            <button type="button" className="btn-secondary w-full" onClick={() => setRestoreTarget(null)}>
              {t('common.cancel')}
            </button>
          </div>
        </div>
      )}

      {/* Create backup modal */}
      {showAdd && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="card max-w-md w-full p-6 space-y-4 bg-white dark:bg-gray-900">
            <h2 className="text-lg font-semibold">{t('backups.new_backup')}</h2>
            <form
              className="space-y-3"
              onSubmit={(ev) => {
                ev.preventDefault()
                const fd = new FormData(ev.currentTarget)
                createM.mutate({
                  domain_id: Number(fd.get('domain_id')),
                  type: String(fd.get('type') || 'full'),
                  ...(fd.get('destination_id') ? { destination_id: Number(fd.get('destination_id')) } : {}),
                })
              }}
            >
              <div>
                <label className="label">{t('domains.name')}</label>
                <select name="domain_id" className="input w-full" required defaultValue={domainFilter !== '' ? domainFilter : undefined}>
                  <option value="">{t('common.select')}</option>
                  {(domainsQ.data ?? []).map((d) => (
                    <option key={d.id} value={d.id}>{d.name}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="label">{t('backups.type')}</label>
                <select name="type" className="input w-full" defaultValue="full">
                  <option value="full">{t('backups.type_full')}</option>
                  <option value="files">{t('backups.type_files')}</option>
                  <option value="database">{t('backups.type_database')}</option>
                </select>
              </div>
              <div>
                <label className="label">{t('backups.destination')}</label>
                <select name="destination_id" className="input w-full" defaultValue={defaultDest?.id ?? ''}>
                  <option value="">{t('backups.destination_local_only')}</option>
                  {destinations.map((d) => (
                    <option key={d.id} value={d.id}>{d.name} ({d.driver})</option>
                  ))}
                </select>
              </div>
              <div className="flex justify-end gap-2">
                <button type="button" className="btn-secondary" onClick={() => setShowAdd(false)}>{t('common.cancel')}</button>
                <button type="submit" className="btn-primary" disabled={createM.isPending}>{t('backups.create_btn')}</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Destinations modal - simplified */}
      {showDest && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="card max-w-lg w-full p-6 space-y-4 bg-white dark:bg-gray-900 max-h-[90vh] overflow-y-auto">
            <h2 className="text-lg font-semibold">{t('backups.destinations')}</h2>
            <p className="text-xs text-gray-500">{t('backups.destinations_hint')}</p>
            <div className="space-y-2">
              {destinations.map((d) => (
                <div key={d.id} className="flex items-center justify-between rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                  <div>
                    <p className="font-medium text-sm">{d.name}</p>
                    <p className="text-xs text-gray-500">{d.driver}{d.config?.email ? ` · ${d.config.email}` : ''}</p>
                  </div>
                  {d.driver !== 'google_drive' && (
                    <span className="text-xs text-gray-400">{d.is_default ? '★' : ''}</span>
                  )}
                </div>
              ))}
            </div>
            {!gdriveQ.data?.connected && gdriveQ.data?.configured && (
              <button type="button" className="btn-primary w-full" onClick={() => connectGdriveM.mutate()}>
                {t('backups.google_connect')}
              </button>
            )}
            <button type="button" className="btn-secondary w-full" onClick={() => setShowDest(false)}>{t('common.cancel')}</button>
          </div>
        </div>
      )}

      {showSchedule && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="card max-w-2xl w-full p-6 bg-white dark:bg-gray-900 max-h-[90vh] overflow-y-auto space-y-4">
            <div className="flex items-start justify-between gap-3">
              <div>
                <h2 className="text-lg font-semibold">{t('backups.schedules')}</h2>
                <p className="text-sm text-gray-500">{t('backups.schedules_hint')}</p>
              </div>
              <button type="button" className="btn-primary text-sm" onClick={() => openScheduleEditor()}>
                {t('backups.schedule_add')}
              </button>
            </div>

            {schedules.length === 0 ? (
              <p className="text-sm text-gray-500">{t('common.no_data')}</p>
            ) : (
              <div className="space-y-2">
                {schedules.map((s) => (
                  <div
                    key={String(s.id)}
                    className="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-gray-200 dark:border-gray-700 p-3"
                  >
                    <div className="min-w-0">
                      <p className="text-sm font-medium font-mono">
                        {String((s.domain as { name?: string })?.name ?? s.domain_id)}
                        <span className="mx-2 text-gray-400">·</span>
                        {String(s.type ?? 'full')}
                      </p>
                      <p className="text-xs text-gray-500 font-mono">{String(s.schedule)}</p>
                      {(s.destination as { name?: string })?.name && (
                        <p className="text-xs text-gray-400">→ {(s.destination as { name?: string }).name}</p>
                      )}
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                      <span
                        className={clsx(
                          'rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase',
                          s.enabled
                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200'
                            : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                        )}
                      >
                        {s.enabled ? t('security.status.on') : t('security.status.off')}
                      </span>
                      <button
                        type="button"
                        className="btn-secondary text-xs py-1"
                        disabled={runScheduleM.isPending}
                        onClick={() => runScheduleM.mutate(Number(s.id))}
                      >
                        {t('backups.schedule_run_now')}
                      </button>
                      <button type="button" className="btn-secondary text-xs py-1" onClick={() => openScheduleEditor(s)}>
                        {t('common.edit')}
                      </button>
                      <button
                        type="button"
                        className="rounded-lg border border-red-200 px-2 py-1 text-xs text-red-600 dark:border-red-900"
                        disabled={deleteScheduleM.isPending}
                        onClick={() => {
                          if (!window.confirm(t('backups.schedule_delete_confirm'))) return
                          deleteScheduleM.mutate(Number(s.id))
                        }}
                      >
                        {t('common.delete')}
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            )}

            <div className="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-3">
              <h3 className="text-sm font-semibold">
                {scheduleForm.id ? t('backups.schedule_edit') : t('backups.schedule_add')}
              </h3>
              <div className="grid gap-3 sm:grid-cols-2">
                <div>
                  <label className="label">{t('domains.name')}</label>
                  <select
                    className="input w-full"
                    value={scheduleForm.domain_id}
                    onChange={(e) =>
                      setScheduleForm((s) => ({ ...s, domain_id: e.target.value ? Number(e.target.value) : '' }))
                    }
                  >
                    <option value="">{t('common.select')}</option>
                    {(domainsQ.data ?? []).map((d) => (
                      <option key={d.id} value={d.id}>
                        {d.name}
                      </option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="label">{t('backups.type')}</label>
                  <select
                    className="input w-full"
                    value={scheduleForm.type}
                    onChange={(e) =>
                      setScheduleForm((s) => ({ ...s, type: e.target.value as 'full' | 'files' | 'database' }))
                    }
                  >
                    <option value="full">{t('backups.type_full')}</option>
                    <option value="files">{t('backups.type_files')}</option>
                    <option value="database">{t('backups.type_database')}</option>
                  </select>
                </div>
                <div>
                  <label className="label">{t('backups.destination')}</label>
                  <select
                    className="input w-full"
                    value={scheduleForm.destination_id}
                    onChange={(e) =>
                      setScheduleForm((s) => ({
                        ...s,
                        destination_id: e.target.value ? Number(e.target.value) : '',
                      }))
                    }
                  >
                    <option value="">{t('backups.destination_local_only')}</option>
                    {destinations.map((d) => (
                      <option key={d.id} value={d.id}>
                        {d.name} ({d.driver})
                      </option>
                    ))}
                  </select>
                </div>
                <div className="flex items-end">
                  <label className="flex items-center gap-2 text-sm">
                    <input
                      type="checkbox"
                      checked={scheduleForm.enabled}
                      onChange={(e) => setScheduleForm((s) => ({ ...s, enabled: e.target.checked }))}
                    />
                    {t('backups.schedule_enabled')}
                  </label>
                </div>
              </div>
              <div>
                <label className="label">{t('backups.schedule_cron')}</label>
                <input
                  className="input w-full font-mono"
                  value={scheduleForm.schedule}
                  onChange={(e) => setScheduleForm((s) => ({ ...s, schedule: e.target.value }))}
                  placeholder="0 3 * * *"
                />
                <p className="mt-1 text-xs text-gray-500">{t('backups.schedule_cron_hint')}</p>
                <div className="mt-2 flex flex-wrap gap-2">
                  <button
                    type="button"
                    className="rounded border border-gray-300 px-2 py-1 text-xs dark:border-gray-600"
                    onClick={() => setScheduleForm((s) => ({ ...s, schedule: '0 3 * * *' }))}
                  >
                    {t('backups.schedule_preset_daily')}
                  </button>
                  <button
                    type="button"
                    className="rounded border border-gray-300 px-2 py-1 text-xs dark:border-gray-600"
                    onClick={() => setScheduleForm((s) => ({ ...s, schedule: '0 4 * * 0' }))}
                  >
                    {t('backups.schedule_preset_weekly')}
                  </button>
                </div>
              </div>
              <div className="flex justify-end gap-2">
                <button
                  type="button"
                  className="btn-secondary"
                  onClick={() => {
                    setShowSchedule(false)
                    resetScheduleForm()
                  }}
                >
                  {t('common.cancel')}
                </button>
                <button
                  type="button"
                  className="btn-primary"
                  disabled={saveScheduleM.isPending || scheduleForm.domain_id === '' || !scheduleForm.schedule.trim()}
                  onClick={() => saveScheduleM.mutate()}
                >
                  {saveScheduleM.isPending ? t('common.loading') : t('common.save')}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
