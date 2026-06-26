import { useEffect, useRef } from 'react'
import { useSearchParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { useMutation, useQuery } from '@tanstack/react-query'
import api from '../services/api'
import { useAuthStore } from '../store/authStore'
import { tokenHasAbility } from '../lib/abilities'
import { CreditCard, ShieldCheck } from 'lucide-react'
import toast from 'react-hot-toast'
import { safeExternalHttpUrl } from '../lib/urlSafety'

type PackageRow = {
  id: number
  name: string
  description: string | null
  price_monthly: string | number
  price_yearly: string | number
  currency: string
  is_active: boolean
}

type SubRow = {
  id: number
  status: string
  billing_cycle: string
  amount: string | number
  currency: string
  hosting_package?: { name: string }
}

type LicenseSummary = {
  has_license_key?: boolean
  valid?: boolean
  tier?: string
  plan?: string | null
  plan_name?: string | null
  license_status?: string | null
  expires_at?: string | null
  subscription_status?: string | null
  renews_at?: string | null
  billing_provider?: string | null
  payment_method_label?: string | null
  downgraded_to_community?: boolean
  message?: string | null
  hub_reachable?: boolean
}

function formatLicenseDate(iso?: string | null): string {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleString()
  } catch {
    return iso
  }
}

export default function BillingPage() {
  const { t } = useTranslation()
  const [searchParams] = useSearchParams()
  const autoCheckoutStarted = useRef(false)
  const abilities = useAuthStore((s) => s.user?.abilities)
  const canWrite = tokenHasAbility(abilities, 'billing:write')

  const isAllowedCheckoutHost = (url: string): boolean => {
    try {
      const u = new URL(url)
      const host = u.hostname.toLowerCase()
      const appHost = window.location.hostname.toLowerCase()
      return host === appHost || host === 'checkout.stripe.com' || host.endsWith('.stripe.com')
    } catch {
      return false
    }
  }

  const pkgs = useQuery({
    queryKey: ['billing-packages'],
    queryFn: async () => (await api.get('/billing/packages')).data as { packages: PackageRow[] },
  })

  const subs = useQuery({
    queryKey: ['billing-subs'],
    queryFn: async () => (await api.get('/billing/subscriptions')).data,
  })

  const licenseQ = useQuery({
    queryKey: ['billing-license'],
    queryFn: async () =>
      (await api.get('/billing/license')).data as { license: LicenseSummary },
  })

  const checkoutM = useMutation({
    mutationFn: async (payload: { package_id: number; billing_cycle: 'monthly' | 'yearly' }) =>
      api.post('/billing/checkout', {
        ...payload,
        success_url: `${window.location.origin}/billing?checkout=success`,
        cancel_url: `${window.location.origin}/billing?checkout=cancel`,
      }),
    onSuccess: (res) => {
      const raw = (res.data as { url?: string })?.url
      const url = raw ? safeExternalHttpUrl(raw) : null
      if (url && isAllowedCheckoutHost(url)) {
        window.location.href = url
      } else if (raw) {
        toast.error(t('billing.checkout_blocked'))
      } else {
        toast.success(t('billing.demo_mode'))
      }
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string; demo?: boolean } } }
      const msg = ax.response?.data?.message
      if (ax.response?.data?.demo) toast(msg ?? t('billing.demo_mode'))
      else toast.error(msg ?? String(err))
    },
  })

  useEffect(() => {
    const checkout = searchParams.get('checkout')
    if (checkout === 'success') {
      toast.success(t('billing.checkout_success'))
    } else if (checkout === 'cancel') {
      toast(t('billing.checkout_cancel'))
    }
  }, [searchParams, t])

  useEffect(() => {
    if (!canWrite || searchParams.get('autoCheckout') !== '1' || autoCheckoutStarted.current) {
      return
    }
    const raw = sessionStorage.getItem('pendingCheckout')
    if (!raw) {
      return
    }
    autoCheckoutStarted.current = true
    sessionStorage.removeItem('pendingCheckout')
    try {
      const p = JSON.parse(raw) as { package_id: number; billing_cycle: 'monthly' | 'yearly' }
      if (typeof p.package_id === 'number' && (p.billing_cycle === 'monthly' || p.billing_cycle === 'yearly')) {
        checkoutM.mutate(p)
      }
    } catch {
      /* ignore */
    }
  }, [searchParams, checkoutM, canWrite])

  const packages = pkgs.data?.packages ?? []
  const subRows: SubRow[] = subs.data?.data ?? []
  const lic = licenseQ.data?.license

  const tierLabel =
    lic?.tier === 'pro'
      ? t('billing.tier_pro')
      : lic?.tier === 'community'
        ? t('billing.tier_community')
        : lic?.tier === 'standard'
          ? t('billing.tier_standard')
          : lic?.tier === 'none'
            ? t('billing.tier_none')
            : t('billing.tier_unknown')

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <CreditCard className="h-8 w-8 text-emerald-500" />
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{t('billing.title')}</h1>
          <p className="text-gray-500 dark:text-gray-400 text-sm">{t('billing.subtitle')}</p>
        </div>
      </div>

      {!canWrite && (
        <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-200">
          {t('billing.read_only_hint')}
        </div>
      )}

      <div className="card overflow-hidden">
        <div className="flex items-center gap-2 border-b border-gray-100 px-4 py-3 dark:border-gray-800">
          <ShieldCheck className="h-5 w-5 text-primary-600 dark:text-primary-400" />
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
            {t('billing.license_title')}
          </h2>
        </div>
        {licenseQ.isError ? (
          <div className="p-4 text-sm text-red-700 dark:text-red-300">
            <p>{t('billing.license_load_error')}</p>
            <button type="button" className="btn-secondary mt-3 text-sm" onClick={() => void licenseQ.refetch()}>
              {t('domains.refresh')}
            </button>
          </div>
        ) : licenseQ.isLoading ? (
          <p className="p-4 text-gray-500">{t('common.loading')}</p>
        ) : !lic?.has_license_key ? (
          <p className="p-4 text-sm text-gray-500">{t('billing.license_none')}</p>
        ) : (
          <dl className="grid gap-3 p-4 text-sm sm:grid-cols-2">
            <div>
              <dt className="text-gray-500">{t('billing.license_plan')}</dt>
              <dd className="font-medium text-gray-900 dark:text-white">
                {lic.plan_name ?? lic.plan ?? '—'}
              </dd>
            </div>
            <div>
              <dt className="text-gray-500">{t('billing.license_tier')}</dt>
              <dd className="font-medium text-gray-900 dark:text-white">{tierLabel}</dd>
            </div>
            <div>
              <dt className="text-gray-500">{t('billing.license_status')}</dt>
              <dd className="font-medium text-gray-900 dark:text-white">
                {lic.valid ? t('billing.license_active') : t('billing.license_inactive')}
                {lic.license_status ? ` (${lic.license_status})` : ''}
              </dd>
            </div>
            <div>
              <dt className="text-gray-500">{t('billing.license_expires')}</dt>
              <dd className="font-medium text-gray-900 dark:text-white">
                {formatLicenseDate(lic.expires_at)}
              </dd>
            </div>
            <div>
              <dt className="text-gray-500">{t('billing.payment_method')}</dt>
              <dd className="font-medium text-gray-900 dark:text-white">
                {lic.payment_method_label ?? lic.billing_provider ?? t('billing.payment_manual')}
              </dd>
            </div>
            <div>
              <dt className="text-gray-500">{t('billing.subscription_hub')}</dt>
              <dd className="font-medium text-gray-900 dark:text-white">
                {lic.subscription_status ?? '—'}
                {lic.renews_at ? ` · ${formatLicenseDate(lic.renews_at)}` : ''}
              </dd>
            </div>
          </dl>
        )}
        {lic?.hub_reachable === false && (
          <p className="border-t border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-200">
            {t('billing.hub_unreachable')}
          </p>
        )}
        {lic?.downgraded_to_community && (
          <p className="border-t border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-200">
            {t('billing.license_downgraded')}
          </p>
        )}
        {lic?.message && !lic.valid && (
          <p className="border-t border-gray-100 px-4 py-2 text-xs text-gray-500 dark:border-gray-800">
            {lic.message}
          </p>
        )}
      </div>

      <div>
        <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-3">{t('billing.packages')}</h2>
        {pkgs.isError && (
          <div className="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/40 dark:bg-red-950/20 dark:text-red-200">
            <p>{t('billing.packages_load_error')}</p>
            <button type="button" className="btn-secondary mt-2 text-xs" onClick={() => void pkgs.refetch()}>
              {t('domains.refresh')}
            </button>
          </div>
        )}
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {packages.map((p) => (
            <div key={p.id} className="card p-5 flex flex-col gap-3">
              <div>
                <h3 className="font-semibold text-gray-900 dark:text-white">{p.name}</h3>
                {p.description && (
                  <p className="text-sm text-gray-500 mt-1 line-clamp-3">{p.description}</p>
                )}
              </div>
              <div className="text-sm text-gray-600 dark:text-gray-400">
                <p>
                  {t('billing.price_monthly', { price: p.price_monthly, currency: p.currency })}
                </p>
                <p>
                  {t('billing.price_yearly', { price: p.price_yearly, currency: p.currency })}
                </p>
              </div>
              <div className="flex gap-2 mt-auto">
                <button
                  type="button"
                  className="btn-primary text-sm flex-1"
                  disabled={!canWrite || !p.is_active || checkoutM.isPending}
                  onClick={() => checkoutM.mutate({ package_id: p.id, billing_cycle: 'monthly' })}
                >
                  {t('billing.cycle_monthly')}
                </button>
                <button
                  type="button"
                  className="btn-secondary text-sm flex-1"
                  disabled={!canWrite || !p.is_active || checkoutM.isPending}
                  onClick={() => checkoutM.mutate({ package_id: p.id, billing_cycle: 'yearly' })}
                >
                  {t('billing.cycle_yearly')}
                </button>
              </div>
            </div>
          ))}
        </div>
        {pkgs.isLoading && <p className="text-gray-500 py-4">{t('common.loading')}</p>}
        {!pkgs.isLoading && !pkgs.isError && packages.length === 0 && (
          <p className="text-gray-500 py-4">{t('common.no_data')}</p>
        )}
      </div>

      <div className="card overflow-hidden">
        <h2 className="text-lg font-semibold text-gray-900 dark:text-white px-4 py-3 border-b border-gray-100 dark:border-gray-800">
          {t('billing.subscriptions')}
        </h2>
        {subs.isError ? (
          <div className="p-4 text-sm text-red-700 dark:text-red-300">
            <p>{t('billing.subs_load_error')}</p>
            <button type="button" className="btn-secondary mt-3 text-sm" onClick={() => void subs.refetch()}>
              {t('domains.refresh')}
            </button>
          </div>
        ) : (
          <>
            <table className="w-full text-sm">
              <thead className="bg-gray-50 dark:bg-gray-800/80">
                <tr>
                  <th className="text-left px-4 py-2">{t('billing.col_package')}</th>
                  <th className="text-left px-4 py-2">{t('billing.col_cycle')}</th>
                  <th className="text-left px-4 py-2">{t('billing.col_amount')}</th>
                  <th className="text-left px-4 py-2">{t('common.status')}</th>
                </tr>
              </thead>
              <tbody>
                {subRows.map((s) => (
                  <tr key={s.id} className="border-t border-gray-100 dark:border-gray-800">
                    <td className="px-4 py-2">{s.hosting_package?.name ?? '—'}</td>
                    <td className="px-4 py-2">
                      {s.billing_cycle === 'yearly'
                        ? t('billing.cycle_yearly')
                        : s.billing_cycle === 'monthly'
                          ? t('billing.cycle_monthly')
                          : s.billing_cycle}
                    </td>
                    <td className="px-4 py-2">
                      {s.amount} {s.currency}
                    </td>
                    <td className="px-4 py-2">{s.status}</td>
                  </tr>
                ))}
              </tbody>
            </table>
            {subs.isLoading && <p className="p-4 text-gray-500">{t('common.loading')}</p>}
            {!subs.isLoading && subRows.length === 0 && (
              <p className="p-6 text-center text-gray-500">{t('common.no_data')}</p>
            )}
          </>
        )}
      </div>
    </div>
  )
}
