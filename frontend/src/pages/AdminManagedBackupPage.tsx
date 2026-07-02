import { useEffect, useState } from 'react'
import { Navigate } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { HardDriveDownload, Save, Loader2, Cloud, Trash2, RefreshCw, AlertTriangle, CheckCircle2 } from 'lucide-react'
import toast from 'react-hot-toast'
import { useAuthStore } from '../store/authStore'
import api from '../services/api'

type PoolAccount = {
  id: number
  name: string
  email: string | null
  folder_name: string | null
  is_active: boolean
  assigned_sites: number
}

type ManagedStatus = {
  settings: {
    enabled: boolean
    hour: number
    retention_count: number
    full_interval_days: number
    notify_email: string
    folder_name: string
  }
  configured: boolean
  pool: PoolAccount[]
  pool_count: number
  managed_schedules: number
  managed_enabled: number
  active_domains: number
  last_run_at: string | null
  failures_24h: { id: number; domain: string | null; type: string; updated_at: string | null }[]
  failures_24h_count: number
  credential_source?: string
  redirect_uri?: string
}

export default function AdminManagedBackupPage() {
  const qc = useQueryClient()
  const user = useAuthStore((s) => s.user)
  const isAdmin = user?.roles?.some((r) => r.name === 'admin')

  const q = useQuery({
    queryKey: ['admin-managed-backup'],
    queryFn: async () => (await api.get<ManagedStatus>('/admin/settings/managed-backup')).data,
    enabled: !!isAdmin,
  })

  const [enabled, setEnabled] = useState(false)
  const [hour, setHour] = useState(3)
  const [retention, setRetention] = useState(7)
  const [fullInterval, setFullInterval] = useState(7)
  const [notifyEmail, setNotifyEmail] = useState('')
  const [folderName, setFolderName] = useState('HostVim Managed Backups')

  useEffect(() => {
    if (!q.data) return
    const s = q.data.settings
    setEnabled(s.enabled)
    setHour(s.hour)
    setRetention(s.retention_count)
    setFullInterval(s.full_interval_days)
    setNotifyEmail(s.notify_email || '')
    setFolderName(s.folder_name || 'HostVim Managed Backups')
  }, [q.data])

  const saveM = useMutation({
    mutationFn: async () =>
      api.put('/admin/settings/managed-backup', {
        enabled,
        hour,
        retention_count: retention,
        full_interval_days: fullInterval,
        notify_email: notifyEmail,
        folder_name: folderName,
      }),
    onSuccess: () => {
      toast.success('Ayarlar kaydedildi ve zamanlamalar senkronlandı.')
      qc.invalidateQueries({ queryKey: ['admin-managed-backup'] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const runNowM = useMutation({
    mutationFn: async () => api.post('/admin/settings/managed-backup/run-now'),
    onSuccess: (res) => {
      const p = (res?.data as { provision?: { error?: string; created?: number; updated?: number } })?.provision
      if (p?.error === 'no_pool') toast.error('Önce en az bir Google Drive hesabı bağlayın.')
      else toast.success(`Senkronlandı — yeni: ${p?.created ?? 0}, güncel: ${p?.updated ?? 0}`)
      qc.invalidateQueries({ queryKey: ['admin-managed-backup'] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const connectM = useMutation({
    mutationFn: async () => (await api.get<{ url: string }>('/admin/settings/managed-backup/auth-url')).data,
    onSuccess: (data) => {
      if (data?.url) window.location.href = data.url
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const disconnectM = useMutation({
    mutationFn: async (id: number) => api.delete(`/admin/settings/managed-backup/accounts/${id}`),
    onSuccess: () => {
      toast.success('Hesap kaldırıldı, siteler kalan hesaplara dağıtıldı.')
      qc.invalidateQueries({ queryKey: ['admin-managed-backup'] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  if (!isAdmin) return <Navigate to="/" replace />

  const d = q.data

  return (
    <div className="space-y-6 max-w-3xl">
      <div className="flex items-center gap-3">
        <HardDriveDownload className="h-8 w-8 text-secondary-500" />
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Merkezi Otomatik Yedekleme</h1>
          <p className="text-sm text-gray-500 dark:text-gray-400">
            Tüm hosting sitelerini (VPS/VDS hariç) her gün şirketin Google Drive hesaplarına yedekler.
          </p>
        </div>
      </div>

      {q.isLoading ? (
        <div className="flex items-center gap-2 text-gray-500">
          <Loader2 className="h-5 w-5 animate-spin" /> Yükleniyor…
        </div>
      ) : (
        <>
          {d && !d.configured && (
            <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/40">
              <p className="text-sm text-amber-900 dark:text-amber-100">
                Google Drive uygulama kimliği yapılandırılmamış. Hesap bağlayabilmek için önce Google Drive
                entegrasyonunu (client_id/secret) yapılandırın.
              </p>
            </div>
          )}

          {/* Özet kartları */}
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div className="card p-4">
              <p className="text-xs text-gray-500">Aktif site</p>
              <p className="text-xl font-bold">{d?.active_domains ?? 0}</p>
            </div>
            <div className="card p-4">
              <p className="text-xs text-gray-500">Kapsanan (aktif zamanlama)</p>
              <p className="text-xl font-bold">{d?.managed_enabled ?? 0}</p>
            </div>
            <div className="card p-4">
              <p className="text-xs text-gray-500">Drive hesabı</p>
              <p className="text-xl font-bold">{d?.pool_count ?? 0}</p>
            </div>
            <div className="card p-4">
              <p className="text-xs text-gray-500">Son 24s başarısız</p>
              <p className={`text-xl font-bold ${(d?.failures_24h_count ?? 0) > 0 ? 'text-red-600' : ''}`}>
                {d?.failures_24h_count ?? 0}
              </p>
            </div>
          </div>

          {/* Ayarlar */}
          <form
            className="card p-6 space-y-4"
            onSubmit={(e) => {
              e.preventDefault()
              saveM.mutate()
            }}
          >
            <label className="flex items-center gap-3 cursor-pointer">
              <input type="checkbox" checked={enabled} onChange={(e) => setEnabled(e.target.checked)} />
              <span className="text-sm font-medium">Merkezi otomatik yedeklemeyi etkinleştir</span>
            </label>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <label className="label">Günlük yedek saati</label>
                <select className="input w-full" value={hour} onChange={(e) => setHour(Number(e.target.value))}>
                  {Array.from({ length: 24 }, (_, i) => i).map((h) => (
                    <option key={h} value={h}>
                      {String(h).padStart(2, '0')}:00
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="label">Saklanacak tam yedek zinciri (retention)</label>
                <input
                  type="number"
                  min={1}
                  max={100}
                  className="input w-full"
                  value={retention}
                  onChange={(e) => setRetention(Number(e.target.value))}
                />
              </div>
              <div>
                <label className="label">Tam yedek aralığı (gün)</label>
                <input
                  type="number"
                  min={1}
                  max={365}
                  className="input w-full"
                  value={fullInterval}
                  onChange={(e) => setFullInterval(Number(e.target.value))}
                />
                <p className="mt-1 text-xs text-gray-500">Arada kalan günler artımlı (sadece değişenler) yedeklenir.</p>
              </div>
              <div>
                <label className="label">Drive klasör adı (yeni hesaplar için)</label>
                <input className="input w-full" value={folderName} onChange={(e) => setFolderName(e.target.value)} />
              </div>
            </div>

            <div>
              <label className="label">Başarısızlık bildirimi e-postası</label>
              <input
                className="input w-full"
                value={notifyEmail}
                onChange={(e) => setNotifyEmail(e.target.value)}
                placeholder="ekip@hostvim.com, admin@hostvim.com"
              />
              <p className="mt-1 text-xs text-gray-500">
                Boş bırakılırsa admin kullanıcıların e-postalarına gönderilir. Günde 1 özet mail.
              </p>
            </div>

            <div className="flex items-center gap-3">
              <button type="submit" className="btn-primary flex items-center gap-2" disabled={saveM.isPending}>
                {saveM.isPending ? <Loader2 className="h-4 w-4 animate-spin" /> : <Save className="h-4 w-4" />}
                Kaydet
              </button>
              <button
                type="button"
                className="btn-secondary flex items-center gap-2"
                onClick={() => runNowM.mutate()}
                disabled={runNowM.isPending}
              >
                {runNowM.isPending ? <Loader2 className="h-4 w-4 animate-spin" /> : <RefreshCw className="h-4 w-4" />}
                Şimdi Senkronla
              </button>
            </div>
          </form>

          {/* Google Drive havuzu */}
          <div className="card p-6 space-y-4">
            <div className="flex items-center justify-between">
              <h2 className="text-lg font-semibold flex items-center gap-2">
                <Cloud className="h-5 w-5 text-secondary-500" /> Google Drive Hesapları
              </h2>
              <button
                type="button"
                className="btn-primary flex items-center gap-2"
                onClick={() => connectM.mutate()}
                disabled={connectM.isPending || (d && !d.configured)}
              >
                {connectM.isPending ? <Loader2 className="h-4 w-4 animate-spin" /> : <Cloud className="h-4 w-4" />}
                Hesap Bağla
              </button>
            </div>

            {(!d?.pool || d.pool.length === 0) ? (
              <p className="text-sm text-gray-500">
                Henüz hesap bağlanmadı. Yedeklerin gönderileceği en az bir Google Drive hesabı bağlayın. Birden fazla
                hesap bağlarsanız siteler hesaplara otomatik dağıtılır.
              </p>
            ) : (
              <div className="divide-y divide-gray-200 dark:divide-gray-700">
                {d.pool.map((a) => (
                  <div key={a.id} className="flex items-center justify-between py-3">
                    <div>
                      <p className="font-medium">{a.email || a.name}</p>
                      <p className="text-xs text-gray-500">
                        Klasör: {a.folder_name || '—'} · Atanan site: <strong>{a.assigned_sites}</strong>
                        {!a.is_active && <span className="ml-2 text-red-500">(pasif)</span>}
                      </p>
                    </div>
                    <button
                      type="button"
                      className="btn-danger flex items-center gap-1 text-sm"
                      onClick={() => {
                        if (confirm(`${a.email || a.name} hesabını kaldır? Siteler kalan hesaplara dağıtılacak.`))
                          disconnectM.mutate(a.id)
                      }}
                      disabled={disconnectM.isPending}
                    >
                      <Trash2 className="h-4 w-4" /> Kaldır
                    </button>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Başarısızlıklar */}
          <div className="card p-6 space-y-3">
            <h2 className="text-lg font-semibold flex items-center gap-2">
              {(d?.failures_24h_count ?? 0) > 0 ? (
                <AlertTriangle className="h-5 w-5 text-red-500" />
              ) : (
                <CheckCircle2 className="h-5 w-5 text-green-500" />
              )}
              Son 24 Saat Başarısız Yedekler
            </h2>
            {(!d?.failures_24h || d.failures_24h.length === 0) ? (
              <p className="text-sm text-gray-500">Başarısız yedek yok.</p>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="text-left text-gray-500">
                      <th className="py-2">Site</th>
                      <th className="py-2">Tür</th>
                      <th className="py-2">Zaman</th>
                    </tr>
                  </thead>
                  <tbody>
                    {d.failures_24h.map((f) => (
                      <tr key={f.id} className="border-t border-gray-100 dark:border-gray-800">
                        <td className="py-2">{f.domain || `#${f.id}`}</td>
                        <td className="py-2">{f.type}</td>
                        <td className="py-2">{f.updated_at ? new Date(f.updated_at).toLocaleString('tr-TR') : '—'}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </>
      )}
    </div>
  )
}
