import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Navigate } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useAuthStore } from '../store/authStore'
import api from '../services/api'
import { Users, Plus, UserX, UserCheck, KeyRound, LogIn } from 'lucide-react'
import toast from 'react-hot-toast'

type Role = { name: string }
type AdminUser = {
  id: number
  name: string
  email: string
  status: string
  roles: Role[]
}

function statusLabel(t: (key: string) => string, status: string): string {
  const key = `common.${status}`
  const translated = t(key)
  return translated === key ? status : translated
}

export default function AdminUsersPage() {
  const { t, i18n } = useTranslation()
  const qc = useQueryClient()
  const currentUser = useAuthStore((s) => s.user)
  const token = useAuthStore((s) => s.token)
  const portal = useAuthStore((s) => s.portal)
  const startImpersonation = useAuthStore((s) => s.startImpersonation)
  const isAdmin = currentUser?.roles?.some((r) => r.name === 'admin')
  const [search, setSearch] = useState('')
  const [showAdd, setShowAdd] = useState(false)
  const [resetTarget, setResetTarget] = useState<AdminUser | null>(null)

  const q = useQuery({
    queryKey: ['admin-users', search],
    queryFn: async () =>
      (await api.get('/admin/users', { params: { ...(search ? { search } : {}), per_page: 100 } })).data,
    enabled: !!isAdmin,
  })

  const rolesQ = useQuery({
    queryKey: ['admin-roles-options'],
    queryFn: async () => (await api.get<{ id: number; name: string; display_name?: string | null }[]>('/admin/roles')).data,
    enabled: !!isAdmin,
  })

  const createM = useMutation({
    mutationFn: async (payload: Record<string, unknown>) => api.post('/admin/users', payload),
    onSuccess: () => {
      toast.success(t('users.created'))
      qc.invalidateQueries({ queryKey: ['admin-users'] })
      setShowAdd(false)
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
      const first = ax.response?.data?.errors
        ? Object.values(ax.response.data.errors)[0]?.[0]
        : undefined
      toast.error(first ?? ax.response?.data?.message ?? String(err))
    },
  })

  const suspendM = useMutation({
    mutationFn: async (id: number) => api.post(`/admin/users/${id}/suspend`),
    onSuccess: () => {
      toast.success(t('users.suspended'))
      qc.invalidateQueries({ queryKey: ['admin-users'] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const activateM = useMutation({
    mutationFn: async (id: number) => api.post(`/admin/users/${id}/activate`),
    onSuccess: () => {
      toast.success(t('users.activated'))
      qc.invalidateQueries({ queryKey: ['admin-users'] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const resetPasswordM = useMutation({
    mutationFn: async (payload: { id: number; password: string; password_confirmation: string }) =>
      api.post(`/admin/users/${payload.id}/reset-password`, {
        password: payload.password,
        password_confirmation: payload.password_confirmation,
      }),
    onSuccess: () => {
      toast.success(t('users.password_reset_ok'))
      setResetTarget(null)
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
      const first = ax.response?.data?.errors
        ? Object.values(ax.response.data.errors)[0]?.[0]
        : undefined
      toast.error(first ?? ax.response?.data?.message ?? String(err))
    },
  })

  const impersonateM = useMutation({
    mutationFn: async (id: number) => api.post(`/admin/users/${id}/impersonate`),
    onSuccess: (res) => {
      const data = res.data as { user: AdminUser & { roles: Role[] }; token: string }
      if (!currentUser || !token) return
      startImpersonation(data.user as never, data.token, {
        user: currentUser,
        token,
        portal,
      })
      toast.success(t('users.impersonation_started'))
      window.location.href = '/dashboard'
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const rows: AdminUser[] = q.data?.data ?? []
  const defaultLocale = i18n.language.split('-')[0] || 'en'

  if (!isAdmin) {
    return <Navigate to="/dashboard" replace />
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <Users className="h-8 w-8 text-primary-500" />
          <div>
            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{t('nav.users')}</h1>
            <p className="text-gray-500 dark:text-gray-400 text-sm">{t('users.admin_subtitle')}</p>
          </div>
        </div>
        <button type="button" className="btn-primary flex items-center gap-2" onClick={() => setShowAdd(true)}>
          <Plus className="h-4 w-4" />
          {t('common.create')}
        </button>
      </div>

      <div className="card p-4">
        <input
          type="search"
          className="input max-w-md"
          placeholder={t('common.search')}
          value={search}
          onChange={(e) => setSearch(e.target.value)}
        />
      </div>

      {showAdd && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="card max-w-lg w-full p-6 space-y-4 bg-white dark:bg-gray-900 max-h-[90vh] overflow-y-auto">
            <h2 className="text-lg font-semibold">{t('users.new_user_title')}</h2>
            {rolesQ.isError && (
              <p className="text-sm text-red-600 dark:text-red-400">
                {t('users.roles_load_error')}{' '}
                <button type="button" className="underline" onClick={() => void rolesQ.refetch()}>
                  {t('common.refresh')}
                </button>
              </p>
            )}
            <form
              className="space-y-3"
              onSubmit={(ev) => {
                ev.preventDefault()
                const fd = new FormData(ev.currentTarget)
                createM.mutate({
                  name: String(fd.get('name')),
                  email: String(fd.get('email')),
                  password: String(fd.get('password')),
                  password_confirmation: String(fd.get('password_confirmation')),
                  role: String(fd.get('role')),
                  locale: String(fd.get('locale') || defaultLocale),
                })
              }}
            >
              <input name="name" className="input w-full" required placeholder={t('users.col_name')} />
              <input name="email" type="email" className="input w-full" required placeholder={t('users.col_email')} />
              <input name="password" type="password" className="input w-full" required minLength={12} placeholder={t('auth.password')} />
              <input
                name="password_confirmation"
                type="password"
                className="input w-full"
                required
                minLength={12}
                placeholder={t('settings.new_password_confirm')}
              />
              <p className="text-xs text-gray-500">{t('users.password_policy_admin')}</p>
              <select name="role" className="input w-full" defaultValue="user" required disabled={rolesQ.isLoading || rolesQ.isError}>
                {(rolesQ.data ?? []).map((r) => (
                  <option key={r.id} value={r.name}>
                    {r.display_name ?? r.name}
                  </option>
                ))}
              </select>
              <select name="locale" className="input w-full" defaultValue={defaultLocale}>
                <option value="tr">tr</option>
                <option value="en">en</option>
              </select>
              <div className="flex justify-end gap-2">
                <button type="button" className="btn-secondary" onClick={() => setShowAdd(false)}>
                  {t('common.cancel')}
                </button>
                <button type="submit" className="btn-primary" disabled={createM.isPending}>
                  {t('common.create')}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {resetTarget && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="card max-w-md w-full p-6 space-y-4 bg-white dark:bg-gray-900">
            <h2 className="text-lg font-semibold">{t('users.reset_title')}</h2>
            <p className="text-xs text-gray-500">{resetTarget.email}</p>
            <form
              className="space-y-3"
              onSubmit={(ev) => {
                ev.preventDefault()
                const fd = new FormData(ev.currentTarget)
                resetPasswordM.mutate({
                  id: resetTarget.id,
                  password: String(fd.get('password') || ''),
                  password_confirmation: String(fd.get('password_confirmation') || ''),
                })
              }}
            >
              <input name="password" type="password" className="input w-full" required minLength={12} />
              <input name="password_confirmation" type="password" className="input w-full" required minLength={12} />
              <p className="text-[11px] text-amber-600 dark:text-amber-400">{t('users.password_policy_admin')}</p>
              <div className="flex justify-end gap-2">
                <button type="button" className="btn-secondary" onClick={() => setResetTarget(null)}>
                  {t('common.cancel')}
                </button>
                <button type="submit" className="btn-primary" disabled={resetPasswordM.isPending}>
                  {t('users.reset_submit')}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      <div className="card overflow-hidden">
        {q.isError ? (
          <div className="p-6 text-sm text-red-600 dark:text-red-400">
            <p>{t('users.load_error')}</p>
            <button type="button" className="btn-secondary mt-3 text-xs" onClick={() => void q.refetch()}>
              {t('common.refresh')}
            </button>
          </div>
        ) : (
          <>
            <table className="w-full text-sm">
              <thead className="bg-gray-50 dark:bg-gray-800/80">
                <tr>
                  <th className="text-left px-4 py-2">{t('users.col_name')}</th>
                  <th className="text-left px-4 py-2">{t('users.col_email')}</th>
                  <th className="text-left px-4 py-2">{t('roles.col_name')}</th>
                  <th className="text-left px-4 py-2">{t('common.status')}</th>
                  <th className="text-right px-4 py-2">{t('common.actions')}</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((u) => (
                  <tr key={u.id} className="border-t border-gray-100 dark:border-gray-800">
                    <td className="px-4 py-2">{u.name}</td>
                    <td className="px-4 py-2 font-mono text-xs">{u.email}</td>
                    <td className="px-4 py-2">{u.roles?.map((r) => r.name).join(', ') ?? '—'}</td>
                    <td className="px-4 py-2">{statusLabel(t, u.status)}</td>
                    <td className="px-4 py-2 text-right space-x-1">
                      {u.roles?.some((r) => r.name === 'user') && u.status === 'active' && (
                        <button
                          type="button"
                          className="btn-primary text-xs py-1"
                          onClick={() => {
                            if (!window.confirm(t('users.confirm_impersonate', { email: u.email }))) return
                            impersonateM.mutate(u.id)
                          }}
                          disabled={impersonateM.isPending || u.id === currentUser?.id}
                        >
                          <LogIn className="h-3 w-3 inline" /> {t('users.impersonate')}
                        </button>
                      )}
                      <button
                        type="button"
                        className="btn-secondary text-xs py-1"
                        onClick={() => setResetTarget(u)}
                        disabled={resetPasswordM.isPending}
                      >
                        <KeyRound className="h-3 w-3 inline" /> {t('users.reset_password')}
                      </button>
                      {u.status === 'active' ? (
                        <button
                          type="button"
                          className="btn-secondary text-xs py-1"
                          onClick={() => {
                            if (!window.confirm(t('users.confirm_suspend'))) return
                            suspendM.mutate(u.id)
                          }}
                          disabled={suspendM.isPending || u.id === currentUser?.id}
                        >
                          <UserX className="h-3 w-3 inline" /> {t('users.suspend')}
                        </button>
                      ) : (
                        <button
                          type="button"
                          className="btn-secondary text-xs py-1"
                          onClick={() => {
                            if (!window.confirm(t('users.confirm_activate'))) return
                            activateM.mutate(u.id)
                          }}
                          disabled={activateM.isPending}
                        >
                          <UserCheck className="h-3 w-3 inline" /> {t('users.activate')}
                        </button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
            {q.isLoading && <p className="p-6 text-center text-gray-500">{t('common.loading')}</p>}
            {!q.isLoading && rows.length === 0 && (
              <p className="p-6 text-center text-gray-500">{t('common.no_data')}</p>
            )}
          </>
        )}
      </div>
    </div>
  )
}
