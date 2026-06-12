import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowRightLeft, Plus, Save, Trash2 } from 'lucide-react'
import toast from 'react-hot-toast'
import api from '../services/api'
import { useAutoDomainId } from '../hooks/useAutoDomainId'

type RedirectRule = {
  id: string
  source: string
  target: string
  status: number
  enabled: boolean
  preserve_query: boolean
  match_type: 'exact' | 'prefix' | 'wildcard'
}

const emptyRule = (): RedirectRule => ({
  id: '',
  source: '/',
  target: '/',
  status: 301,
  enabled: true,
  preserve_query: true,
  match_type: 'exact',
})

export default function RedirectsPage() {
  const { t } = useTranslation()
  const qc = useQueryClient()
  const { domainId, setDomainId, domainsQ } = useAutoDomainId({ param: false })
  const [rules, setRules] = useState<RedirectRule[]>([])

  const redirectsQ = useQuery({
    queryKey: ['redirects', domainId],
    enabled: domainId !== '',
    queryFn: async () => {
      const { data } = await api.get(`/domains/${domainId}/redirects`)
      return data as { rules?: RedirectRule[]; server_type?: string }
    },
  })

  useEffect(() => {
    setRules((redirectsQ.data?.rules ?? []).map((r) => ({
      ...r,
      status: r.status || 301,
      preserve_query: r.preserve_query !== false,
      match_type: (r.match_type || 'exact') as RedirectRule['match_type'],
    })))
  }, [redirectsQ.data])

  const saveM = useMutation({
    mutationFn: async () => {
      const { data } = await api.put(`/domains/${domainId}/redirects`, { rules })
      return data
    },
    onSuccess: () => {
      toast.success(t('redirects.saved'))
      qc.invalidateQueries({ queryKey: ['redirects', domainId] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const addRule = () => setRules((prev) => [...prev, emptyRule()])
  const removeRule = (idx: number) => setRules((prev) => prev.filter((_, i) => i !== idx))
  const updateRule = (idx: number, patch: Partial<RedirectRule>) =>
    setRules((prev) => prev.map((r, i) => (i === idx ? { ...r, ...patch } : r)))

  const nginxOnly = Boolean(redirectsQ.data?.server_type && redirectsQ.data.server_type !== 'nginx')

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <ArrowRightLeft className="h-8 w-8 text-indigo-500" />
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{t('redirects.title')}</h1>
          <p className="text-gray-500 dark:text-gray-400 text-sm">{t('redirects.subtitle')}</p>
        </div>
      </div>

      <div className="card p-4 space-y-4">
        <div>
          <label className="label">{t('redirects.domain')}</label>
          <select
            className="input min-w-[260px]"
            value={domainId}
            onChange={(e) => setDomainId(e.target.value ? Number(e.target.value) : '')}
          >
            <option value="">{t('common.select')}</option>
            {(domainsQ.data ?? []).map((d) => (
              <option key={d.id} value={d.id}>{d.name}</option>
            ))}
          </select>
        </div>

        {nginxOnly && (
          <p className="text-sm text-amber-700 dark:text-amber-300">{t('redirects.nginx_only')}</p>
        )}

        {domainId !== '' && (
          <>
            <p className="text-xs text-gray-500 dark:text-gray-400">{t('redirects.seo_hint')}</p>
            <div className="space-y-3">
              {rules.map((rule, idx) => (
                <div key={idx} className="grid gap-3 md:grid-cols-12 items-end border border-gray-100 dark:border-gray-800 rounded-lg p-3">
                  <div className="md:col-span-3">
                    <label className="label">{t('redirects.source')}</label>
                    <input className="input w-full font-mono text-sm" value={rule.source} onChange={(e) => updateRule(idx, { source: e.target.value })} placeholder="/eski-sayfa" />
                  </div>
                  <div className="md:col-span-3">
                    <label className="label">{t('redirects.target')}</label>
                    <input className="input w-full font-mono text-sm" value={rule.target} onChange={(e) => updateRule(idx, { target: e.target.value })} placeholder="/yeni veya https://..." />
                  </div>
                  <div className="md:col-span-2">
                    <label className="label">{t('redirects.status')}</label>
                    <select className="input w-full" value={rule.status} onChange={(e) => updateRule(idx, { status: Number(e.target.value) })}>
                      <option value={301}>{t('redirects.status_301')}</option>
                      <option value={308}>{t('redirects.status_308')}</option>
                      <option value={302}>{t('redirects.status_302')}</option>
                      <option value={307}>{t('redirects.status_307')}</option>
                    </select>
                  </div>
                  <div className="md:col-span-2">
                    <label className="label">{t('redirects.match')}</label>
                    <select className="input w-full" value={rule.match_type} onChange={(e) => updateRule(idx, { match_type: e.target.value as RedirectRule['match_type'] })}>
                      <option value="exact">{t('redirects.match_exact')}</option>
                      <option value="prefix">{t('redirects.match_prefix')}</option>
                      <option value="wildcard">{t('redirects.match_wildcard')}</option>
                    </select>
                  </div>
                  <div className="md:col-span-2 flex flex-wrap gap-3 items-center">
                    <label className="flex items-center gap-2 text-xs">
                      <input type="checkbox" checked={rule.enabled} onChange={(e) => updateRule(idx, { enabled: e.target.checked })} />
                      {t('redirects.enabled')}
                    </label>
                    <label className="flex items-center gap-2 text-xs">
                      <input type="checkbox" checked={rule.preserve_query} onChange={(e) => updateRule(idx, { preserve_query: e.target.checked })} />
                      {t('redirects.preserve_query')}
                    </label>
                    <button type="button" className="btn-danger p-2" onClick={() => removeRule(idx)} aria-label={t('common.delete')}>
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </div>
                </div>
              ))}
              {rules.length === 0 && <p className="text-sm text-gray-500">{t('redirects.empty')}</p>}
            </div>
            <div className="flex gap-2">
              <button type="button" className="btn-secondary" onClick={addRule}>
                <Plus className="h-4 w-4 inline mr-1" />
                {t('redirects.add')}
              </button>
              <button type="button" className="btn-primary" onClick={() => saveM.mutate()} disabled={saveM.isPending || nginxOnly}>
                <Save className="h-4 w-4 inline mr-1" />
                {t('redirects.save_apply')}
              </button>
            </div>
          </>
        )}
      </div>
    </div>
  )
}
