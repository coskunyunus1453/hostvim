import { useEffect, useMemo, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '../services/api'
import { useAuthStore } from '../store/authStore'
import { tokenHasAbility } from '../lib/abilities'
import { Receipt, ShoppingCart, RefreshCw, CheckCircle2, Clock, AlertTriangle } from 'lucide-react'
import toast from 'react-hot-toast'
import { safeExternalHttpUrl } from '../lib/urlSafety'

type InvoiceItem = { id: number; description: string; quantity: number; unit_price: string; amount: string }
type Invoice = {
  id: number
  number: string
  status: string
  subtotal: string
  tax_rate: string
  tax_amount: string
  total: string
  currency: string
  due_at: string | null
  paid_at: string | null
  items?: InvoiceItem[]
}
type PackageRow = {
  id: number
  name: string
  description: string | null
  price_monthly: string | number
  price_yearly: string | number
  currency: string
  is_active: boolean
}
type Service = {
  id: number
  service_status?: string
  status: string
  billing_cycle: string
  amount: string
  currency: string
  next_due_at?: string | null
  hosting_package?: { name: string }
  domain?: { name: string }
}

const statusMeta: Record<string, { label: string; cls: string; icon: typeof Clock }> = {
  unpaid: { label: 'Ödenmedi', cls: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200', icon: Clock },
  overdue: { label: 'Gecikti', cls: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200', icon: AlertTriangle },
  paid: { label: 'Ödendi', cls: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200', icon: CheckCircle2 },
  cancelled: { label: 'İptal', cls: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300', icon: Clock },
  refunded: { label: 'İade', cls: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200', icon: Clock },
  draft: { label: 'Taslak', cls: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300', icon: Clock },
}

const serviceStatusMeta: Record<string, { label: string; cls: string }> = {
  active: { label: 'Aktif', cls: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' },
  suspended: { label: 'Askıda', cls: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200' },
  pending: { label: 'Bekliyor', cls: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' },
  terminated: { label: 'Sonlandı', cls: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' },
}

function fmtDate(iso?: string | null): string {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleDateString('tr-TR')
  } catch {
    return iso
  }
}

function money(amount: string | number, currency: string): string {
  const n = typeof amount === 'string' ? parseFloat(amount) : amount
  return `${n.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${currency}`
}

export default function InvoicesPage() {
  const qc = useQueryClient()
  const [searchParams] = useSearchParams()
  const abilities = useAuthStore((s) => s.user?.abilities)
  const canWrite = tokenHasAbility(abilities, 'billing:write')
  const [buyPkg, setBuyPkg] = useState<PackageRow | null>(null)
  const [buyCycle, setBuyCycle] = useState<'monthly' | 'yearly'>('monthly')
  const [buyDomain, setBuyDomain] = useState('')

  useEffect(() => {
    if (searchParams.get('paid') === '1') toast.success('Ödemeniz alındı, hizmetiniz hazırlanıyor.')
  }, [searchParams])

  const invoicesQ = useQuery({
    queryKey: ['my-invoices'],
    queryFn: async () => (await api.get('/billing/invoices')).data as { data: Invoice[] },
  })
  const servicesQ = useQuery({
    queryKey: ['my-services'],
    queryFn: async () => (await api.get('/billing/subscriptions')).data as { data: Service[] },
  })
  const pkgsQ = useQuery({
    queryKey: ['store-packages'],
    queryFn: async () => (await api.get('/billing/packages')).data as { packages: PackageRow[] },
  })

  const payM = useMutation({
    mutationFn: async (invoiceId: number) => (await api.post(`/billing/invoices/${invoiceId}/pay`)).data,
    onSuccess: (data: { method?: string; url?: string; instructions?: string; reference?: string }) => {
      if (data.method === 'stripe' && data.url) {
        const url = safeExternalHttpUrl(data.url)
        if (url) {
          window.location.href = url
          return
        }
      }
      if (data.method === 'manual') {
        toast(data.instructions || `Lütfen havale açıklamasına "${data.reference}" yazınız.`, { duration: 8000, icon: '🏦' })
        return
      }
      toast.success('Ödeme başlatıldı.')
    },
    onError: (e: unknown) => toast.error((e as { response?: { data?: { message?: string } } })?.response?.data?.message || 'Ödeme başlatılamadı.'),
  })

  const orderM = useMutation({
    mutationFn: async (payload: { package_id: number; billing_cycle: 'monthly' | 'yearly'; domain?: string }) =>
      (await api.post('/billing/orders', { items: [payload] })).data,
    onSuccess: () => {
      toast.success('Siparişiniz oluşturuldu, faturanız hazır.')
      setBuyPkg(null)
      setBuyDomain('')
      void qc.invalidateQueries({ queryKey: ['my-invoices'] })
    },
    onError: (e: unknown) => toast.error((e as { response?: { data?: { message?: string } } })?.response?.data?.message || 'Sipariş oluşturulamadı.'),
  })

  const invoices = invoicesQ.data?.data ?? []
  const services = servicesQ.data?.data ?? []
  const packages = pkgsQ.data?.packages ?? []
  const outstanding = useMemo(
    () => invoices.filter((i) => i.status === 'unpaid' || i.status === 'overdue').reduce((s, i) => s + parseFloat(i.total), 0),
    [invoices],
  )
  const currency = invoices[0]?.currency || packages[0]?.currency || 'TRY'

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Receipt className="h-8 w-8 text-emerald-500" />
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Faturalar & Hizmetler</h1>
          <p className="text-gray-500 dark:text-gray-400 text-sm">Faturalarınızı ödeyin, hosting hizmetlerinizi yönetin.</p>
        </div>
        <button className="btn-secondary ml-auto text-sm" onClick={() => { void invoicesQ.refetch(); void servicesQ.refetch() }}>
          <RefreshCw className="h-4 w-4" />
        </button>
      </div>

      {outstanding > 0 && (
        <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-200">
          Ödenmemiş toplam tutarınız: <strong>{money(outstanding, currency)}</strong>
        </div>
      )}

      {/* Faturalar */}
      <div className="card overflow-hidden">
        <h2 className="text-lg font-semibold text-gray-900 dark:text-white px-4 py-3 border-b border-gray-100 dark:border-gray-800">
          Faturalarım
        </h2>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 dark:bg-gray-800/80">
              <tr>
                <th className="text-left px-4 py-2">Fatura No</th>
                <th className="text-left px-4 py-2">Tutar</th>
                <th className="text-left px-4 py-2">Son Ödeme</th>
                <th className="text-left px-4 py-2">Durum</th>
                <th className="text-right px-4 py-2">İşlem</th>
              </tr>
            </thead>
            <tbody>
              {invoices.map((inv) => {
                const meta = statusMeta[inv.status] ?? statusMeta.unpaid
                const Icon = meta.icon
                const payable = inv.status === 'unpaid' || inv.status === 'overdue'
                return (
                  <tr key={inv.id} className="border-t border-gray-100 dark:border-gray-800">
                    <td className="px-4 py-2 font-medium text-gray-900 dark:text-white">{inv.number}</td>
                    <td className="px-4 py-2">{money(inv.total, inv.currency)}</td>
                    <td className="px-4 py-2">{fmtDate(inv.due_at)}</td>
                    <td className="px-4 py-2">
                      <span className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ${meta.cls}`}>
                        <Icon className="h-3 w-3" />
                        {meta.label}
                      </span>
                    </td>
                    <td className="px-4 py-2 text-right">
                      {payable && canWrite && (
                        <button className="btn-primary text-xs" disabled={payM.isPending} onClick={() => payM.mutate(inv.id)}>
                          Öde
                        </button>
                      )}
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
          {invoicesQ.isLoading && <p className="p-4 text-gray-500">Yükleniyor…</p>}
          {!invoicesQ.isLoading && invoices.length === 0 && <p className="p-6 text-center text-gray-500">Henüz faturanız yok.</p>}
        </div>
      </div>

      {/* Hizmetler */}
      <div className="card overflow-hidden">
        <h2 className="text-lg font-semibold text-gray-900 dark:text-white px-4 py-3 border-b border-gray-100 dark:border-gray-800">
          Hizmetlerim
        </h2>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 dark:bg-gray-800/80">
              <tr>
                <th className="text-left px-4 py-2">Paket</th>
                <th className="text-left px-4 py-2">Alan Adı</th>
                <th className="text-left px-4 py-2">Dönem</th>
                <th className="text-left px-4 py-2">Tutar</th>
                <th className="text-left px-4 py-2">Sonraki Ödeme</th>
                <th className="text-left px-4 py-2">Durum</th>
              </tr>
            </thead>
            <tbody>
              {services.map((s) => {
                const meta = serviceStatusMeta[s.service_status || ''] ?? { label: s.status, cls: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }
                return (
                  <tr key={s.id} className="border-t border-gray-100 dark:border-gray-800">
                    <td className="px-4 py-2">{s.hosting_package?.name ?? '—'}</td>
                    <td className="px-4 py-2">{s.domain?.name ?? '—'}</td>
                    <td className="px-4 py-2">{s.billing_cycle === 'yearly' ? 'Yıllık' : 'Aylık'}</td>
                    <td className="px-4 py-2">{money(s.amount, s.currency)}</td>
                    <td className="px-4 py-2">{fmtDate(s.next_due_at)}</td>
                    <td className="px-4 py-2">
                      <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${meta.cls}`}>{meta.label}</span>
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
          {servicesQ.isLoading && <p className="p-4 text-gray-500">Yükleniyor…</p>}
          {!servicesQ.isLoading && services.length === 0 && <p className="p-6 text-center text-gray-500">Aktif hizmetiniz yok.</p>}
        </div>
      </div>

      {/* Mağaza */}
      <div>
        <div className="flex items-center gap-2 mb-3">
          <ShoppingCart className="h-5 w-5 text-primary-600 dark:text-primary-400" />
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Hosting Satın Al</h2>
        </div>
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {packages.map((p) => (
            <div key={p.id} className="card p-5 flex flex-col gap-3">
              <div>
                <h3 className="font-semibold text-gray-900 dark:text-white">{p.name}</h3>
                {p.description && <p className="text-sm text-gray-500 mt-1 line-clamp-3">{p.description}</p>}
              </div>
              <div className="text-sm text-gray-600 dark:text-gray-400">
                <p>Aylık: {money(p.price_monthly, p.currency)}</p>
                <p>Yıllık: {money(p.price_yearly, p.currency)}</p>
              </div>
              <button
                className="btn-primary text-sm mt-auto"
                disabled={!canWrite || !p.is_active}
                onClick={() => { setBuyPkg(p); setBuyCycle('monthly'); setBuyDomain('') }}
              >
                Satın Al
              </button>
            </div>
          ))}
        </div>
        {pkgsQ.isLoading && <p className="text-gray-500 py-4">Yükleniyor…</p>}
      </div>

      {/* Satın alma modalı */}
      {buyPkg && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" onClick={() => setBuyPkg(null)}>
          <div className="card w-full max-w-md p-5 space-y-4" onClick={(e) => e.stopPropagation()}>
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">{buyPkg.name} — Satın Al</h3>
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Faturalama dönemi</label>
              <div className="flex gap-2">
                <button className={`flex-1 rounded-lg border px-3 py-2 text-sm ${buyCycle === 'monthly' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' : 'border-gray-200 dark:border-gray-700'}`} onClick={() => setBuyCycle('monthly')}>
                  Aylık · {money(buyPkg.price_monthly, buyPkg.currency)}
                </button>
                <button className={`flex-1 rounded-lg border px-3 py-2 text-sm ${buyCycle === 'yearly' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' : 'border-gray-200 dark:border-gray-700'}`} onClick={() => setBuyCycle('yearly')}>
                  Yıllık · {money(buyPkg.price_yearly, buyPkg.currency)}
                </button>
              </div>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Alan adı (opsiyonel)</label>
              <input
                value={buyDomain}
                onChange={(e) => setBuyDomain(e.target.value)}
                placeholder="ornek.com"
                className="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
              />
              <p className="text-xs text-gray-500 mt-1">Ödeme onaylanınca site otomatik kurulur.</p>
            </div>
            <div className="flex justify-end gap-2 pt-2">
              <button className="btn-secondary text-sm" onClick={() => setBuyPkg(null)}>Vazgeç</button>
              <button
                className="btn-primary text-sm"
                disabled={orderM.isPending}
                onClick={() => orderM.mutate({ package_id: buyPkg.id, billing_cycle: buyCycle, domain: buyDomain.trim() || undefined })}
              >
                Siparişi Oluştur
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
