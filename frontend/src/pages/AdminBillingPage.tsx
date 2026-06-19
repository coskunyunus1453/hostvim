import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '../services/api'
import { Wallet, RefreshCw, Save } from 'lucide-react'
import toast from 'react-hot-toast'

type Stats = {
  invoices: { unpaid: number; overdue: number; paid_this_month: number }
  revenue_this_month: number
  outstanding: number
  orders_pending: number
  services: { active: number; suspended: number }
}
type Invoice = { id: number; number: string; status: string; total: string; currency: string; due_at: string | null; user?: { name: string; email: string } }
type Service = {
  id: number
  service_status: string
  billing_cycle: string
  amount: string
  currency: string
  next_due_at: string | null
  user?: { name: string; email: string }
  hosting_package?: { name: string }
  domain?: { name: string }
}

type Tab = 'overview' | 'invoices' | 'services' | 'settings'

function money(a: string | number, c = 'TRY'): string {
  const n = typeof a === 'string' ? parseFloat(a) : a
  return `${(n || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${c}`
}
function fmt(iso?: string | null): string {
  if (!iso) return '—'
  try { return new Date(iso).toLocaleDateString('tr-TR') } catch { return iso }
}

const invStatus: Record<string, string> = { unpaid: 'Ödenmedi', overdue: 'Gecikti', paid: 'Ödendi', cancelled: 'İptal', refunded: 'İade', draft: 'Taslak' }
const svcStatus: Record<string, string> = { active: 'Aktif', suspended: 'Askıda', pending: 'Bekliyor', terminated: 'Sonlandı' }

export default function AdminBillingPage() {
  const qc = useQueryClient()
  const [tab, setTab] = useState<Tab>('overview')

  const statsQ = useQuery({ queryKey: ['admin-billing-stats'], queryFn: async () => (await api.get('/admin/billing/stats')).data as Stats })

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Wallet className="h-8 w-8 text-emerald-500" />
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Faturalama Yönetimi</h1>
          <p className="text-gray-500 dark:text-gray-400 text-sm">Faturalar, hizmetler ve otomasyon ayarları.</p>
        </div>
      </div>

      <div className="flex gap-1 border-b border-gray-200 dark:border-gray-800">
        {([['overview', 'Genel'], ['invoices', 'Faturalar'], ['services', 'Hizmetler'], ['settings', 'Ayarlar']] as [Tab, string][]).map(([k, label]) => (
          <button
            key={k}
            onClick={() => setTab(k)}
            className={`px-4 py-2 text-sm font-medium border-b-2 -mb-px ${tab === k ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-800 dark:hover:text-gray-200'}`}
          >
            {label}
          </button>
        ))}
      </div>

      {tab === 'overview' && (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {statsQ.data && (
            <>
              <StatCard label="Bu ay gelir" value={money(statsQ.data.revenue_this_month)} />
              <StatCard label="Açık bakiye" value={money(statsQ.data.outstanding)} tone="amber" />
              <StatCard label="Ödenmemiş fatura" value={String(statsQ.data.invoices.unpaid)} />
              <StatCard label="Gecikmiş fatura" value={String(statsQ.data.invoices.overdue)} tone="red" />
              <StatCard label="Bu ay ödenen" value={String(statsQ.data.invoices.paid_this_month)} tone="green" />
              <StatCard label="Bekleyen sipariş" value={String(statsQ.data.orders_pending)} />
              <StatCard label="Aktif hizmet" value={String(statsQ.data.services.active)} tone="green" />
              <StatCard label="Askıdaki hizmet" value={String(statsQ.data.services.suspended)} tone="red" />
            </>
          )}
          {statsQ.isLoading && <p className="text-gray-500">Yükleniyor…</p>}
        </div>
      )}

      {tab === 'invoices' && <InvoicesTab onChange={() => qc.invalidateQueries({ queryKey: ['admin-billing-stats'] })} />}
      {tab === 'services' && <ServicesTab onChange={() => qc.invalidateQueries({ queryKey: ['admin-billing-stats'] })} />}
      {tab === 'settings' && <SettingsTab />}
    </div>
  )
}

function StatCard({ label, value, tone }: { label: string; value: string; tone?: 'amber' | 'red' | 'green' }) {
  const toneCls = tone === 'amber' ? 'text-amber-600 dark:text-amber-400' : tone === 'red' ? 'text-red-600 dark:text-red-400' : tone === 'green' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-900 dark:text-white'
  return (
    <div className="card p-4">
      <p className="text-xs text-gray-500">{label}</p>
      <p className={`text-2xl font-bold ${toneCls}`}>{value}</p>
    </div>
  )
}

function InvoicesTab({ onChange }: { onChange: () => void }) {
  const qc = useQueryClient()
  const [status, setStatus] = useState('')
  const invoicesQ = useQuery({
    queryKey: ['admin-invoices', status],
    queryFn: async () => (await api.get('/admin/billing/invoices', { params: status ? { status } : {} })).data as { data: Invoice[] },
  })
  const markPaidM = useMutation({
    mutationFn: async (id: number) => api.post(`/admin/billing/invoices/${id}/mark-paid`, { method: 'manual' }),
    onSuccess: () => { toast.success('Fatura ödendi olarak işaretlendi, hizmet otomatik açıldı.'); void qc.invalidateQueries({ queryKey: ['admin-invoices'] }); onChange() },
    onError: () => toast.error('İşlem başarısız.'),
  })
  const cancelM = useMutation({
    mutationFn: async (id: number) => api.post(`/admin/billing/invoices/${id}/cancel`),
    onSuccess: () => { toast.success('Fatura iptal edildi.'); void qc.invalidateQueries({ queryKey: ['admin-invoices'] }); onChange() },
  })
  const invoices = invoicesQ.data?.data ?? []
  return (
    <div className="space-y-3">
      <div className="flex items-center gap-2">
        <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
          <option value="">Tüm durumlar</option>
          <option value="unpaid">Ödenmemiş</option>
          <option value="overdue">Gecikmiş</option>
          <option value="paid">Ödenmiş</option>
          <option value="cancelled">İptal</option>
        </select>
        <button className="btn-secondary text-sm" onClick={() => void invoicesQ.refetch()}><RefreshCw className="h-4 w-4" /></button>
      </div>
      <div className="card overflow-x-auto">
        <table className="w-full text-sm">
          <thead className="bg-gray-50 dark:bg-gray-800/80">
            <tr>
              <th className="text-left px-4 py-2">No</th>
              <th className="text-left px-4 py-2">Müşteri</th>
              <th className="text-left px-4 py-2">Tutar</th>
              <th className="text-left px-4 py-2">Vade</th>
              <th className="text-left px-4 py-2">Durum</th>
              <th className="text-right px-4 py-2">İşlem</th>
            </tr>
          </thead>
          <tbody>
            {invoices.map((inv) => {
              const payable = inv.status === 'unpaid' || inv.status === 'overdue'
              return (
                <tr key={inv.id} className="border-t border-gray-100 dark:border-gray-800">
                  <td className="px-4 py-2 font-medium text-gray-900 dark:text-white">{inv.number}</td>
                  <td className="px-4 py-2">{inv.user?.name ?? '—'}<br /><span className="text-xs text-gray-500">{inv.user?.email}</span></td>
                  <td className="px-4 py-2">{money(inv.total, inv.currency)}</td>
                  <td className="px-4 py-2">{fmt(inv.due_at)}</td>
                  <td className="px-4 py-2">{invStatus[inv.status] ?? inv.status}</td>
                  <td className="px-4 py-2 text-right space-x-2">
                    {payable && (
                      <>
                        <button className="btn-primary text-xs" disabled={markPaidM.isPending} onClick={() => markPaidM.mutate(inv.id)}>Ödendi</button>
                        <button className="btn-secondary text-xs" disabled={cancelM.isPending} onClick={() => cancelM.mutate(inv.id)}>İptal</button>
                      </>
                    )}
                  </td>
                </tr>
              )
            })}
          </tbody>
        </table>
        {invoicesQ.isLoading && <p className="p-4 text-gray-500">Yükleniyor…</p>}
        {!invoicesQ.isLoading && invoices.length === 0 && <p className="p-6 text-center text-gray-500">Kayıt yok.</p>}
      </div>
    </div>
  )
}

function ServicesTab({ onChange }: { onChange: () => void }) {
  const qc = useQueryClient()
  const servicesQ = useQuery({ queryKey: ['admin-services'], queryFn: async () => (await api.get('/admin/billing/services')).data as { data: Service[] } })
  const act = useMutation({
    mutationFn: async ({ id, action }: { id: number; action: string }) => api.post(`/admin/billing/services/${id}/${action}`),
    onSuccess: () => { toast.success('Hizmet güncellendi.'); void qc.invalidateQueries({ queryKey: ['admin-services'] }); onChange() },
    onError: () => toast.error('İşlem başarısız.'),
  })
  const services = servicesQ.data?.data ?? []
  return (
    <div className="card overflow-x-auto">
      <table className="w-full text-sm">
        <thead className="bg-gray-50 dark:bg-gray-800/80">
          <tr>
            <th className="text-left px-4 py-2">Müşteri</th>
            <th className="text-left px-4 py-2">Paket</th>
            <th className="text-left px-4 py-2">Alan Adı</th>
            <th className="text-left px-4 py-2">Sonraki Ödeme</th>
            <th className="text-left px-4 py-2">Durum</th>
            <th className="text-right px-4 py-2">İşlem</th>
          </tr>
        </thead>
        <tbody>
          {services.map((s) => (
            <tr key={s.id} className="border-t border-gray-100 dark:border-gray-800">
              <td className="px-4 py-2">{s.user?.name ?? '—'}<br /><span className="text-xs text-gray-500">{s.user?.email}</span></td>
              <td className="px-4 py-2">{s.hosting_package?.name ?? '—'}</td>
              <td className="px-4 py-2">{s.domain?.name ?? '—'}</td>
              <td className="px-4 py-2">{fmt(s.next_due_at)}</td>
              <td className="px-4 py-2">{svcStatus[s.service_status] ?? s.service_status}</td>
              <td className="px-4 py-2 text-right space-x-2">
                {s.service_status === 'active' && <button className="btn-secondary text-xs" onClick={() => act.mutate({ id: s.id, action: 'suspend' })}>Askıya Al</button>}
                {s.service_status === 'suspended' && <button className="btn-primary text-xs" onClick={() => act.mutate({ id: s.id, action: 'unsuspend' })}>Aç</button>}
                {s.service_status !== 'terminated' && <button className="text-xs text-red-600 hover:underline" onClick={() => { if (confirm('Hizmet sonlandırılsın mı?')) act.mutate({ id: s.id, action: 'terminate' }) }}>Sonlandır</button>}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
      {servicesQ.isLoading && <p className="p-4 text-gray-500">Yükleniyor…</p>}
      {!servicesQ.isLoading && services.length === 0 && <p className="p-6 text-center text-gray-500">Kayıt yok.</p>}
    </div>
  )
}

type Settings = Record<string, unknown>

function SettingsTab() {
  const qc = useQueryClient()
  const settingsQ = useQuery({ queryKey: ['billing-settings'], queryFn: async () => (await api.get('/admin/settings/billing')).data as { settings: Settings } })
  const [form, setForm] = useState<Settings | null>(null)
  const data = form ?? settingsQ.data?.settings ?? null

  const saveM = useMutation({
    mutationFn: async (payload: Settings) => (await api.put('/admin/settings/billing', payload)).data,
    onSuccess: () => { toast.success('Ayarlar kaydedildi.'); void qc.invalidateQueries({ queryKey: ['billing-settings'] }); setForm(null) },
    onError: () => toast.error('Kaydedilemedi.'),
  })

  if (!data) return <p className="text-gray-500">Yükleniyor…</p>
  const set = (k: string, v: unknown) => setForm({ ...(form ?? settingsQ.data!.settings), [k]: v })
  const num = (k: string) => Number(data[k] ?? 0)
  const str = (k: string) => String(data[k] ?? '')
  const bool = (k: string) => Boolean(data[k])

  return (
    <div className="card p-5 space-y-5 max-w-3xl">
      <div className="grid gap-4 sm:grid-cols-2">
        <Field label="Para birimi"><input value={str('currency')} onChange={(e) => set('currency', e.target.value.toUpperCase())} className={inputCls} maxLength={3} /></Field>
        <Field label="KDV oranı (%)"><input type="number" value={num('tax_rate')} onChange={(e) => set('tax_rate', Number(e.target.value))} className={inputCls} /></Field>
        <Field label="Fatura vadesi (gün)"><input type="number" value={num('due_days')} onChange={(e) => set('due_days', Number(e.target.value))} className={inputCls} /></Field>
        <Field label="Yenileme faturası (gün önce)"><input type="number" value={num('renew_generate_days_before')} onChange={(e) => set('renew_generate_days_before', Number(e.target.value))} className={inputCls} /></Field>
        <Field label="Askıya alma (vade+gün)"><input type="number" value={num('suspend_after_days')} onChange={(e) => set('suspend_after_days', Number(e.target.value))} className={inputCls} /></Field>
        <Field label="Sonlandırma (askı+gün)"><input type="number" value={num('terminate_after_days')} onChange={(e) => set('terminate_after_days', Number(e.target.value))} className={inputCls} /></Field>
      </div>

      <div className="grid gap-3 sm:grid-cols-2">
        <Toggle label="Faturalama otomasyonu aktif" checked={bool('enabled')} onChange={(v) => set('enabled', v)} />
        <Toggle label="KDV fiyata dahil" checked={bool('tax_inclusive')} onChange={(v) => set('tax_inclusive', v)} />
        <Toggle label="Otomatik askıya alma" checked={bool('auto_suspend')} onChange={(v) => set('auto_suspend', v)} />
        <Toggle label="Otomatik sonlandırma" checked={bool('auto_terminate')} onChange={(v) => set('auto_terminate', v)} />
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <Field label="Firma adı"><input value={str('company_name')} onChange={(e) => set('company_name', e.target.value)} className={inputCls} /></Field>
        <Field label="Vergi no"><input value={str('company_tax_id')} onChange={(e) => set('company_tax_id', e.target.value)} className={inputCls} /></Field>
        <Field label="Destek e-postası"><input value={str('support_email')} onChange={(e) => set('support_email', e.target.value)} className={inputCls} /></Field>
        <Field label="Varsayılan PHP"><input value={str('default_php')} onChange={(e) => set('default_php', e.target.value)} className={inputCls} /></Field>
      </div>
      <Field label="Ödeme talimatları (havale vb.)">
        <textarea value={str('payment_instructions')} onChange={(e) => set('payment_instructions', e.target.value)} rows={3} className={inputCls} />
      </Field>

      <div className="flex justify-end">
        <button className="btn-primary text-sm" disabled={saveM.isPending || !form} onClick={() => saveM.mutate(form!)}>
          <Save className="h-4 w-4" /> Kaydet
        </button>
      </div>
    </div>
  )
}

const inputCls = 'w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm'

function Field({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <label className="block">
      <span className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{label}</span>
      {children}
    </label>
  )
}

function Toggle({ label, checked, onChange }: { label: string; checked: boolean; onChange: (v: boolean) => void }) {
  return (
    <label className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
      <input type="checkbox" checked={checked} onChange={(e) => onChange(e.target.checked)} className="h-4 w-4 rounded" />
      {label}
    </label>
  )
}
