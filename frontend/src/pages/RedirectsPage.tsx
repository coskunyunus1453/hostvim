import { useEffect, useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowRightLeft, Loader2, Plus, Save, Trash2 } from 'lucide-react'
import toast from 'react-hot-toast'
import api from '../services/api'
import { useAutoDomainId } from '../hooks/useAutoDomainId'
import { useAuthStore } from '../store/authStore'
import { tokenHasAbility } from '../lib/abilities'

type RedirectRule = {
  id: string
  source: string
  target: string
  status: number
  enabled: boolean
  preserve_query: boolean
  match_type: 'exact' | 'prefix' | 'wildcard'
}

const MAX_RULES = 50

const emptyRule = (): RedirectRule => ({
  id: '',
  source: '/',
  target: '/',
  status: 301,
  enabled: true,
  preserve_query: true,
  match_type: 'exact',
})

function normalizeLoadedRules(rules: RedirectRule[]): RedirectRule[] {
  return rules.map((r) => ({
    ...r,
    status: r.status || 301,
    preserve_query: r.preserve_query !== false,
    match_type: (r.match_type || 'exact') as RedirectRule['match_type'],
  }))
}

function rulesEqual(a: RedirectRule[], b: RedirectRule[]): boolean {
  return JSON.stringify(a) === JSON.stringify(b)
}

function validateRulesClient(
  rules: RedirectRule[],
  t: (key: string, options?: Record<string, unknown>) => string,
): string | null {
  if (rules.length > MAX_RULES) {
    return t('redirects.too_many_rules', { max: MAX_RULES })
  }
  for (const rule of rules) {
    const source = rule.source.trim() || '/'
    if (!source.startsWith('/')) {
      return t('redirects.source_must_start_slash')
    }
    if (/[\r\n\t]/.test(source)) {
      return t('redirects.source_must_start_slash')
    }
    const target = rule.target.trim()
    if (!target) {
      return t('redirects.target_required')
    }
    if (/[\r\n\t]/.test(target) || target.startsWith('//')) {
      return t('redirects.target_required')
    }
    if (rule.match_type === 'wildcard' && (source.match(/\*/g) ?? []).length !== 1) {
      return t('redirects.wildcard_one_star')
    }
  }
  return null
}

export default function RedirectsPage() {
  const { t } = useTranslation()
  const qc = useQueryClient()
  const abilities = useAuthStore((s) => s.user?.abilities)
  const canWrite = tokenHasAbility(abilities, 'domains:write')
  const { domainId, setDomainId, domainsQ } = useAutoDomainId({ param: 'domain' })
  const [rules, setRules] = useState<RedirectRule[]>([])
  const [loadedRules, setLoadedRules] = useState<RedirectRule[]>([])

  const redirectsQ = useQuery({
    queryKey: ['redirects', domainId],
    enabled: domainId !== '',
    queryFn: async () => {
      const { data } = await api.get(`/domains/${domainId}/redirects`)
      return data as { rules?: RedirectRule[]; server_type?: string }
    },
  })

  useEffect(() => {
    if (redirectsQ.isLoading || redirectsQ.isFetching) return
    const next = normalizeLoadedRules(redirectsQ.data?.rules ?? [])
    setRules(next)
    setLoadedRules(next)
  }, [redirectsQ.data, redirectsQ.isLoading, redirectsQ.isFetching, domainId])

  const hasUnsaved = useMemo(() => !rulesEqual(rules, loadedRules), [rules, loadedRules])

  const saveM = useMutation({
    mutationFn: async () => {
      const err = validateRulesClient(rules, t)
      if (err) {
        throw new Error(err)
      }
      const { data } = await api.put(`/domains/${domainId}/redirects`, { rules })
      return data
    },
    onSuccess: () => {
      toast.success(t('redirects.saved'))
      setLoadedRules(rules)
      qc.invalidateQueries({ queryKey: ['redirects', domainId] })
    },
    onError: (err: unknown) => {
      if (err instanceof Error && err.message && !('response' in err)) {
        toast.error(err.message)
        return
      }
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const serverType = redirectsQ.data?.server_type ?? ''
  const nginxOnly = Boolean(serverType && serverType !== 'nginx')
  const readOnly = !canWrite || nginxOnly

  const addRule = () => {
    if (rules.length >= MAX_RULES) {
      toast.error(t('redirects.too_many_rules', { max: MAX_RULES }))
      return
    }
    setRules((prev) => [...prev, emptyRule()])
  }

  const removeRule = (idx: number) => {
    if (!window.confirm(t('redirects.remove_rule_confirm'))) return
    setRules((prev) => prev.filter((_, i) => i !== idx))
  }

  const updateRule = (idx: number, patch: Partial<RedirectRule>) =>
    setRules((prev) => prev.map((r, i) => (i === idx ? { ...r, ...patch } : r)))

  const handleDomainChange = (nextId: number | '') => {
    if (nextId === domainId) return
    if (hasUnsaved) {
      if (!window.confirm(t('redirects.unsaved_switch_confirm'))) return
    }
    setDomainId(nextId)
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <ArrowRightLeft className="h-8 w-8 text-indigo-500" />
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{t('redirects.title')}</h1>
          <p className="text-gray-500 dark:text-gray-400 text-sm">{t('redirects.subtitle')}</p>
        </div>
      </div>

      {!canWrite && (
        <p className="text-sm text-amber-700 dark:text-amber-300">{t('redirects.read_only_hint')}</p>
      )}

      <div className="card p-4 space-y-4">
        <div>
          <label className="label">{t('redirects.domain')}</label>
          <select
            className="input min-w-[260px]"
            value={domainId}
            onChange={(e) =>
              handleDomainChange(e.target.value ? Number(e.target.value) : '')
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

        {domainId !== '' && redirectsQ.isLoading && (
          <div className="flex items-center gap-2 text-sm text-gray-500">
            <Loader2 className="h-4 w-4 animate-spin" />
            {t('common.loading')}
          </div>
        )}

        {domainId !== '' && redirectsQ.isError && (
          <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 dark:border-red-900/40 dark:bg-red-950/30">
            <p className="text-sm text-red-800 dark:text-red-200">
              {(redirectsQ.error as { response?: { data?: { message?: string } } })?.response?.data
                ?.message ?? t('redirects.list_load_error')}
            </p>
            <button
              type="button"
              className="btn-secondary mt-2 text-sm"
              onClick={() => void redirectsQ.refetch()}
            >
              {t('domains.refresh')}
            </button>
          </div>
        )}

        {nginxOnly && domainId !== '' && !redirectsQ.isLoading && (
          <p className="text-sm text-amber-700 dark:text-amber-300">{t('redirects.nginx_only')}</p>
        )}

        {domainId !== '' && !redirectsQ.isLoading && !redirectsQ.isError && (
          <>
            <p className="text-xs text-gray-500 dark:text-gray-400">{t('redirects.seo_hint')}</p>
            {hasUnsaved && canWrite && !nginxOnly && (
              <p className="text-xs font-medium text-amber-600 dark:text-amber-400">
                {t('redirects.unsaved_hint')}
              </p>
            )}
            <div className="space-y-3">
              {rules.map((rule, idx) => (
                <div
                  key={rule.id || `new-${idx}`}
                  className="grid gap-3 md:grid-cols-12 items-end border border-gray-100 dark:border-gray-800 rounded-lg p-3"
                >
                  <div className="md:col-span-3">
                    <label className="label">{t('redirects.source')}</label>
                    <input
                      className="input w-full font-mono text-sm disabled:opacity-60"
                      value={rule.source}
                      disabled={readOnly}
                      onChange={(e) => updateRule(idx, { source: e.target.value })}
                      placeholder="/eski-sayfa"
                    />
                  </div>
                  <div className="md:col-span-3">
                    <label className="label">{t('redirects.target')}</label>
                    <input
                      className="input w-full font-mono text-sm disabled:opacity-60"
                      value={rule.target}
                      disabled={readOnly}
                      onChange={(e) => updateRule(idx, { target: e.target.value })}
                      placeholder="/yeni veya https://..."
                    />
                  </div>
                  <div className="md:col-span-2">
                    <label className="label">{t('redirects.status')}</label>
                    <select
                      className="input w-full disabled:opacity-60"
                      value={rule.status}
                      disabled={readOnly}
                      onChange={(e) => updateRule(idx, { status: Number(e.target.value) })}
                    >
                      <option value={301}>{t('redirects.status_301')}</option>
                      <option value={308}>{t('redirects.status_308')}</option>
                      <option value={302}>{t('redirects.status_302')}</option>
                      <option value={307}>{t('redirects.status_307')}</option>
                    </select>
                  </div>
                  <div className="md:col-span-2">
                    <label className="label">{t('redirects.match')}</label>
                    <select
                      className="input w-full disabled:opacity-60"
                      value={rule.match_type}
                      disabled={readOnly}
                      onChange={(e) =>
                        updateRule(idx, { match_type: e.target.value as RedirectRule['match_type'] })
                      }
                    >
                      <option value="exact">{t('redirects.match_exact')}</option>
                      <option value="prefix">{t('redirects.match_prefix')}</option>
                      <option value="wildcard">{t('redirects.match_wildcard')}</option>
                    </select>
                  </div>
                  <div className="md:col-span-2 flex flex-wrap gap-3 items-center">
                    <label className="flex items-center gap-2 text-xs">
                      <input
                        type="checkbox"
                        checked={rule.enabled}
                        disabled={readOnly}
                        onChange={(e) => updateRule(idx, { enabled: e.target.checked })}
                      />
                      {t('redirects.enabled')}
                    </label>
                    <label className="flex items-center gap-2 text-xs">
                      <input
                        type="checkbox"
                        checked={rule.preserve_query}
                        disabled={readOnly}
                        onChange={(e) => updateRule(idx, { preserve_query: e.target.checked })}
                      />
                      {t('redirects.preserve_query')}
                    </label>
                    {canWrite && !nginxOnly && (
                      <button
                        type="button"
                        className="btn-danger p-2"
                        onClick={() => removeRule(idx)}
                        aria-label={t('common.delete')}
                      >
                        <Trash2 className="h-4 w-4" />
                      </button>
                    )}
                  </div>
                </div>
              ))}
              {rules.length === 0 && <p className="text-sm text-gray-500">{t('redirects.empty')}</p>}
            </div>
            {canWrite && !nginxOnly && (
              <div className="flex flex-wrap gap-2">
                <button type="button" className="btn-secondary" onClick={addRule}>
                  <Plus className="h-4 w-4 inline mr-1" />
                  {t('redirects.add')}
                </button>
                <button
                  type="button"
                  className="btn-primary"
                  onClick={() => saveM.mutate()}
                  disabled={saveM.isPending || !hasUnsaved}
                >
                  {saveM.isPending ? (
                    <Loader2 className="h-4 w-4 inline mr-1 animate-spin" />
                  ) : (
                    <Save className="h-4 w-4 inline mr-1" />
                  )}
                  {t('redirects.save_apply')}
                </button>
              </div>
            )}
          </>
        )}
      </div>
    </div>
  )
}
