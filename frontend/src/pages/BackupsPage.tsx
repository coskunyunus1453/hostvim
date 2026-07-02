import { useEffect, useMemo, useRef, useState, useCallback } from 'react'
import { useTranslation } from 'react-i18next'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '../services/api'
import {
  Cloud,
  Download,
  ExternalLink,
  HardDrive,
  HelpCircle,
  Loader2,
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
import { tokenHasAbility } from '../lib/abilities'

type BackupRow = {
  id: number
  domain_id: number
  destination_id?: number | null
  type: string
  level?: number | null
  parent_backup_id?: number | null
  base_backup_id?: number | null
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

const ACTIVE_BACKUP_STATUSES = new Set(['pending', 'queued', 'running', 'syncing'])

function isActiveBackupStatus(status: string): boolean {
  return ACTIVE_BACKUP_STATUSES.has(status)
}

function statusClass(status: string): string {
  switch (status) {
    case 'completed':
      return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
    case 'running':
    case 'pending':
    case 'queued':
      return 'bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200'
    case 'syncing':
      return 'bg-blue-50 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200'
    case 'failed':
      return 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
    default:
      return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'
  }
}

type BackupsListResponse = {
  data: BackupRow[]
  total?: number
}

type OperationUi = {
  type: 'download' | 'restore'
  title: string
  subtitle: string
  phase: 'running' | 'success' | 'error'
  progress: number
  detail: string
  error?: string
}

function restoreDetailForProgress(progress: number, t: (key: string) => string): string {
  if (progress < 25) return t('backups.restore_progress_preparing')
  if (progress < 55) return t('backups.restore_progress_download')
  return t('backups.restore_progress_extract')
}

function isProFeatureBlocked(err: unknown): boolean {
  const ax = err as { response?: { status?: number; data?: { code?: string } } }
  return ax.response?.status === 403 && ax.response?.data?.code === 'pro_license_required'
}

export default function BackupsPage() {
  const { t } = useTranslation()
  const isAdmin = useAuthStore((s) => s.user?.roles?.some((r) => r.name === 'admin'))
  const abilities = useAuthStore((s) => s.user?.abilities)
  const canWrite = tokenHasAbility(abilities, 'backups:write')
  const qc = useQueryClient()
  const { domainId: domainFilter, setDomainId: setDomainFilter, domainsQ } = useAutoDomainId({
    param: 'domain',
  })
  const { domainId: uploadDomainId, setDomainId: setUploadDomainId } = useAutoDomainId({ param: false })
  const uploadRef = useRef<HTMLInputElement>(null)
  const [showAdd, setShowAdd] = useState(false)
  const [showDest, setShowDest] = useState(false)
  const [showSchedule, setShowSchedule] = useState(false)
  const [scheduleForm, setScheduleForm] = useState<{
    id: number | null
    domain_id: number | ''
    destination_id: number | ''
    type: 'full' | 'incremental' | 'files' | 'database'
    full_interval_days: number
    retention_count: number | ''
    schedule: string
    enabled: boolean
  }>({
    id: null,
    domain_id: '',
    destination_id: '',
    type: 'full',
    full_interval_days: 7,
    retention_count: '',
    schedule: '0 3 * * *',
    enabled: true,
  })
  const [restoreTarget, setRestoreTarget] = useState<BackupRow | null>(null)
  const [showGdriveGuide, setShowGdriveGuide] = useState(false)
  const [statusFilter, setStatusFilter] = useState<'all' | 'active' | 'completed' | 'failed'>('all')
  const [downloadingId, setDownloadingId] = useState<number | null>(null)
  const [retryingId, setRetryingId] = useState<number | null>(null)
  const [deletingId, setDeletingId] = useState<number | null>(null)
  const [runningScheduleId, setRunningScheduleId] = useState<number | null>(null)
  const [operationUi, setOperationUi] = useState<OperationUi | null>(null)

  useEffect(() => {
    if (!operationUi || operationUi.phase !== 'running') return
    const timer = window.setInterval(() => {
      setOperationUi((prev) => {
        if (!prev || prev.phase !== 'running') return prev
        if (prev.progress >= 92) return prev
        const step = prev.progress < 35 ? 2.5 : prev.progress < 65 ? 1.2 : 0.5
        const next = Math.min(92, prev.progress + step)
        const detail =
          prev.type === 'restore'
            ? restoreDetailForProgress(next, t)
            : prev.detail
        return { ...prev, progress: next, detail }
      })
    }, 650)
    return () => clearInterval(timer)
  }, [operationUi?.phase, operationUi?.type, t])

  const statusLabel = useCallback(
    (status: string) => {
      const key = `backups.status_${status}` as const
      const translated = t(key)
      return translated === key ? status : translated
    },
    [t],
  )

  const q = useQuery({
    queryKey: ['backups', domainFilter, statusFilter],
    queryFn: async () =>
      (await api.get('/backups', {
        params: {
          per_page: 50,
          ...(domainFilter !== '' ? { domain_id: domainFilter } : {}),
          ...(statusFilter !== 'all' ? { status: statusFilter } : {}),
        },
      })).data as BackupsListResponse,
    refetchInterval: (query) => {
      const list = (query.state.data as BackupsListResponse | undefined)?.data ?? []
      return list.some((b) => isActiveBackupStatus(b.status)) ? 4000 : false
    },
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
    retry: false,
  })

  const gdriveProBlocked = gdriveQ.isError && isProFeatureBlocked(gdriveQ.error)

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
      full_interval_days: 7,
      retention_count: '',
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
        type: (String(row.type ?? 'full') as 'full' | 'incremental' | 'files' | 'database') || 'full',
        full_interval_days: Number(row.full_interval_days ?? 7) || 7,
        retention_count: row.retention_count != null ? Number(row.retention_count) : '',
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
        full_interval_days: scheduleForm.type === 'incremental' ? scheduleForm.full_interval_days : undefined,
        retention_count: scheduleForm.retention_count !== '' ? Number(scheduleForm.retention_count) : undefined,
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
    onMutate: (id) => setRunningScheduleId(id),
    onSuccess: () => {
      setRunningScheduleId(null)
      toast.success(t('backups.schedule_run_started'))
      qc.invalidateQueries({ queryKey: ['backups'] })
    },
    onError: (err: unknown) => {
      setRunningScheduleId(null)
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const createM = useMutation({
    mutationFn: async (payload: { domain_id: number; type: string; destination_id?: number }) => {
      const { data } = await api.post('/backups', payload)
      return data as { backup?: BackupRow; message?: string }
    },
    onMutate: async (payload) => {
      toast.loading(t('backups.starting'), { id: 'backup-create' })
      setShowAdd(false)
      if (domainFilter !== '' && domainFilter !== payload.domain_id) {
        setDomainFilter(payload.domain_id)
      }
      await qc.cancelQueries({ queryKey: ['backups'] })
      const domain = (domainsQ.data ?? []).find((d) => d.id === payload.domain_id)
      const destList = (qc.getQueryData(['backup-destinations']) as { destinations?: DestinationRow[] } | undefined)
        ?.destinations ?? []
      const dest = payload.destination_id
        ? destList.find((d) => d.id === payload.destination_id)
        : undefined
      const optimistic: BackupRow = {
        id: -Date.now(),
        domain_id: payload.domain_id,
        destination_id: payload.destination_id ?? null,
        type: payload.type,
        status: 'queued',
        created_at: new Date().toISOString(),
        domain: domain ? { name: domain.name } : undefined,
        destination: dest ? { id: dest.id, name: dest.name, driver: dest.driver } : undefined,
      }
      qc.setQueriesData<BackupsListResponse>({ queryKey: ['backups'] }, (old) => {
        if (!old?.data) return old
        return { ...old, data: [optimistic, ...old.data] }
      })
      return { optimisticId: optimistic.id }
    },
    onSuccess: (data) => {
      toast.success(data?.message ?? t('backups.queued'), { id: 'backup-create' })
      if (data?.backup) {
        qc.setQueriesData<BackupsListResponse>({ queryKey: ['backups'] }, (old) => {
          if (!old?.data) return old
          const withoutOptimistic = old.data.filter((b) => b.id > 0)
          const exists = withoutOptimistic.some((b) => b.id === data.backup!.id)
          return {
            ...old,
            data: exists
              ? withoutOptimistic.map((b) => (b.id === data.backup!.id ? { ...b, ...data.backup } : b))
              : [data.backup!, ...withoutOptimistic],
          }
        })
      }
      qc.invalidateQueries({ queryKey: ['backups'] })
      qc.invalidateQueries({ queryKey: ['gdrive-files'] })
    },
    onError: (err: unknown, _payload, ctx) => {
      toast.dismiss('backup-create')
      if (ctx?.optimisticId) {
        qc.setQueriesData<BackupsListResponse>({ queryKey: ['backups'] }, (old) => {
          if (!old?.data) return old
          return { ...old, data: old.data.filter((b) => b.id !== ctx.optimisticId) }
        })
      }
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const retryM = useMutation({
    mutationFn: async (id: number) => api.post(`/backups/${id}/retry`),
    onMutate: (id) => setRetryingId(id),
    onSuccess: () => {
      setRetryingId(null)
      toast.success(t('backups.queued'))
      qc.invalidateQueries({ queryKey: ['backups'] })
    },
    onError: (err: unknown) => {
      setRetryingId(null)
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const deleteM = useMutation({
    mutationFn: async (id: number) => api.delete(`/backups/${id}`),
    onMutate: (id) => setDeletingId(id),
    onSuccess: () => {
      setDeletingId(null)
      toast.success(t('backups.deleted'))
      qc.invalidateQueries({ queryKey: ['backups'] })
    },
    onError: (err: unknown) => {
      setDeletingId(null)
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const finishOperation = useCallback((success: boolean, detail: string, error?: string) => {
    setOperationUi((prev) =>
      prev
        ? {
            ...prev,
            phase: success ? 'success' : 'error',
            progress: success ? 100 : prev.progress,
            detail,
            error,
          }
        : prev,
    )
    window.setTimeout(() => setOperationUi(null), success ? 3500 : 6000)
  }, [])

  const runRestoreOperation = useCallback(
    async (subtitle: string, request: () => Promise<unknown>) => {
      setRestoreTarget(null)
      setOperationUi({
        type: 'restore',
        title: t('backups.restore_progress_title'),
        subtitle,
        phase: 'running',
        progress: 4,
        detail: t('backups.restore_progress_preparing'),
      })
      try {
        await request()
        finishOperation(true, t('backups.restore_success'))
      } catch (err: unknown) {
        const ax = err as { response?: { data?: { message?: string } } }
        const msg = ax.response?.data?.message ?? String(err)
        finishOperation(false, t('backups.restore_failed'), msg)
      }
    },
    [finishOperation, t],
  )

  const restoreFromBackup = useCallback(
    (payload: { id: number; source?: 'engine' | 'remote'; subtitle: string }) => {
      void runRestoreOperation(payload.subtitle, () =>
        api.post(`/backups/${payload.id}/restore`, { source: payload.source ?? 'engine' }),
      )
    },
    [runRestoreOperation],
  )

  const restoreFromRemote = useCallback(
    (payload: { domain_id: number; destination_id: number; remote_file_id: string; subtitle: string }) => {
      void runRestoreOperation(payload.subtitle, () =>
        api.post('/backups/restore-remote', {
          domain_id: payload.domain_id,
          destination_id: payload.destination_id,
          remote_file_id: payload.remote_file_id,
        }),
      )
    },
    [runRestoreOperation],
  )

  const restoreUploadBusy = useRef(false)

  const restoreFromUpload = useCallback(
    async (payload: { domain_id: number; file: File; subtitle: string }) => {
      if (restoreUploadBusy.current) return
      restoreUploadBusy.current = true
      setOperationUi({
        type: 'restore',
        title: t('backups.restore_progress_title'),
        subtitle: payload.subtitle,
        phase: 'running',
        progress: 2,
        detail: t('backups.restore_progress_upload'),
      })
      const fd = new FormData()
      fd.append('domain_id', String(payload.domain_id))
      fd.append('archive', payload.file)
      try {
        await api.post('/backups/restore-upload', fd, {
          onUploadProgress: (ev) => {
            if (!ev.total) return
            const uploadPct = Math.round((ev.loaded / ev.total) * 45)
            setOperationUi((prev) =>
              prev
                ? {
                    ...prev,
                    progress: Math.max(prev.progress, uploadPct),
                    detail: t('backups.restore_progress_upload'),
                  }
                : prev,
            )
          },
        })
        finishOperation(true, t('backups.restore_success'))
      } catch (err: unknown) {
        const ax = err as { response?: { data?: { message?: string } } }
        finishOperation(false, t('backups.restore_failed'), ax.response?.data?.message ?? String(err))
      } finally {
        restoreUploadBusy.current = false
      }
    },
    [finishOperation, t],
  )

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

  const downloadBackup = async (id: number, filename: string, sizeMb?: number | null) => {
    const safeName = filename.endsWith('.tar.gz') ? filename : `${filename}.tar.gz`
    setDownloadingId(id)
    setOperationUi({
      type: 'download',
      title: t('backups.download_progress_title'),
      subtitle: safeName,
      phase: 'running',
      progress: 2,
      detail: t('backups.download_preparing'),
    })
    try {
      const res = await api.get(`/backups/${id}/download`, {
        responseType: 'blob',
        timeout: 0,
        onDownloadProgress: (ev) => {
          if (ev.total && ev.total > 0) {
            const pct = Math.min(99, Math.round((ev.loaded / ev.total) * 100))
            setOperationUi((prev) =>
              prev
                ? {
                    ...prev,
                    progress: pct,
                    detail: t('backups.download_progress_percent', { pct }),
                  }
                : prev,
            )
          } else if (ev.loaded > 0) {
            const mb = (ev.loaded / 1048576).toFixed(1)
            const guess = sizeMb && sizeMb > 0 ? Math.min(95, Math.round((ev.loaded / (sizeMb * 1048576)) * 100)) : undefined
            setOperationUi((prev) =>
              prev
                ? {
                    ...prev,
                    progress: guess ?? Math.min(90, prev.progress + 0.5),
                    detail: t('backups.download_progress_bytes', { mb }),
                  }
                : prev,
            )
          }
        },
      })
      const url = window.URL.createObjectURL(res.data)
      const a = document.createElement('a')
      a.href = url
      a.download = safeName
      a.click()
      window.URL.revokeObjectURL(url)
      finishOperation(true, t('backups.download_success'))
    } catch (err: unknown) {
      const ax = err as { response?: { data?: { message?: string } } }
      finishOperation(false, t('backups.download_failed'), ax.response?.data?.message ?? String(err))
    } finally {
      setDownloadingId(null)
    }
  }

  const rows: BackupRow[] = q.data?.data ?? []
  const destinations = destQ.data?.destinations ?? []
  const schedules = (scheduleQ.data?.schedules ?? []) as Array<Record<string, unknown>>
  const gdriveFiles = gdriveFilesQ.data?.files ?? []
  const activeRows = useMemo(() => rows.filter((b) => isActiveBackupStatus(b.status)), [rows])
  const hasActiveBackups = activeRows.length > 0
  // Listedeki arttırımlı yedeklerin taban (base) aldığı yedek id'leri → "base" rozeti için.
  const baseBackupIds = useMemo(() => {
    const set = new Set<number>()
    for (const b of rows) {
      if (b.base_backup_id != null) set.add(Number(b.base_backup_id))
    }
    return set
  }, [rows])

  const defaultDest = useMemo(
    () => destinations.find((d) => d.is_default) ?? destinations.find((d) => d.driver === 'google_drive'),
    [destinations],
  )

  const renderBackupRow = (b: BackupRow) => {
    const active = isActiveBackupStatus(b.status)
    return (
      <div
        key={b.id}
        className={clsx(
          'p-4 flex flex-wrap items-center justify-between gap-3 hover:bg-gray-50/80 dark:hover:bg-gray-800/30',
          active && 'bg-amber-50/40 dark:bg-amber-950/20',
        )}
      >
        <div className="min-w-0 flex-1">
          <div className="flex flex-wrap items-center gap-2">
            {active && <Loader2 className="h-3.5 w-3.5 animate-spin text-amber-600 shrink-0" />}
            <span className="font-medium text-gray-900 dark:text-white">{b.domain?.name ?? b.domain_id}</span>
            <span className={clsx('rounded-full px-2 py-0.5 text-xs font-medium', statusClass(b.status))}>
              {statusLabel(b.status)}
            </span>
            <span className="text-xs font-mono text-gray-500 uppercase">{b.type}</span>
            {b.level != null && b.level > 0 ? (
              <span className="rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200">
                {t('backups.level_incremental', { level: b.level })}
              </span>
            ) : baseBackupIds.has(b.id) ? (
              <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200">
                {t('backups.level_base')}
              </span>
            ) : null}
          </div>
          <p className="text-xs text-gray-500 mt-1">
            {new Date(b.created_at).toLocaleString()}
            {b.size_mb != null ? ` · ${formatBytes(b.size_mb)}` : ''}
            {b.destination ? ` · ${b.destination.name}` : ''}
            {b.remote_file_id ? ` · Drive ✓` : b.remote_path ? ` · ☁` : ''}
          </p>
        </div>
        <div className="flex items-center gap-1">
          {canWrite && b.status === 'failed' && (
            <button
              type="button"
              className="btn-secondary text-xs py-1.5 px-2 inline-flex items-center gap-1"
              disabled={retryingId === b.id}
              onClick={() => retryM.mutate(b.id)}
            >
              {retryingId === b.id ? <Loader2 className="h-3 w-3 animate-spin" /> : null}
              {t('backups.retry')}
            </button>
          )}
          {canWrite && (b.status === 'running' || b.status === 'pending') && !b.engine_backup_id && (
            <button
              type="button"
              className="btn-secondary text-xs py-1.5 px-2 inline-flex items-center gap-1"
              disabled={retryingId === b.id}
              onClick={() => retryM.mutate(b.id)}
            >
              {retryingId === b.id ? <Loader2 className="h-3 w-3 animate-spin" /> : null}
              {t('backups.retry')}
            </button>
          )}
          <button
            type="button"
            className="p-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 text-blue-600 disabled:opacity-40"
            title={t('backups.download')}
            disabled={active || downloadingId === b.id || operationUi?.phase === 'running'}
            onClick={() => downloadBackup(b.id, `${b.domain?.name ?? 'backup'}_${b.id}.tar.gz`, b.size_mb)}
          >
            {downloadingId === b.id ? (
              <Loader2 className="h-4 w-4 animate-spin" />
            ) : (
              <Download className="h-4 w-4" />
            )}
          </button>
          {canWrite && (
            <button
              type="button"
              className="p-2 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 text-indigo-600 disabled:opacity-40"
              title={t('backups.restore')}
              disabled={(!b.engine_backup_id?.trim() && !b.remote_file_id) || active || operationUi?.phase === 'running'}
              onClick={() => setRestoreTarget(b)}
            >
              <RotateCcw className="h-4 w-4" />
            </button>
          )}
          {canWrite && (
            <button
              type="button"
              className="p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 disabled:opacity-40"
              disabled={(active && b.id > 0) || deletingId === b.id}
              onClick={() => {
                if (window.confirm(t('common.confirm_delete'))) deleteM.mutate(b.id)
              }}
            >
              {deletingId === b.id ? (
                <Loader2 className="h-4 w-4 animate-spin" />
              ) : (
                <Trash2 className="h-4 w-4" />
              )}
            </button>
          )}
        </div>
      </div>
    )
  }

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
        {canWrite && (
          <button type="button" className="btn-primary flex items-center gap-2" onClick={() => setShowAdd(true)}>
            <Plus className="h-4 w-4" />
            {t('backups.new_backup')}
          </button>
        )}
      </div>

      {!canWrite && (
        <p className="text-sm text-amber-700 dark:text-amber-300">{t('backups.read_only_hint')}</p>
      )}

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
        <div className="min-w-[180px]">
          <label className="label">{t('backups.filter_status')}</label>
          <select
            className="input w-full"
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value as typeof statusFilter)}
          >
            <option value="all">{t('backups.filter_status_all')}</option>
            <option value="active">{t('backups.filter_status_active')}</option>
            <option value="completed">{t('backups.filter_status_completed')}</option>
            <option value="failed">{t('backups.filter_status_failed')}</option>
          </select>
        </div>
        <button type="button" className="btn-secondary" onClick={() => setShowDest(true)}>
          <Server className="h-4 w-4 inline mr-1" />
          {t('backups.destinations')}
        </button>
        {canWrite && (
          <button type="button" className="btn-secondary" onClick={() => openScheduleEditor()}>
            <Play className="h-4 w-4 inline mr-1" />
            {t('backups.schedules')}
          </button>
        )}
      </div>

      {/* Google Drive card */}
      {!gdriveProBlocked && (
      <div className="card p-5 border-l-4 border-l-blue-500">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div className="flex items-start gap-3">
            <Cloud className="h-7 w-7 text-blue-500 shrink-0 mt-0.5" />
            <div>
              <h2 className="font-semibold text-gray-900 dark:text-white">{t('backups.google_drive_title')}</h2>
              {isAdmin ? (
                <>
                  <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">{t('backups.google_drive_hint')}</p>
                  {gdriveQ.data?.connected && gdriveQ.data.destination && (
                    <p className="text-xs text-emerald-600 dark:text-emerald-400 mt-2">
                      {t('backups.google_connected_as', {
                        email: gdriveQ.data.destination.email ?? gdriveQ.data.destination.name,
                      })}
                    </p>
                  )}
                  {!gdriveQ.data?.configured && (
                    <p className="text-xs text-amber-700 dark:text-amber-300 mt-2">
                      {gdriveQ.data?.hub_expected
                        ? t('backups.google_not_configured_hub')
                        : t('backups.google_not_configured_admin')}
                    </p>
                  )}
                  {gdriveQ.data?.configured && gdriveQ.data.credential_source === 'hub' && (
                    <p className="text-xs text-emerald-700 dark:text-emerald-300 mt-2">{t('backups.google_configured_hub')}</p>
                  )}
                  {gdriveQ.data?.configured && !gdriveQ.data?.connected && (
                    <p className="text-xs text-blue-700 dark:text-blue-300 mt-2">{t('backups.google_connect_hint')}</p>
                  )}
                </>
              ) : (
                <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                  {gdriveQ.data?.connected && gdriveQ.data.destination
                    ? t('backups.google_connected_account', {
                        account: gdriveQ.data.destination.email ?? gdriveQ.data.destination.name,
                      })
                    : !gdriveQ.data?.configured
                      ? t('backups.google_not_configured_customer')
                      : t('backups.google_drive_hint_customer')}
                </p>
              )}
            </div>
          </div>
          <div className="flex flex-wrap gap-2">
            {isAdmin && (
              <button
                type="button"
                className="btn-secondary text-sm inline-flex items-center gap-1.5"
                onClick={() => setShowGdriveGuide(true)}
              >
                <HelpCircle className="h-4 w-4" />
                {gdriveQ.data?.configured ? t('backups.google_connect_guide_btn') : t('backups.google_setup_guide_btn')}
              </button>
            )}
            {canWrite && (gdriveQ.data?.connected ? (
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
            ))}
          </div>
        </div>

        {gdriveQ.data?.connected && gdriveFiles.length > 0 && (
          <div className="mt-4">
            <h3 className="text-sm font-medium text-gray-800 dark:text-gray-200 mb-2">{t('backups.google_files')}</h3>
            {gdriveFilesQ.isLoading && <p className="text-sm text-gray-500">{t('common.loading')}</p>}
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
                    disabled={operationUi?.phase === 'running' || domainFilter === '' || !canWrite}
                    title={domainFilter === '' ? t('backups.select_domain_restore') : undefined}
                    onClick={() => {
                      if (domainFilter === '') {
                        toast.error(t('backups.select_domain_restore'))
                        return
                      }
                      if (!window.confirm(t('backups.restore_confirm', { name: f.name }))) return
                      const domainName = (domainsQ.data ?? []).find((d) => d.id === domainFilter)?.name ?? String(domainFilter)
                      restoreFromRemote({
                        domain_id: domainFilter as number,
                        destination_id: gdriveQ.data!.destination!.id,
                        remote_file_id: f.id,
                        subtitle: `${domainName} · ${f.name}`,
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
      )}

      {gdriveProBlocked && (
        <div className="card p-4 text-sm text-gray-600 dark:text-gray-400 border-l-4 border-l-gray-300">
          <p>{t('backups.pro_feature_required')}</p>
        </div>
      )}

      {/* Upload restore */}
      {canWrite && (
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
              const domainName = (domainsQ.data ?? []).find((d) => d.id === uploadDomainId)?.name ?? String(uploadDomainId)
              void restoreFromUpload({ domain_id: uploadDomainId as number, file, subtitle: `${domainName} · ${file.name}` })
              e.target.value = ''
            }}
          />
          <button
            type="button"
            className="btn-primary"
            disabled={uploadDomainId === '' || operationUi?.phase === 'running'}
            onClick={() => uploadRef.current?.click()}
          >
            <HardDrive className="h-4 w-4 inline mr-1" />
            {t('backups.upload_restore_btn')}
          </button>
        </div>
      </div>
      )}

      {hasActiveBackups && statusFilter !== 'active' && (
        <div className="card overflow-hidden border-l-4 border-l-amber-400">
          <div className="px-4 py-3 border-b border-gray-100 dark:border-gray-800 bg-amber-50/50 dark:bg-amber-950/20">
            <div className="flex items-center gap-2">
              <Loader2 className="h-4 w-4 animate-spin text-amber-600" />
              <h2 className="font-semibold text-gray-900 dark:text-white">{t('backups.active_title')}</h2>
              <span className="rounded-full bg-amber-200/80 px-2 py-0.5 text-xs font-medium text-amber-900 dark:bg-amber-800 dark:text-amber-100">
                {activeRows.length}
              </span>
            </div>
            <p className="text-xs text-gray-500 mt-1">{t('backups.active_hint')}</p>
          </div>
          <div className="divide-y divide-gray-100 dark:divide-gray-800">
            {activeRows.map((b) => renderBackupRow(b))}
          </div>
        </div>
      )}

      {/* Backup list */}
      <div className="card overflow-hidden">
        <div className="px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex flex-wrap items-center justify-between gap-2">
          <h2 className="font-semibold text-gray-900 dark:text-white">{t('backups.list_title')}</h2>
          {q.data?.total != null && (
            <span className="text-xs text-gray-500">{t('backups.total_count', { count: q.data.total })}</span>
          )}
        </div>
        {domainFilter !== '' && (
          <p className="px-4 py-2 text-xs text-amber-700 bg-amber-50/60 dark:bg-amber-950/20 dark:text-amber-300 border-b border-amber-100 dark:border-amber-900/40">
            {t('backups.domain_filter_hint')}
          </p>
        )}
        {q.isError ? (
          <div className="px-4 py-8 text-center">
            <p className="text-sm text-red-700 dark:text-red-300">{t('backups.load_error')}</p>
            <button type="button" className="btn-secondary mt-3 text-sm" onClick={() => void q.refetch()}>
              {t('domains.refresh')}
            </button>
          </div>
        ) : q.isLoading ? (
          <p className="p-6 text-center text-gray-500">{t('common.loading')}</p>
        ) : rows.length === 0 ? (
          <p className="p-6 text-center text-gray-500">{t('backups.empty')}</p>
        ) : null}
        {!q.isLoading && !q.isError && (
        <div className="divide-y divide-gray-100 dark:divide-gray-800">
          {rows
            .filter((b) => !hasActiveBackups || statusFilter === 'active' || !isActiveBackupStatus(b.status))
            .map((b) => renderBackupRow(b))}
        </div>
        )}
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

      {/* Download / restore progress */}
      {operationUi && (
        <div className="fixed inset-0 z-[60] flex items-center justify-center bg-black/55 p-4">
          <div
            className="card max-w-md w-full p-6 space-y-4 bg-white dark:bg-gray-900"
            role="status"
            aria-live="polite"
          >
            <div className="flex items-start gap-3">
              {operationUi.phase === 'running' && (
                <Loader2 className="h-6 w-6 shrink-0 animate-spin text-blue-500 mt-0.5" />
              )}
              {operationUi.phase === 'success' && (
                <div className="h-6 w-6 shrink-0 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-sm font-bold">
                  ✓
                </div>
              )}
              {operationUi.phase === 'error' && (
                <div className="h-6 w-6 shrink-0 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-sm font-bold">
                  !
                </div>
              )}
              <div className="min-w-0">
                <h2 className="text-lg font-semibold text-gray-900 dark:text-white">{operationUi.title}</h2>
                <p className="text-sm text-gray-500 truncate">{operationUi.subtitle}</p>
              </div>
            </div>
            <div className="space-y-2">
              <div className="flex justify-between text-xs text-gray-600 dark:text-gray-400">
                <span>{operationUi.detail}</span>
                <span>{Math.round(operationUi.progress)}%</span>
              </div>
              <div className="h-2.5 w-full rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                <div
                  className={clsx(
                    'h-full rounded-full transition-[width] duration-300 ease-out',
                    operationUi.phase === 'error'
                      ? 'bg-rose-500'
                      : operationUi.phase === 'success'
                        ? 'bg-emerald-500'
                        : 'bg-blue-500',
                  )}
                  style={{ width: `${Math.max(operationUi.phase === 'success' ? 100 : 4, operationUi.progress)}%` }}
                />
              </div>
              {operationUi.phase === 'running' && (
                <p className="text-xs text-gray-500 dark:text-gray-400">
                  {operationUi.type === 'download' ? t('backups.download_wait_hint') : t('backups.restore_wait_hint')}
                </p>
              )}
              {operationUi.phase === 'error' && operationUi.error && (
                <p className="text-sm text-rose-600 dark:text-rose-400">{operationUi.error}</p>
              )}
            </div>
            {operationUi.phase !== 'running' && (
              <button type="button" className="btn-secondary w-full" onClick={() => setOperationUi(null)}>
                {t('common.close')}
              </button>
            )}
          </div>
        </div>
      )}

      {/* Restore modal */}
      {restoreTarget && !operationUi && canWrite && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="card max-w-md w-full p-6 space-y-4 bg-white dark:bg-gray-900">
            <h2 className="text-lg font-semibold">{t('backups.restore_title')}</h2>
            <p className="text-sm text-gray-500">{restoreTarget.domain?.name} · {restoreTarget.type}</p>
            <div className="space-y-2">
              {restoreTarget.engine_backup_id?.trim() && (
                <button
                  type="button"
                  className="btn-primary w-full"
                  onClick={() => {
                    if (!window.confirm(t('backups.restore_confirm', { name: restoreTarget.domain?.name ?? '' }))) return
                    restoreFromBackup({
                      id: restoreTarget.id,
                      source: 'engine',
                      subtitle: `${restoreTarget.domain?.name ?? ''} · ${restoreTarget.type}`,
                    })
                  }}
                >
                  {t('backups.restore_from_server')}
                </button>
              )}
              {restoreTarget.remote_file_id && restoreTarget.destination_id && (
                <button
                  type="button"
                  className="btn-secondary w-full"
                  onClick={() => {
                    if (!window.confirm(t('backups.restore_confirm', { name: 'Google Drive' }))) return
                    restoreFromRemote({
                      domain_id: restoreTarget.domain_id,
                      destination_id: restoreTarget.destination_id!,
                      remote_file_id: restoreTarget.remote_file_id!,
                      subtitle: `${restoreTarget.domain?.name ?? ''} · Google Drive`,
                    })
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
      {showAdd && canWrite && (
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
                  <option value="incremental">{t('backups.type_incremental')}</option>
                  <option value="files">{t('backups.type_files')}</option>
                  <option value="database">{t('backups.type_database')}</option>
                </select>
                <p className="mt-1 text-xs text-gray-500">{t('backups.type_incremental_hint')}</p>
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
                <button type="submit" className="btn-primary inline-flex items-center gap-2" disabled={createM.isPending}>
                  {createM.isPending && <Loader2 className="h-4 w-4 animate-spin" />}
                  {createM.isPending ? t('backups.starting') : t('backups.create_btn')}
                </button>
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

      {showSchedule && canWrite && (
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
                        className="btn-secondary text-xs py-1 inline-flex items-center gap-1"
                        disabled={runningScheduleId === Number(s.id)}
                        onClick={() => runScheduleM.mutate(Number(s.id))}
                      >
                        {runningScheduleId === Number(s.id) && (
                          <Loader2 className="h-3 w-3 animate-spin" />
                        )}
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
                      setScheduleForm((s) => ({ ...s, type: e.target.value as 'full' | 'incremental' | 'files' | 'database' }))
                    }
                  >
                    <option value="full">{t('backups.type_full')}</option>
                    <option value="incremental">{t('backups.type_incremental')}</option>
                    <option value="files">{t('backups.type_files')}</option>
                    <option value="database">{t('backups.type_database')}</option>
                  </select>
                </div>
                {scheduleForm.type === 'incremental' && (
                  <div>
                    <label className="label">{t('backups.full_interval_days')}</label>
                    <input
                      type="number"
                      min={1}
                      max={365}
                      className="input w-full"
                      value={scheduleForm.full_interval_days}
                      onChange={(e) =>
                        setScheduleForm((s) => ({ ...s, full_interval_days: Math.max(1, Number(e.target.value) || 7) }))
                      }
                    />
                    <p className="mt-1 text-xs text-gray-500">{t('backups.full_interval_days_hint')}</p>
                  </div>
                )}
                <div>
                  <label className="label">{t('backups.retention_count')}</label>
                  <input
                    type="number"
                    min={1}
                    max={100}
                    className="input w-full"
                    placeholder={t('backups.retention_count_placeholder')}
                    value={scheduleForm.retention_count}
                    onChange={(e) =>
                      setScheduleForm((s) => ({ ...s, retention_count: e.target.value === '' ? '' : Math.max(1, Number(e.target.value) || 1) }))
                    }
                  />
                  <p className="mt-1 text-xs text-gray-500">{t('backups.retention_count_hint')}</p>
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
