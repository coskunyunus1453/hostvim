import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Navigate } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useAuthStore } from '../store/authStore'
import api from '../services/api'
import { Package, Plus, Trash2, Pencil } from 'lucide-react'
import toast from 'react-hot-toast'

type Pkg = {
  id: number
  name: string
  slug: string
  description?: string | null
  disk_space_mb: number
  bandwidth_mb: number
  max_domains: number
  max_subdomains: number
  max_databases: number
  max_email_accounts: number
  max_ftp_accounts: number
  max_cron_jobs: number
  cpu_limit?: number | null
  memory_limit_mb?: number | null
  inode_limit?: number | null
  ssl_enabled: boolean
  backup_enabled: boolean
  price_monthly: string | number
  price_yearly: string | number
  currency: string
  is_active: boolean
}

type FormState = {
  name: string
  slug: string
  description: string
  disk_space_mb: number
  bandwidth_mb: number
  max_domains: number
  max_subdomains: number
  max_databases: number
  max_email_accounts: number
  max_ftp_accounts: number
  max_cron_jobs: number
  cpu_limit: number
  memory_limit_mb: number
  inode_limit: number
  ssl_enabled: boolean
  backup_enabled: boolean
  price_monthly: number
  price_yearly: number
  currency: string
  is_active: boolean
}

const emptyForm: FormState = {
  name: '',
  slug: '',
  description: '',
  disk_space_mb: 5000,
  bandwidth_mb: 50000,
  max_domains: 1,
  max_subdomains: 10,
  max_databases: 5,
  max_email_accounts: 10,
  max_ftp_accounts: 5,
  max_cron_jobs: 5,
  cpu_limit: 100,
  memory_limit_mb: 1024,
  inode_limit: -1,
  ssl_enabled: true,
  backup_enabled: true,
  price_monthly: 9.99,
  price_yearly: 99,
  currency: 'TRY',
  is_active: true,
}

function fromPkg(p: Pkg): FormState {
  return {
    name: p.name,
    slug: p.slug,
    description: p.description ?? '',
    disk_space_mb: p.disk_space_mb,
    bandwidth_mb: p.bandwidth_mb,
    max_domains: p.max_domains,
    max_subdomains: p.max_subdomains,
    max_databases: p.max_databases,
    max_email_accounts: p.max_email_accounts,
    max_ftp_accounts: p.max_ftp_accounts,
    max_cron_jobs: p.max_cron_jobs,
    cpu_limit: p.cpu_limit ?? 100,
    memory_limit_mb: p.memory_limit_mb ?? 1024,
    inode_limit: p.inode_limit ?? -1,
    ssl_enabled: p.ssl_enabled,
    backup_enabled: p.backup_enabled,
    price_monthly: Number(p.price_monthly),
    price_yearly: Number(p.price_yearly),
    currency: p.currency,
    is_active: p.is_active,
  }
}

const fmtLimit = (v: number) => (v < 0 ? 'Limitsiz' : String(v))

export default function AdminPackagesPage() {
  const { t } = useTranslation()
  const qc = useQueryClient()
  const isAdmin = useAuthStore((s) => s.user?.roles?.some((r) => r.name === 'admin'))
  const [editing, setEditing] = useState<Pkg | null>(null)
  const [showForm, setShowForm] = useState(false)
  const [form, setForm] = useState<FormState>(emptyForm)

  const q = useQuery({
    queryKey: ['admin-packages'],
    queryFn: async () => (await api.get('/admin/packages')).data as { packages: Pkg[] },
    enabled: !!isAdmin,
  })

  const onError = (err: unknown) => {
    const ax = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
    const first = ax.response?.data?.errors ? Object.values(ax.response.data.errors)[0]?.[0] : undefined
    toast.error(first ?? ax.response?.data?.message ?? String(err))
  }

  const createM = useMutation({
    mutationFn: async (payload: Record<string, unknown>) => api.post('/admin/packages', payload),
    onSuccess: () => {
      toast.success(t('packages.created'))
      qc.invalidateQueries({ queryKey: ['admin-packages'] })
      closeForm()
    },
    onError,
  })

  const patchM = useMutation({
    mutationFn: async ({ id, body }: { id: number; body: Record<string, unknown> }) =>
      api.patch(`/admin/packages/${id}`, body),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin-packages'] })
      toast.success(t('packages.updated'))
      closeForm()
    },
    onError,
  })

  const deleteM = useMutation({
    mutationFn: async (id: number) => api.delete(`/admin/packages/${id}`),
    onSuccess: () => {
      toast.success(t('packages.deleted'))
      qc.invalidateQueries({ queryKey: ['admin-packages'] })
    },
    onError,
  })

  const packages = q.data?.packages ?? []

  function openCreate() {
    setEditing(null)
    setForm({ ...emptyForm, slug: `paket-${Date.now()}` })
    setShowForm(true)
  }

  function openEdit(p: Pkg) {
    setEditing(p)
    setForm(fromPkg(p))
    setShowForm(true)
  }

  function closeForm() {
    setShowForm(false)
    setEditing(null)
  }

  function submit(ev: React.FormEvent) {
    ev.preventDefault()
    const body: Record<string, unknown> = {
      name: form.name,
      description: form.description,
      disk_space_mb: Number(form.disk_space_mb),
      bandwidth_mb: Number(form.bandwidth_mb),
      max_domains: Number(form.max_domains),
      max_subdomains: Number(form.max_subdomains),
      max_databases: Number(form.max_databases),
      max_email_accounts: Number(form.max_email_accounts),
      max_ftp_accounts: Number(form.max_ftp_accounts),
      max_cron_jobs: Number(form.max_cron_jobs),
      cpu_limit: Number(form.cpu_limit),
      memory_limit_mb: Number(form.memory_limit_mb),
      inode_limit: Number(form.inode_limit),
      ssl_enabled: form.ssl_enabled,
      backup_enabled: form.backup_enabled,
      price_monthly: Number(form.price_monthly),
      price_yearly: Number(form.price_yearly),
      currency: form.currency,
      is_active: form.is_active,
    }
    if (editing) {
      patchM.mutate({ id: editing.id, body })
    } else {
      createM.mutate({ ...body, slug: form.slug })
    }
  }

  const set = <K extends keyof FormState>(k: K, v: FormState[K]) => setForm((f) => ({ ...f, [k]: v }))

  if (!isAdmin) {
    return <Navigate to="/dashboard" replace />
  }

  const num = (label: string, k: keyof FormState, hint?: string) => (
    <label className="block">
      <span className="text-xs font-medium text-gray-600 dark:text-gray-300">{label}</span>
      <input
        type="number"
        className="input w-full mt-1"
        value={form[k] as number}
        onChange={(e) => set(k, Number(e.target.value) as never)}
      />
      {hint && <span className="text-[11px] text-gray-400">{hint}</span>}
    </label>
  )

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <Package className="h-8 w-8 text-primary-500" />
          <div>
            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{t('nav.packages')}</h1>
            <p className="text-gray-500 dark:text-gray-400 text-sm">Hosting paketleri (kaynak limitleri)</p>
          </div>
        </div>
        <button type="button" className="btn-primary flex items-center gap-2" onClick={openCreate}>
          <Plus className="h-4 w-4" />
          {t('common.create')}
        </button>
      </div>

      {showForm && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="card max-w-2xl w-full p-6 space-y-4 bg-white dark:bg-gray-900 max-h-[92vh] overflow-y-auto">
            <h2 className="text-lg font-semibold">{editing ? `Paketi düzenle: ${editing.name}` : 'Yeni paket'}</h2>
            <p className="text-xs text-gray-400">Limit alanlarında <strong>-1 = Limitsiz</strong>.</p>
            <form className="space-y-5" onSubmit={submit}>
              <div className="space-y-3">
                <h3 className="text-xs font-semibold uppercase text-gray-400">Genel</h3>
                <label className="block">
                  <span className="text-xs font-medium text-gray-600 dark:text-gray-300">Paket Adı</span>
                  <input className="input w-full mt-1" value={form.name} onChange={(e) => set('name', e.target.value)} required />
                </label>
                {!editing && (
                  <label className="block">
                    <span className="text-xs font-medium text-gray-600 dark:text-gray-300">Slug (benzersiz)</span>
                    <input className="input w-full mt-1 font-mono text-sm" value={form.slug} onChange={(e) => set('slug', e.target.value)} />
                  </label>
                )}
                <label className="block">
                  <span className="text-xs font-medium text-gray-600 dark:text-gray-300">Açıklama</span>
                  <textarea className="input w-full mt-1 min-h-[56px]" value={form.description} onChange={(e) => set('description', e.target.value)} />
                </label>
              </div>

              <div className="space-y-3">
                <h3 className="text-xs font-semibold uppercase text-gray-400">Kaynaklar</h3>
                <div className="grid grid-cols-2 gap-3">
                  {num('Disk Alanı (MB)', 'disk_space_mb', '-1 = limitsiz')}
                  {num('Trafik (MB)', 'bandwidth_mb', '-1 = limitsiz')}
                  {num('İşlemci (CPU %)', 'cpu_limit', 'Bilgi amaçlı — gerçek sınır için sunucu altyapısı gerekir')}
                  {num('RAM (MB)', 'memory_limit_mb', 'Bilgi amaçlı — gerçek sınır için sunucu altyapısı gerekir')}
                  {num('inode Limiti (dosya sayısı)', 'inode_limit', '-1 = limitsiz. Günlük tarama ile uygulanır; aşımda grace sonrası askıya alınır')}
                </div>
              </div>

              <div className="space-y-3">
                <h3 className="text-xs font-semibold uppercase text-gray-400">Hesap Limitleri</h3>
                <div className="grid grid-cols-2 gap-3">
                  {num('Maks. Alan Adı (Domain)', 'max_domains')}
                  {num('Maks. Subdomain', 'max_subdomains')}
                  {num('Maks. Veritabanı (MySQL)', 'max_databases')}
                  {num('Maks. E-posta Hesabı', 'max_email_accounts')}
                  {num('Maks. FTP Hesabı', 'max_ftp_accounts')}
                  {num('Maks. Cron Job', 'max_cron_jobs')}
                </div>
              </div>

              <div className="space-y-3">
                <h3 className="text-xs font-semibold uppercase text-gray-400">Özellikler & Fiyat</h3>
                <div className="flex flex-wrap gap-4">
                  <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={form.ssl_enabled} onChange={(e) => set('ssl_enabled', e.target.checked)} />
                    SSL (Let's Encrypt)
                  </label>
                  <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={form.backup_enabled} onChange={(e) => set('backup_enabled', e.target.checked)} />
                    Yedekleme
                  </label>
                  <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={form.is_active} onChange={(e) => set('is_active', e.target.checked)} />
                    Aktif
                  </label>
                </div>
                <div className="grid grid-cols-3 gap-3">
                  {num('Aylık Fiyat', 'price_monthly')}
                  {num('Yıllık Fiyat', 'price_yearly')}
                  <label className="block">
                    <span className="text-xs font-medium text-gray-600 dark:text-gray-300">Para Birimi</span>
                    <input className="input w-full mt-1" value={form.currency} maxLength={3} onChange={(e) => set('currency', e.target.value.toUpperCase())} />
                  </label>
                </div>
              </div>

              <div className="flex justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                <button type="button" className="btn-secondary" onClick={closeForm}>
                  {t('common.cancel')}
                </button>
                <button type="submit" className="btn-primary" disabled={createM.isPending || patchM.isPending}>
                  {editing ? t('common.save') : t('common.create')}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      <div className="card overflow-x-auto">
        <table className="w-full text-sm">
          <thead className="bg-gray-50 dark:bg-gray-800/80">
            <tr>
              <th className="text-left px-4 py-2">Ad</th>
              <th className="text-left px-4 py-2">Disk</th>
              <th className="text-left px-4 py-2">RAM</th>
              <th className="text-left px-4 py-2">inode</th>
              <th className="text-left px-4 py-2">Domain</th>
              <th className="text-left px-4 py-2">DB</th>
              <th className="text-left px-4 py-2">E-posta</th>
              <th className="text-left px-4 py-2">Fiyat (ay/yıl)</th>
              <th className="text-left px-4 py-2">Aktif</th>
              <th className="text-right px-4 py-2">{t('common.actions')}</th>
            </tr>
          </thead>
          <tbody>
            {packages.map((p) => (
              <tr key={p.id} className="border-t border-gray-100 dark:border-gray-800">
                <td className="px-4 py-2 font-medium">{p.name}</td>
                <td className="px-4 py-2">{fmtLimit(p.disk_space_mb)} MB</td>
                <td className="px-4 py-2">{p.memory_limit_mb ? `${p.memory_limit_mb} MB` : '—'}</td>
                <td className="px-4 py-2">{p.inode_limit == null ? '—' : fmtLimit(p.inode_limit)}</td>
                <td className="px-4 py-2">{fmtLimit(p.max_domains)}</td>
                <td className="px-4 py-2">{fmtLimit(p.max_databases)}</td>
                <td className="px-4 py-2">{fmtLimit(p.max_email_accounts)}</td>
                <td className="px-4 py-2">
                  {p.price_monthly} / {p.price_yearly} {p.currency}
                </td>
                <td className="px-4 py-2">
                  <button
                    type="button"
                    className={`text-xs px-2 py-1 rounded ${p.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-200'}`}
                    onClick={() => patchM.mutate({ id: p.id, body: { is_active: !p.is_active } })}
                  >
                    {p.is_active ? 'on' : 'off'}
                  </button>
                </td>
                <td className="px-4 py-2 text-right">
                  <div className="flex justify-end gap-1">
                    <button
                      type="button"
                      className="p-1.5 rounded-lg hover:bg-primary-50 text-gray-500"
                      onClick={() => openEdit(p)}
                      title="Düzenle"
                    >
                      <Pencil className="h-4 w-4" />
                    </button>
                    <button
                      type="button"
                      className="p-1.5 rounded-lg hover:bg-red-50 text-gray-500"
                      onClick={() => {
                        if (window.confirm(t('common.confirm_delete'))) deleteM.mutate(p.id)
                      }}
                      title="Sil"
                    >
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        {q.isLoading && <p className="p-6 text-center text-gray-500">{t('common.loading')}</p>}
        {!q.isLoading && packages.length === 0 && (
          <p className="p-6 text-center text-gray-500">{t('common.no_data')}</p>
        )}
      </div>
    </div>
  )
}
