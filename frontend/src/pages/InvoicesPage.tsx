import { useEffect, useMemo, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '../services/api'
import { useAuthStore } from '../store/authStore'
import { tokenHasAbility } from '../lib/abilities'
import { Receipt, ShoppingCart, RefreshCw, CheckCircle2, Clock, AlertTriangle, Globe, Search } from 'lucide-react'
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

function fmtDate(iso?: string | null, locale?: string): string {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleDateString(locale || 'tr-TR')
  } catch {
    return iso
  }
}

function money(amount: string | number, currency: string, locale?: string): string {
  const n = typeof amount === 'string' ? parseFloat(amount) : amount
  return `${n.toLocaleString(locale || 'tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${currency}`
}

export default function InvoicesPage() {
  const { t, i18n } = useTranslation()
  const qc = useQueryClient()
  const [searchParams] = useSearchParams()
  const abilities = useAuthStore((s) => s.user?.abilities)
  const canWrite = tokenHasAbility(abilities, 'billing:write')
  const [buyPkg, setBuyPkg] = useState<PackageRow | null>(null)
  const [buyCycle, setBuyCycle] = useState<'monthly' | 'yearly'>('monthly')
  const [buyDomain, setBuyDomain] = useState('')
  const [paytrIframe, setPaytrIframe] = useState<string | null>(null)
  const [domainQuery, setDomainQuery] = useState('')
  const [domainYears, setDomainYears] = useState(1)
  const [domainCheck, setDomainCheck] = useState<{ domain: string; available: boolean; register_price: number; currency: string } | null>(null)
  const locale = i18n.language?.startsWith('en') ? 'en-US' : 'tr-TR'

  const statusMeta = useMemo(
    () =>
      ({
        unpaid: { label: t('invoices.status_unpaid'), cls: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200', icon: Clock },
        overdue: { label: t('invoices.status_overdue'), cls: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200', icon: AlertTriangle },
        paid: { label: t('invoices.status_paid'), cls: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200', icon: CheckCircle2 },
        cancelled: { label: t('invoices.status_cancelled'), cls: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300', icon: Clock },
        refunded: { label: t('invoices.status_refunded'), cls: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200', icon: Clock },
        draft: { label: t('invoices.status_draft'), cls: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300', icon: Clock },
      }) as Record<string, { label: string; cls: string; icon: typeof Clock }>,
    [t],
  )

  const serviceStatusMeta = useMemo(
    () =>
      ({
        active: { label: t('invoices.svc_active'), cls: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' },
        suspended: { label: t('invoices.svc_suspended'), cls: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200' },
        pending: { label: t('invoices.svc_pending'), cls: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' },
        terminated: { label: t('invoices.svc_terminated'), cls: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' },
      }) as Record<string, { label: string; cls: string }>,
    [t],
  )

  useEffect(() => {
    if (searchParams.get('paid') === '1') toast.success(t('invoices.paid_success'))
  }, [searchParams, t])

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
    onSuccess: (data: { method?: string; url?: string; iframe_url?: string; instructions?: string; reference?: string }) => {
      if (data.method === 'paytr' && data.iframe_url) {
        const url = safeExternalHttpUrl(data.iframe_url)
        if (url) {
          setPaytrIframe(url)
          return
        }
      }
      if ((data.method === 'stripe' || data.method === 'iyzico') && data.url) {
        const url = safeExternalHttpUrl(data.url)
        if (url) {
          window.location.href = url
          return
        }
      }
      if (data.method === 'manual') {
        toast(
          data.instructions || t('invoices.manual_pay_hint', { reference: data.reference || '' }),
          { duration: 8000, icon: '🏦' },
        )
        return
      }
      toast.success(t('invoices.pay_started'))
    },
    onError: (e: unknown) =>
      toast.error((e as { response?: { data?: { message?: string } } })?.response?.data?.message || t('invoices.pay_failed')),
  })

  const checkDomainM = useMutation({
    mutationFn: async (domain: string) => (await api.post('/billing/domains/check', { domain })).data as {
      domain: string; available: boolean; register_price: number; currency: string; reason?: string
    },
    onSuccess: (d) => {
      setDomainCheck(d)
      if (!d.available) toast.error(t('invoices.domain_unavailable'))
    },
    onError: (e: unknown) => toast.error((e as { response?: { data?: { message?: string } } })?.response?.data?.message || t('invoices.domain_check_failed')),
  })

  const domainOrderM = useMutation({
    mutationFn: async () =>
      (await api.post('/billing/orders', { items: [{ item_type: 'domain_register', domain: domainCheck!.domain, domain_years: domainYears }] })).data,
    onSuccess: () => {
      toast.success(t('invoices.order_created'))
      setDomainQuery('')
      setDomainCheck(null)
      void qc.invalidateQueries({ queryKey: ['my-invoices'] })
    },
    onError: (e: unknown) => toast.error((e as { response?: { data?: { message?: string } } })?.response?.data?.message || t('invoices.order_failed')),
  })

  const orderM = useMutation({
    mutationFn: async (payload: { package_id: number; billing_cycle: 'monthly' | 'yearly'; domain?: string }) =>
      (await api.post('/billing/orders', { items: [payload] })).data,
    onSuccess: () => {
      toast.success(t('invoices.order_created'))
      setBuyPkg(null)
      setBuyDomain('')
      void qc.invalidateQueries({ queryKey: ['my-invoices'] })
    },
    onError: (e: unknown) =>
      toast.error((e as { response?: { data?: { message?: string } } })?.response?.data?.message || t('invoices.order_failed')),
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
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{t('invoices.title')}</h1>
          <p className="text-gray-500 dark:text-gray-400 text-sm">{t('invoices.subtitle')}</p>
        </div>
        <button className="btn-secondary ml-auto text-sm" onClick={() => { void invoicesQ.refetch(); void servicesQ.refetch() }}>
          <RefreshCw className="h-4 w-4" />
        </button>
      </div>

      {outstanding > 0 && (
        <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-200">
          {t('invoices.outstanding_banner', { amount: money(outstanding, currency, locale) })}
        </div>
      )}

      <div className="card overflow-hidden">
        <h2 className="text-lg font-semibold text-gray-900 dark:text-white px-4 py-3 border-b border-gray-100 dark:border-gray-800">
          {t('invoices.my_invoices')}
        </h2>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 dark:bg-gray-800/80">
              <tr>
                <th className="text-left px-4 py-2">{t('invoices.col_number')}</th>
                <th className="text-left px-4 py-2">{t('invoices.col_amount')}</th>
                <th className="text-left px-4 py-2">{t('invoices.col_due')}</th>
                <th className="text-left px-4 py-2">{t('invoices.col_status')}</th>
                <th className="text-right px-4 py-2">{t('invoices.col_action')}</th>
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
                    <td className="px-4 py-2">{money(inv.total, inv.currency, locale)}</td>
                    <td className="px-4 py-2">{fmtDate(inv.due_at, locale)}</td>
                    <td className="px-4 py-2">
                      <span className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ${meta.cls}`}>
                        <Icon className="h-3 w-3" />
                        {meta.label}
                      </span>
                    </td>
                    <td className="px-4 py-2 text-right">
                      {payable && canWrite && (
                        <button className="btn-primary text-xs" disabled={payM.isPending} onClick={() => payM.mutate(inv.id)}>
                          {t('invoices.pay')}
                        </button>
                      )}
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
          {invoicesQ.isLoading && <p className="p-4 text-gray-500">{t('invoices.loading')}</p>}
          {!invoicesQ.isLoading && invoices.length === 0 && <p className="p-6 text-center text-gray-500">{t('invoices.no_invoices')}</p>}
        </div>
      </div>

      <div className="card overflow-hidden">
        <h2 className="text-lg font-semibold text-gray-900 dark:text-white px-4 py-3 border-b border-gray-100 dark:border-gray-800">
          {t('invoices.my_services')}
        </h2>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 dark:bg-gray-800/80">
              <tr>
                <th className="text-left px-4 py-2">{t('invoices.col_package')}</th>
                <th className="text-left px-4 py-2">{t('invoices.col_domain')}</th>
                <th className="text-left px-4 py-2">{t('invoices.col_cycle')}</th>
                <th className="text-left px-4 py-2">{t('invoices.col_amount')}</th>
                <th className="text-left px-4 py-2">{t('invoices.col_next_due')}</th>
                <th className="text-left px-4 py-2">{t('invoices.col_status')}</th>
              </tr>
            </thead>
            <tbody>
              {services.map((s) => {
                const meta = serviceStatusMeta[s.service_status || ''] ?? { label: s.status, cls: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }
                return (
                  <tr key={s.id} className="border-t border-gray-100 dark:border-gray-800">
                    <td className="px-4 py-2">{s.hosting_package?.name ?? '—'}</td>
                    <td className="px-4 py-2">{s.domain?.name ?? '—'}</td>
                    <td className="px-4 py-2">{s.billing_cycle === 'yearly' ? t('invoices.yearly') : t('invoices.monthly')}</td>
                    <td className="px-4 py-2">{money(s.amount, s.currency, locale)}</td>
                    <td className="px-4 py-2">{fmtDate(s.next_due_at, locale)}</td>
                    <td className="px-4 py-2">
                      <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${meta.cls}`}>{meta.label}</span>
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
          {servicesQ.isLoading && <p className="p-4 text-gray-500">{t('invoices.loading')}</p>}
          {!servicesQ.isLoading && services.length === 0 && <p className="p-6 text-center text-gray-500">{t('invoices.no_services')}</p>}
        </div>
      </div>

      <div className="card p-5 space-y-4">
        <div className="flex items-center gap-2">
          <Globe className="h-5 w-5 text-sky-600 dark:text-sky-400" />
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white">{t('invoices.domain_register')}</h2>
        </div>
        <p className="text-sm text-gray-500">{t('invoices.domain_register_hint')}</p>
        <div className="flex flex-wrap gap-2">
          <input
            value={domainQuery}
            onChange={(e) => setDomainQuery(e.target.value)}
            placeholder={t('invoices.domain_placeholder')}
            className="flex-1 min-w-[200px] rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
          />
          <select value={domainYears} onChange={(e) => setDomainYears(Number(e.target.value))} className="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            {[1, 2, 3, 5].map((y) => <option key={y} value={y}>{y} {t('invoices.years')}</option>)}
          </select>
          <button className="btn-secondary text-sm" disabled={!canWrite || checkDomainM.isPending || !domainQuery.trim()} onClick={() => checkDomainM.mutate(domainQuery.trim())}>
            <Search className="h-4 w-4" /> {t('invoices.domain_check')}
          </button>
        </div>
        {domainCheck && (
          <div className={`rounded-lg border px-4 py-3 text-sm ${domainCheck.available ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900/40 dark:bg-emerald-950/20' : 'border-gray-200 dark:border-gray-700'}`}>
            <p className="font-medium text-gray-900 dark:text-white">{domainCheck.domain}</p>
            {domainCheck.available ? (
              <div className="mt-2 flex flex-wrap items-center justify-between gap-2">
                <span>{money(domainCheck.register_price * domainYears, domainCheck.currency, locale)}</span>
                <button className="btn-primary text-xs" disabled={domainOrderM.isPending} onClick={() => domainOrderM.mutate()}>
                  {t('invoices.domain_order')}
                </button>
              </div>
            ) : (
              <p className="text-gray-500 mt-1">{t('invoices.domain_unavailable')}</p>
            )}
          </div>
        )}
      </div>

      <div>
        <div className="flex items-center gap-2 mb-3">
          <ShoppingCart className="h-5 w-5 text-primary-600 dark:text-primary-400" />
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white">{t('invoices.buy_hosting')}</h2>
        </div>
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {packages.map((p) => (
            <div key={p.id} className="card p-5 flex flex-col gap-3">
              <div>
                <h3 className="font-semibold text-gray-900 dark:text-white">{p.name}</h3>
                {p.description && <p className="text-sm text-gray-500 mt-1 line-clamp-3">{p.description}</p>}
              </div>
              <div className="text-sm text-gray-600 dark:text-gray-400">
                <p>{t('invoices.monthly')}: {money(p.price_monthly, p.currency, locale)}</p>
                <p>{t('invoices.yearly')}: {money(p.price_yearly, p.currency, locale)}</p>
              </div>
              <button
                className="btn-primary text-sm mt-auto"
                disabled={!canWrite || !p.is_active}
                onClick={() => { setBuyPkg(p); setBuyCycle('monthly'); setBuyDomain('') }}
              >
                {t('invoices.buy')}
              </button>
            </div>
          ))}
        </div>
        {pkgsQ.isLoading && <p className="text-gray-500 py-4">{t('invoices.loading')}</p>}
      </div>

      {buyPkg && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" onClick={() => setBuyPkg(null)}>
          <div className="card w-full max-w-md p-5 space-y-4" onClick={(e) => e.stopPropagation()}>
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">{t('invoices.buy_modal_title', { name: buyPkg.name })}</h3>
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{t('invoices.billing_cycle')}</label>
              <div className="flex gap-2">
                <button className={`flex-1 rounded-lg border px-3 py-2 text-sm ${buyCycle === 'monthly' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' : 'border-gray-200 dark:border-gray-700'}`} onClick={() => setBuyCycle('monthly')}>
                  {t('invoices.monthly')} · {money(buyPkg.price_monthly, buyPkg.currency, locale)}
                </button>
                <button className={`flex-1 rounded-lg border px-3 py-2 text-sm ${buyCycle === 'yearly' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' : 'border-gray-200 dark:border-gray-700'}`} onClick={() => setBuyCycle('yearly')}>
                  {t('invoices.yearly')} · {money(buyPkg.price_yearly, buyPkg.currency, locale)}
                </button>
              </div>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{t('invoices.domain_optional')}</label>
              <input
                value={buyDomain}
                onChange={(e) => setBuyDomain(e.target.value)}
                placeholder={t('invoices.domain_placeholder')}
                className="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
              />
              <p className="text-xs text-gray-500 mt-1">{t('invoices.domain_hint')}</p>
            </div>
            <div className="flex justify-end gap-2 pt-2">
              <button className="btn-secondary text-sm" onClick={() => setBuyPkg(null)}>{t('invoices.cancel')}</button>
              <button
                className="btn-primary text-sm"
                disabled={orderM.isPending}
                onClick={() => orderM.mutate({ package_id: buyPkg.id, billing_cycle: buyCycle, domain: buyDomain.trim() || undefined })}
              >
                {t('invoices.place_order')}
              </button>
            </div>
          </div>
        </div>
      )}
      {paytrIframe && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" onClick={() => setPaytrIframe(null)}>
          <div className="card w-full max-w-lg overflow-hidden" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center justify-between px-4 py-2 border-b border-gray-200 dark:border-gray-700">
              <span className="text-sm font-medium">PayTR</span>
              <button className="text-sm text-gray-500 hover:text-gray-800" onClick={() => setPaytrIframe(null)}>{t('invoices.cancel')}</button>
            </div>
            <iframe src={paytrIframe} title="PayTR" className="w-full h-[520px] border-0" sandbox="allow-forms allow-scripts allow-same-origin allow-top-navigation" />
          </div>
        </div>
      )}
    </div>
  )
}
