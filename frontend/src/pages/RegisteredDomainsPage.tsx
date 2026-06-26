import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Globe, ArrowRightLeft, Server, ExternalLink } from 'lucide-react'
import api from '../services/api'
import toast from 'react-hot-toast'

type Registration = {
  id: number
  domain: string
  status: string
  registrar: string
  source_registrar?: string | null
  expires_at?: string | null
  auto_renew: boolean
  locked: boolean
}

type Registrar = {
  id: string
  name: string
  website?: string | null
  transfer_url?: string | null
  auth_code_help?: string
  ns_help?: string
}

export default function RegisteredDomainsPage() {
  const { t } = useTranslation()
  const qc = useQueryClient()
  const [transferDomain, setTransferDomain] = useState('')
  const [transferRegistrar, setTransferRegistrar] = useState('natro')
  const [authCode, setAuthCode] = useState('')

  const portfolioQ = useQuery({
    queryKey: ['domain-portfolio'],
    queryFn: async () => (await api.get('/domain-portfolio')).data as {
      registrations: Registration[]
      hosting_domains: { id: number; name: string; status: string }[]
      transfers: { id: number; domain: string; status: string; source_registrar: string }[]
      nameservers: { primary?: string; secondary?: string }
    },
  })

  const registrarsQ = useQuery({
    queryKey: ['domain-registrars'],
    queryFn: async () => (await api.get('/domain-portfolio/registrars')).data as { providers: Registrar[] },
  })

  const transferM = useMutation({
    mutationFn: async () =>
      api.post('/domain-portfolio/transfers', {
        domain: transferDomain,
        source_registrar: transferRegistrar,
        auth_code: authCode || undefined,
        direction: 'in',
      }),
    onSuccess: () => {
      toast.success(t('domain_portfolio.transfer_submitted'))
      setTransferDomain('')
      setAuthCode('')
      qc.invalidateQueries({ queryKey: ['domain-portfolio'] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const toggleM = useMutation({
    mutationFn: async ({ id, field, value }: { id: number; field: 'auto_renew' | 'locked'; value: boolean }) =>
      api.patch(`/domain-portfolio/registrations/${id}`, { [field]: value }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['domain-portfolio'] }),
  })

  const data = portfolioQ.data
  const registrars = registrarsQ.data?.providers ?? []

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Globe className="h-8 w-8 text-primary-500" />
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{t('domain_portfolio.title')}</h1>
          <p className="text-sm text-gray-500 dark:text-gray-400">{t('domain_portfolio.subtitle')}</p>
        </div>
      </div>

      {data?.nameservers && (data.nameservers.primary || data.nameservers.secondary) && (
        <div className="card p-4 border-l-4 border-primary-500">
          <h2 className="font-semibold text-gray-900 dark:text-white mb-2">{t('domain_portfolio.ns_title')}</h2>
          <p className="text-sm text-gray-600 dark:text-gray-300 mb-2">{t('domain_portfolio.ns_hint')}</p>
          <ul className="font-mono text-sm space-y-1">
            {data.nameservers.primary && <li>NS1: {data.nameservers.primary}</li>}
            {data.nameservers.secondary && <li>NS2: {data.nameservers.secondary}</li>}
          </ul>
        </div>
      )}

      <div className="card overflow-hidden">
        <div className="px-4 py-3 border-b border-gray-100 dark:border-gray-800 font-semibold">
          {t('domain_portfolio.registered_list')}
        </div>
        {portfolioQ.isLoading ? (
          <p className="p-6 text-gray-500">{t('common.loading')}</p>
        ) : (data?.registrations?.length ?? 0) === 0 ? (
          <p className="p-6 text-gray-500">{t('domain_portfolio.no_registrations')}</p>
        ) : (
          <table className="w-full text-sm">
            <thead className="bg-gray-50 dark:bg-gray-800/80">
              <tr>
                <th className="text-left px-4 py-2">{t('domains.col_name')}</th>
                <th className="text-left px-4 py-2">{t('common.status')}</th>
                <th className="text-left px-4 py-2">{t('domain_portfolio.registrar')}</th>
                <th className="text-left px-4 py-2">{t('domain_portfolio.expires')}</th>
                <th className="text-right px-4 py-2">{t('common.actions')}</th>
              </tr>
            </thead>
            <tbody>
              {data?.registrations.map((r) => (
                <tr key={r.id} className="border-t border-gray-100 dark:border-gray-800">
                  <td className="px-4 py-2 font-mono">{r.domain}</td>
                  <td className="px-4 py-2">{r.status}</td>
                  <td className="px-4 py-2">{r.registrar}</td>
                  <td className="px-4 py-2">{r.expires_at ? new Date(r.expires_at).toLocaleDateString() : '—'}</td>
                  <td className="px-4 py-2 text-right space-x-2">
                    <label className="text-xs inline-flex items-center gap-1">
                      <input
                        type="checkbox"
                        checked={r.auto_renew}
                        onChange={(e) =>
                          toggleM.mutate({ id: r.id, field: 'auto_renew', value: e.target.checked })
                        }
                      />
                      {t('domain_portfolio.auto_renew')}
                    </label>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      {(data?.hosting_domains?.length ?? 0) > 0 && (
        <div className="card p-4">
          <h2 className="font-semibold flex items-center gap-2 mb-3">
            <Server className="h-4 w-4" />
            {t('domain_portfolio.hosting_domains')}
          </h2>
          <ul className="space-y-1 text-sm font-mono">
            {data?.hosting_domains.map((d) => (
              <li key={d.id}>
                {d.name} <span className="text-gray-400">({d.status})</span>
              </li>
            ))}
          </ul>
        </div>
      )}

      <div className="grid lg:grid-cols-2 gap-6">
        <div className="card p-5 space-y-4">
          <h2 className="font-semibold flex items-center gap-2">
            <ArrowRightLeft className="h-4 w-4" />
            {t('domain_portfolio.transfer_in')}
          </h2>
          <p className="text-sm text-gray-500">{t('domain_portfolio.transfer_in_hint')}</p>
          <input
            className="input w-full"
            placeholder="ornek.com"
            value={transferDomain}
            onChange={(e) => setTransferDomain(e.target.value)}
          />
          <select className="input w-full" value={transferRegistrar} onChange={(e) => setTransferRegistrar(e.target.value)}>
            {registrars.map((r) => (
              <option key={r.id} value={r.id}>
                {r.name}
              </option>
            ))}
          </select>
          <input
            className="input w-full"
            placeholder={t('domain_portfolio.auth_code')}
            value={authCode}
            onChange={(e) => setAuthCode(e.target.value)}
          />
          <button
            type="button"
            className="btn-primary w-full"
            disabled={!transferDomain || transferM.isPending}
            onClick={() => transferM.mutate()}
          >
            {t('domain_portfolio.submit_transfer')}
          </button>
        </div>

        <div className="card p-5 space-y-3 max-h-[420px] overflow-y-auto">
          <h2 className="font-semibold">{t('domain_portfolio.registrar_guides')}</h2>
          {registrars.map((r) => (
            <div key={r.id} className="border-b border-gray-100 dark:border-gray-800 pb-3 last:border-0">
              <div className="flex items-center justify-between gap-2">
                <span className="font-medium">{r.name}</span>
                {r.website && (
                  <a href={r.website} target="_blank" rel="noreferrer" className="text-primary-500 text-xs inline-flex items-center gap-0.5">
                    {t('common.open')} <ExternalLink className="h-3 w-3" />
                  </a>
                )}
              </div>
              {r.auth_code_help && <p className="text-xs text-gray-500 mt-1">{r.auth_code_help}</p>}
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}
