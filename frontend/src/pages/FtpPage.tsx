import { useEffect, useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { HardDrive, Loader2, Plus, Server, Trash2 } from 'lucide-react'
import toast from 'react-hot-toast'
import { copyPlaintextWithToasts } from '../lib/copyText'
import api from '../services/api'
import { useAutoDomainId } from '../hooks/useAutoDomainId'
import { useAuthStore } from '../store/authStore'
import { tokenHasAbility } from '../lib/abilities'

const USERNAME_RE = /^[a-zA-Z0-9][a-zA-Z0-9._-]{0,30}$/

function isSafeHomeDirectory(path: string): boolean {
  const t = path.trim().replace(/^\/+|\/+$/g, '')
  if (!t) return false
  if (t.includes('\\')) return false
  const segs = t.split('/').filter(Boolean)
  return segs.length > 0 && !segs.some((s) => s === '.' || s === '..' || s.length > 255)
}

type FtpRow = {
  id: number
  username: string
  home_directory: string
  quota_mb: number
  status: string
}

type EngineFtpRow = {
  username?: string
  home_directory?: string
  quota_mb?: number
}

export default function FtpPage() {
  const { t } = useTranslation()
  const qc = useQueryClient()
  const user = useAuthStore((s) => s.user)
  const abilities = useAuthStore((s) => s.user?.abilities)
  const canWrite = tokenHasAbility(abilities, 'ftp:write')
  const isAdmin = user?.roles?.some((r) => r.name === 'admin')

  const { domainId, setDomainId, domainsQ } = useAutoDomainId({ param: 'domain' })
  const [showAdd, setShowAdd] = useState(false)
  const [deletingId, setDeletingId] = useState<number | null>(null)
  const [passwordReveal, setPasswordReveal] = useState<{
    value: string
    username: string
    expiresAt: number
  } | null>(null)
  const [nowTs, setNowTs] = useState(Date.now())

  const domainName = useMemo(
    () => (domainsQ.data ?? []).find((d) => d.id === domainId)?.name ?? '',
    [domainsQ.data, domainId],
  )

  const q = useQuery({
    queryKey: ['ftp', domainId],
    enabled: domainId !== '',
    queryFn: async () => (await api.get(`/domains/${domainId}/ftp`)).data,
  })

  const createM = useMutation({
    mutationFn: async (payload: {
      username: string
      home_directory: string
      quota_mb?: number
    }) => api.post(`/domains/${domainId}/ftp`, payload),
    onSuccess: (res) => {
      const data = res.data as { password_plain?: string; account?: { username?: string } }
      const plain = data.password_plain
      const username = data.account?.username ?? ''
      toast.success(t('ftp.created'))
      if (plain) {
        setPasswordReveal({
          value: plain,
          username,
          expiresAt: Date.now() + 30_000,
        })
        void copyPlaintextWithToasts(plain, {
          ok: t('databases.password_copied'),
          fail: t('databases.copy_failed'),
        })
      }
      qc.invalidateQueries({ queryKey: ['ftp', domainId] })
      setShowAdd(false)
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const deleteM = useMutation({
    mutationFn: async (id: number) => api.delete(`/ftp/${id}`),
    onSuccess: () => {
      setDeletingId(null)
      toast.success(t('ftp.deleted'))
      qc.invalidateQueries({ queryKey: ['ftp', domainId] })
    },
    onError: (err: unknown) => {
      setDeletingId(null)
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

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

  const local: FtpRow[] = q.data?.local ?? []
  const engineList: EngineFtpRow[] = Array.isArray(q.data?.engine) ? q.data.engine : []

  const submitCreate = (ev: React.FormEvent<HTMLFormElement>) => {
    ev.preventDefault()
    const fd = new FormData(ev.currentTarget)
    const username = String(fd.get('username') || '').trim()
    const home_directory = String(fd.get('home_directory') || 'public_html').trim()
    if (!USERNAME_RE.test(username)) {
      toast.error(t('ftp.username_invalid'))
      return
    }
    if (!isSafeHomeDirectory(home_directory)) {
      toast.error(t('ftp.home_invalid'))
      return
    }
    createM.mutate({
      username,
      home_directory,
      quota_mb: fd.get('quota_mb') ? Number(fd.get('quota_mb')) : -1,
    })
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-500/10">
            <HardDrive className="h-7 w-7 text-slate-600 dark:text-slate-400" />
          </div>
          <div>
            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{t('nav.ftp')}</h1>
            <p className="text-sm text-gray-500 dark:text-gray-400">{t('ftp.subtitle')}</p>
          </div>
        </div>
        {canWrite && (
          <button
            type="button"
            className="btn-primary inline-flex items-center gap-2"
            disabled={domainId === ''}
            onClick={() => setShowAdd(true)}
          >
            <Plus className="h-4 w-4" />
            {t('common.create')}
          </button>
        )}
      </div>

      <div className="card p-4">
        <label className="label">{t('domains.name')}</label>
        <select
          className="input max-w-md"
          value={domainId}
          onChange={(e) => setDomainId(e.target.value ? Number(e.target.value) : '')}
        >
          <option value="">{t('common.select')}</option>
          {(domainsQ.data ?? []).map((d) => (
            <option key={d.id} value={d.id}>
              {d.name}
            </option>
          ))}
        </select>
        {domainName && (
          <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">{t('ftp.connection_hint', { host: domainName })}</p>
        )}
      </div>

      {!canWrite && domainId !== '' && (
        <p className="text-sm text-amber-700 dark:text-amber-300">{t('ftp.read_only_hint')}</p>
      )}

      {domainId === '' ? (
        <p className="rounded-lg border border-dashed border-gray-200 bg-gray-50/80 px-4 py-8 text-center text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-800/30 dark:text-gray-400">
          {t('ftp.no_domain')}
        </p>
      ) : q.isLoading ? (
        <p className="flex items-center justify-center gap-2 py-12 text-gray-500">
          <Loader2 className="h-5 w-5 animate-spin" />
          {t('common.loading')}
        </p>
      ) : q.isError ? (
        <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-8 text-center dark:border-red-900/40 dark:bg-red-950/30">
          <p className="text-sm text-red-700 dark:text-red-300">{t('ftp.load_error')}</p>
          <button type="button" className="btn-secondary mt-3 text-sm" onClick={() => void q.refetch()}>
            {t('domains.refresh')}
          </button>
        </div>
      ) : (
        <>
          <div className="card overflow-hidden">
            <h3 className="border-b border-gray-100 px-4 py-3 text-sm font-semibold text-gray-900 dark:border-gray-800 dark:text-white">
              {t('ftp.panel_accounts_title')}
            </h3>
            {local.length === 0 ? (
              <p className="p-6 text-center text-gray-500 dark:text-gray-400">{t('ftp.empty')}</p>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead className="bg-gray-50 dark:bg-gray-800/80">
                    <tr>
                      <th className="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-400">
                        {t('ftp.col_username')}
                      </th>
                      <th className="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-400">
                        {t('ftp.col_directory')}
                      </th>
                      <th className="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-400">
                        {t('ftp.col_quota')}
                      </th>
                      <th className="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-400">
                        {t('ftp.col_status')}
                      </th>
                      {canWrite && (
                        <th className="px-4 py-2 text-right font-medium text-gray-600 dark:text-gray-400">
                          {t('common.actions')}
                        </th>
                      )}
                    </tr>
                  </thead>
                  <tbody>
                    {local.map((a) => (
                      <tr key={a.id} className="border-t border-gray-100 dark:border-gray-800">
                        <td className="px-4 py-2 font-mono text-gray-900 dark:text-gray-100">{a.username}</td>
                        <td className="px-4 py-2 font-mono text-gray-700 dark:text-gray-300">{a.home_directory}</td>
                        <td className="px-4 py-2 text-gray-700 dark:text-gray-300">
                          {a.quota_mb < 0 ? t('ftp.quota_unlimited') : `${a.quota_mb} MB`}
                        </td>
                        <td className="px-4 py-2 text-gray-600 dark:text-gray-400">{a.status}</td>
                        {canWrite && (
                          <td className="px-4 py-2 text-right">
                            <button
                              type="button"
                              className="inline-flex rounded-lg p-1.5 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                              disabled={deletingId === a.id}
                              title={t('common.delete')}
                              onClick={() => {
                                if (window.confirm(t('ftp.delete_confirm', { username: a.username }))) {
                                  setDeletingId(a.id)
                                  deleteM.mutate(a.id)
                                }
                              }}
                            >
                              {deletingId === a.id ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                              ) : (
                                <Trash2 className="h-4 w-4" />
                              )}
                            </button>
                          </td>
                        )}
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>

          {isAdmin && engineList.length > 0 && (
            <div className="card overflow-hidden">
              <div className="flex items-center gap-2 border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                <Server className="h-4 w-4 text-gray-500" />
                <h3 className="text-sm font-semibold text-gray-900 dark:text-white">{t('ftp.engine_accounts_title')}</h3>
              </div>
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead className="bg-gray-50 dark:bg-gray-800/80">
                    <tr>
                      <th className="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-400">
                        {t('ftp.col_username')}
                      </th>
                      <th className="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-400">
                        {t('ftp.col_directory')}
                      </th>
                      <th className="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-400">
                        {t('ftp.col_quota')}
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    {engineList.map((a, i) => (
                      <tr key={`${a.username ?? i}`} className="border-t border-gray-100 dark:border-gray-800">
                        <td className="px-4 py-2 font-mono">{a.username ?? '—'}</td>
                        <td className="px-4 py-2 font-mono">{a.home_directory ?? '—'}</td>
                        <td className="px-4 py-2">
                          {a.quota_mb == null ? '—' : a.quota_mb < 0 ? t('ftp.quota_unlimited') : `${a.quota_mb} MB`}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {!isAdmin && (
            <p className="text-xs text-gray-500 dark:text-gray-400">{t('ftp.engine_admin_hint')}</p>
          )}
        </>
      )}

      {showAdd && domainId !== '' && canWrite && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="card max-w-md w-full space-y-4 bg-white p-6 dark:bg-gray-900">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">{t('ftp.add_title')}</h2>
            <form className="space-y-3" onSubmit={submitCreate}>
              <div>
                <label className="label">{t('ftp.col_username')}</label>
                <input name="username" className="input w-full" required autoComplete="off" />
                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{t('ftp.username_hint')}</p>
              </div>
              <div>
                <label className="label">{t('ftp.col_directory')}</label>
                <input name="home_directory" className="input w-full" defaultValue="public_html" required />
                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{t('ftp.home_hint')}</p>
              </div>
              <div>
                <label className="label">{t('ftp.quota_label')}</label>
                <input name="quota_mb" type="number" className="input w-full" defaultValue={-1} min={-1} />
                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{t('ftp.quota_hint')}</p>
              </div>
              <div className="flex justify-end gap-2 pt-2">
                <button type="button" className="btn-secondary" onClick={() => setShowAdd(false)}>
                  {t('common.cancel')}
                </button>
                <button type="submit" className="btn-primary inline-flex items-center gap-2" disabled={createM.isPending}>
                  {createM.isPending ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
                  {t('common.create')}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {passwordReveal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="card max-w-md w-full space-y-4 bg-white p-6 dark:bg-gray-900">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">{t('ftp.password_temp_title')}</h2>
            {passwordReveal.username ? (
              <p className="text-sm text-gray-600 dark:text-gray-400">
                {t('ftp.password_for_user', { username: passwordReveal.username })}
              </p>
            ) : null}
            <p className="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
              {t('databases.password_temp_desc', { seconds: secondsLeft })}
            </p>
            <div className="break-all rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 font-mono text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
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
              <button type="button" className="btn-primary" onClick={() => setPasswordReveal(null)}>
                {t('common.close')}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
