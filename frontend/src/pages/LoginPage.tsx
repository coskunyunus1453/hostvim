import { useEffect, useState } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { useQueryClient } from '@tanstack/react-query'
import { useAuthStore } from '../store/authStore'
import { authService, type LoginResponse } from '../services/authService'
import { useBranding, WL_SESSION_KEY } from '../hooks/useBranding'
import { Server, Eye, EyeOff, Globe, ShieldCheck, Zap, ArrowRight } from 'lucide-react'
import toast from 'react-hot-toast'
import { mustEnrollTwoFactor } from '../lib/authRoles'
import { prefetchDashboard } from '../lib/prefetchDashboard'
import { safeBrandingImageUrl } from '../lib/urlSafety'

export default function LoginPage() {
  const { t } = useTranslation()
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const setAuth = useAuthStore((s) => s.setAuth)
  const portal: 'customer' | 'vendor' = 'customer'

  const [step, setStep] = useState<'password' | 'twofa'>('password')
  const [otp, setOtp] = useState('')
  const [backupCode, setBackupCode] = useState('')
  const [twofaMethod, setTwofaMethod] = useState<'otp' | 'backup'>('otp')

  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [showPassword, setShowPassword] = useState(false)
  const [loading, setLoading] = useState(false)
  const { data: branding } = useBranding()
  const customerLogoUrl = safeBrandingImageUrl(branding?.logo_customer_url)
  const qc = useQueryClient()

  useEffect(() => {
    const wl = searchParams.get('wl')?.trim()
    if (wl) {
      sessionStorage.setItem(WL_SESSION_KEY, wl)
      qc.invalidateQueries({ queryKey: ['branding'] })
    }
  }, [searchParams, qc])

  useEffect(() => {
    if (searchParams.get('demo') === '1') {
      setEmail('demo@panelze.com')
      setPassword('DemoPanelze2026!')
    }
  }, [searchParams])

  useEffect(() => {
    const pid = searchParams.get('package_id')
    const cycle = searchParams.get('billing_cycle') ?? searchParams.get('cycle')
    if (pid) {
      const n = Number(pid)
      if (!Number.isNaN(n)) {
        sessionStorage.setItem(
          'pendingCheckout',
          JSON.stringify({
            package_id: n,
            billing_cycle: cycle === 'yearly' || cycle === 'annual' ? 'yearly' : 'monthly',
          }),
        )
      }
    }
  }, [searchParams])

  const navigateAfterLogin = () => {
    if (sessionStorage.getItem('pendingCheckout')) {
      navigate('/billing?autoCheckout=1')
      return
    }
    navigate('/dashboard')
  }

  const finishLogin = (data: LoginResponse) => {
    setAuth(data.user, data.token, portal, {
      enforce_admin_2fa: data.enforce_admin_2fa,
      white_label: data.white_label,
      active_plugin_slugs: data.active_plugin_slugs,
    })
    prefetchDashboard(queryClient)
    if (data.force_password_change || data.user.force_password_change) {
      toast('İlk giriş: Şifrenizi şimdi değiştirin.', { icon: '🔒' })
      navigate('/settings?mandatoryPassword=1')
      return
    }
    toast.success(t('dashboard.welcome'))
    const enforce = data.enforce_admin_2fa ?? null
    if (mustEnrollTwoFactor(data.user, enforce)) {
      navigate('/settings?mandatory2fa=1')
      return
    }
    navigateAfterLogin()
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (loading) return
    setLoading(true)

    try {
      if (step === 'password') {
        const result = await authService.login(email.trim(), password, portal)
        if (!result.ok && result.challenge === 'twofa_required') {
          setStep('twofa')
          setOtp('')
          setBackupCode('')
          return
        }
        if (result.ok) finishLogin(result.data)
        return
      }

      if (twofaMethod === 'otp') {
        const code = otp.trim()
        if (!/^\d{6}$/.test(code)) {
          toast.error('Geçerli 6 haneli OTP kodu girin.')
          return
        }

        const result = await authService.login(email.trim(), password, portal, { otp: code })
        if (result.ok) {
          finishLogin(result.data)
        } else {
          toast.error(result.message || t('common.error'))
        }
        return
      }

      const bc = backupCode.trim()
      if (!/^(\d{5}-\d{5}|\d{10})$/.test(bc)) {
        toast.error('Yedek kodu formatı: 12345-67890')
        return
      }

      const result = await authService.login(email.trim(), password, portal, { backupCode: bc })
      if (result.ok) {
        finishLogin(result.data)
      } else {
        toast.error(result.message || t('common.error'))
      }
    } catch (err: unknown) {
      const error = err as { response?: { data?: { message?: string; code?: string } } }
      const message = error.response?.data?.message || t('common.error')

      toast.error(message)
    } finally {
      setLoading(false)
    }
  }

  const loginTitle = branding?.login_title?.trim() || t('app.name')
  const loginSubtitle = branding?.login_subtitle?.trim() || t('auth.login_subtitle')
  const accent = branding?.primary_color || undefined
  const accent2 = branding?.secondary_color || undefined

  const features = [
    { icon: Globe, title: t('theme.login_feat1_title'), desc: t('theme.login_feat1_desc') },
    { icon: ShieldCheck, title: t('theme.login_feat2_title'), desc: t('theme.login_feat2_desc') },
    { icon: Zap, title: t('theme.login_feat3_title'), desc: t('theme.login_feat3_desc') },
  ]
  const segClass = (active: boolean) =>
    `flex-1 rounded-xl border py-2 text-sm font-medium transition-colors ${
      active
        ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-500/15 dark:text-primary-200'
        : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-panel-border dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.06]'
    }`

  return (
    <div className="flex min-h-screen bg-gray-50 dark:bg-panel-bg">
      <aside
        className="relative hidden w-[46%] max-w-[640px] flex-col justify-between overflow-hidden bg-ink-950 p-10 text-white lg:flex xl:p-14"
        style={accent && accent2 ? { background: `linear-gradient(160deg, ${accent}33, #15110e 55%, ${accent2}22)` } : undefined}
      >
        <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(80%_60%_at_0%_0%,rgba(249,115,22,0.35),transparent_60%),radial-gradient(60%_50%_at_100%_100%,rgba(251,191,36,0.14),transparent_60%)]" aria-hidden />
        <div className="pointer-events-none absolute -bottom-24 -right-24 h-80 w-80 rounded-full border border-white/10" aria-hidden />
        <div className="pointer-events-none absolute -bottom-10 -right-10 h-52 w-52 rounded-full border border-white/10" aria-hidden />

        <div className="relative flex items-center gap-3">
          {customerLogoUrl ? (
            <img src={customerLogoUrl} alt="" className="max-h-11 object-contain" />
          ) : (
            <>
              <span
                className="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-primary-600 shadow-glow"
                style={accent ? { background: accent } : undefined}
              >
                <Server className="h-5 w-5 text-white" />
              </span>
              <span>
                <span className="block text-lg font-bold leading-tight">{t('app.name')}</span>
                <span className="block text-xs text-gray-400">{t('theme.brand_tagline')}</span>
              </span>
            </>
          )}
        </div>

        <div className="relative">
          <h2 className="max-w-md text-3xl font-bold leading-tight tracking-tight xl:text-4xl">{t('theme.login_headline')}</h2>
          <p className="mt-3 max-w-md text-sm leading-relaxed text-gray-300">{t('theme.login_lead')}</p>
          <ul className="mt-8 space-y-4">
            {features.map((f) => (
              <li key={f.title} className="flex items-start gap-3">
                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-amber-200 ring-1 ring-inset ring-white/15">
                  <f.icon className="h-5 w-5" />
                </span>
                <span>
                  <span className="block text-sm font-semibold">{f.title}</span>
                  <span className="block text-sm text-gray-400">{f.desc}</span>
                </span>
              </li>
            ))}
          </ul>
        </div>

        <p className="relative text-xs text-gray-500">
          {t('app.name')} &mdash; {t('app.tagline')}
        </p>
      </aside>

      <main className="relative flex flex-1 items-center justify-center px-4 py-10 sm:px-8">
        <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(60%_40%_at_50%_0%,rgba(249,115,22,0.08),transparent_70%)]" aria-hidden />
        <div className="relative w-full max-w-md">
          <div className="mb-8 text-center lg:hidden">
            {customerLogoUrl ? (
              <div className="mb-4 flex justify-center">
                <img src={customerLogoUrl} alt="" className="max-h-16 object-contain" />
              </div>
            ) : (
              <span
                className="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-primary-600 shadow-glow"
                style={accent ? { background: accent } : undefined}
              >
                <Server className="h-7 w-7 text-white" />
              </span>
            )}
            <p className="text-sm text-gray-500 dark:text-gray-400">{t('theme.brand_tagline')}</p>
          </div>

          <div className="mb-6">
            <h1 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">{loginTitle}</h1>
            <p className="mt-1.5 text-sm text-gray-500 dark:text-gray-400">{loginSubtitle}</p>
          </div>

          <div className="card p-6 shadow-lift sm:p-8">
            <h2 className="mb-5 text-base font-semibold text-gray-900 dark:text-white">{t('auth.login_title')}</h2>
            {searchParams.get('demo') === '1' && (
              <div className="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-400/30 dark:bg-amber-500/10 dark:text-amber-100">
                <p className="font-semibold">Public demo hazır</p>
                <p className="mt-1 opacity-80">
                  Alanlar dolduruldu. Giriş yapın — yalnızca örnek siteler görünür; gerçek müşteri verisi yok.
                </p>
              </div>
            )}
            <form onSubmit={handleSubmit} className="space-y-5">
              {step === 'password' ? (
                <>
                  <div>
                    <label className="label">{t('auth.email')}</label>
                    <input
                      type="email"
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                      className="input py-3"
                      placeholder="admin@panelze.com"
                      autoComplete="username"
                      required
                    />
                  </div>

                  <div>
                    <label className="label">{t('auth.password')}</label>
                    <div className="relative">
                      <input
                        type={showPassword ? 'text' : 'password'}
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        className="input py-3 pr-12"
                        placeholder="••••••••"
                        autoComplete="current-password"
                        required
                      />
                      <button
                        type="button"
                        onClick={() => setShowPassword(!showPassword)}
                        className="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-1 text-gray-400 hover:text-gray-700 dark:hover:text-white"
                      >
                        {showPassword ? <EyeOff className="h-5 w-5" /> : <Eye className="h-5 w-5" />}
                      </button>
                    </div>
                  </div>

                  <label className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <input
                      type="checkbox"
                      className="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-white/5"
                    />
                    {t('auth.remember')}
                  </label>

                  <button
                    type="submit"
                    disabled={loading}
                    className="btn-primary w-full justify-center py-3"
                    style={accent ? { background: accent, borderColor: accent } : undefined}
                  >
                    {loading ? t('common.loading') : t('auth.login')}
                    {!loading && <ArrowRight className="h-4 w-4" />}
                  </button>
                </>
              ) : (
                <>
                  <div className="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-panel-border dark:bg-white/[0.03]">
                    <p className="mb-1 text-sm font-medium text-gray-900 dark:text-white">2FA doğrulama gerekli</p>
                    <p className="text-xs text-gray-500 dark:text-gray-400">OTP kodu veya yedek kod ile giriş yapın.</p>
                  </div>

                  <div className="flex gap-2">
                    <button type="button" onClick={() => setTwofaMethod('otp')} className={segClass(twofaMethod === 'otp')}>
                      OTP
                    </button>
                    <button type="button" onClick={() => setTwofaMethod('backup')} className={segClass(twofaMethod === 'backup')}>
                      Yedek Kod
                    </button>
                  </div>

                  {twofaMethod === 'otp' ? (
                    <div>
                      <label className="label">OTP (6 hane)</label>
                      <input
                        value={otp}
                        onChange={(e) => setOtp(e.target.value)}
                        inputMode="numeric"
                        autoComplete="one-time-code"
                        className="input py-3 text-center font-mono text-lg tracking-[0.4em]"
                        placeholder="123456"
                        required
                      />
                    </div>
                  ) : (
                    <div>
                      <label className="label">Yedek Kod</label>
                      <input
                        value={backupCode}
                        onChange={(e) => setBackupCode(e.target.value)}
                        className="input py-3 text-center font-mono tracking-widest"
                        placeholder="12345-67890"
                        required
                      />
                    </div>
                  )}

                  <button type="submit" disabled={loading} className="btn-primary w-full justify-center py-3">
                    {loading ? t('common.loading') : 'Doğrula ve Giriş Yap'}
                  </button>

                  <button
                    type="button"
                    onClick={() => {
                      setStep('password')
                      setOtp('')
                      setBackupCode('')
                    }}
                    className="w-full text-center text-sm text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                  >
                    Şifre ekranına geri dön
                  </button>
                </>
              )}
            </form>
          </div>

          <p className="mt-6 text-center text-xs text-gray-400 lg:hidden">
            {t('app.name')} &mdash; {t('app.tagline')}
          </p>
        </div>
      </main>
    </div>
  )
}
