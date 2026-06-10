import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Globe, Plus, Trash2, Download, Wand2 } from 'lucide-react'
import toast from 'react-hot-toast'
import api from '../services/api'
import { useDomainsList } from '../hooks/useDomains'
import { useAuthStore } from '../store/authStore'

type DnsRow = {
  id: number
  type: string
  name: string
  value: string
  ttl?: number
  priority?: number | null
}

export default function DnsPage() {
  const { t } = useTranslation()
  const qc = useQueryClient()
  const isAdmin = useAuthStore((s) => s.user?.roles?.some((r) => r.name === 'admin'))
  const domainsQ = useDomainsList()
  const [domainId, setDomainId] = useState<number | ''>('')
  const [showAdd, setShowAdd] = useState(false)

  const recordsQ = useQuery({
    queryKey: ['dns', domainId],
    enabled: domainId !== '',
    queryFn: async () => (await api.get(`/domains/${domainId}/dns`)).data,
  })

  const createM = useMutation({
    mutationFn: async (payload: {
      type: string
      name: string
      value: string
      ttl?: number
      priority?: number
    }) => api.post(`/domains/${domainId}/dns`, payload),
    onSuccess: () => {
      toast.success(t('dns.created'))
      qc.invalidateQueries({ queryKey: ['dns', domainId] })
      setShowAdd(false)
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const bootstrapM = useMutation({
    mutationFn: async () => api.post(`/domains/${domainId}/dns/bootstrap`),
    onSuccess: (res) => {
      const created = Number(res.data?.created ?? 0)
      toast.success(
        created > 0 ? t('dns.bootstrap_done', { count: created }) : t('dns.bootstrap_none'),
      )
      qc.invalidateQueries({ queryKey: ['dns', domainId] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const deleteM = useMutation({
    mutationFn: async (id: number) => api.delete(`/dns/${id}`),
    onSuccess: () => {
      toast.success(t('dns.deleted'))
      qc.invalidateQueries({ queryKey: ['dns', domainId] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const exportZone = async () => {
    if (domainId === '') return
    try {
      const { data } = await api.get(`/domains/${domainId}/dns/zone`)
      const domainName = String(data.domain ?? 'zone')
      const zoneText = String(data.zone ?? '')
      const blob = new Blob([zoneText], { type: 'text/plain;charset=utf-8' })
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `${domainName}.zone`
      a.click()
      window.URL.revokeObjectURL(url)
      toast.success(t('dns.export_done'))
    } catch (err: unknown) {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? t('dns.export_failed'))
    }
  }

  const records: DnsRow[] = recordsQ.data?.records ?? []
  const bindInfo = recordsQ.data?.bind as
    | { enabled?: boolean; ns?: [string, string]; server_ip?: string }
    | undefined
  const nsList = Array.isArray(bindInfo?.ns) ? bindInfo.ns.filter(Boolean) : []
  const dnsTypes = ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'CAA', 'SRV', 'PTR']

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <Globe className="h-8 w-8 text-secondary-500" />
          <div>
            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">DNS</h1>
            <p className="text-gray-500 dark:text-gray-400 text-sm">{t('dns.subtitle')}</p>
          </div>
        </div>
        <div className="flex flex-wrap gap-2">
          <button
            type="button"
            className="btn-secondary flex items-center gap-2"
            disabled={!domainId || bootstrapM.isPending}
            onClick={() => bootstrapM.mutate()}
          >
            <Wand2 className="h-4 w-4" />
            {t('dns.bootstrap_defaults')}
          </button>
          <button
            type="button"
            className="btn-secondary flex items-center gap-2"
            disabled={!domainId}
            onClick={() => void exportZone()}
          >
            <Download className="h-4 w-4" />
            {t('dns.export_zone')}
          </button>
          <button
            type="button"
            className="btn-primary flex items-center gap-2"
            disabled={!domainId}
            onClick={() => setShowAdd(true)}
          >
            <Plus className="h-4 w-4" />
            {t('common.create')}
          </button>
        </div>
      </div>

      <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/40 space-y-2">
        <p className="font-medium text-blue-900 dark:text-blue-100">{t('dns.bind_info_title')}</p>
        <p className="text-sm text-blue-800 dark:text-blue-200">{t('dns.bind_info_body')}</p>
        {nsList.length > 0 && (
          <div className="text-sm text-blue-800 dark:text-blue-200">
            <p className="font-medium">{t('dns.nameservers_title')}</p>
            <ul className="mt-1 list-disc list-inside font-mono">
              {nsList.map((ns) => (
                <li key={ns}>{ns}</li>
              ))}
            </ul>
            {bindInfo?.server_ip && (
              <p className="mt-2">
                {t('dns.glue_hint', { ip: bindInfo.server_ip, ns1: nsList[0] ?? '', ns2: nsList[1] ?? '' })}
              </p>
            )}
          </div>
        )}
        <p className="text-sm text-blue-800 dark:text-blue-200">{t('dns.registrar_hint')}</p>
        {isAdmin && (
          <Link to="/admin/dns-settings" className="inline-block text-sm font-medium text-blue-900 underline dark:text-blue-100">
            {t('dns.admin_settings_link')}
          </Link>
        )}
      </div>

      <div className="card p-4 flex flex-wrap gap-4 items-end">
        <div>
          <label className="label">{t('domains.name')}</label>
          <select
            className="input min-w-[220px]"
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
        </div>
      </div>

      {showAdd && domainId && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="card max-w-lg w-full p-6 space-y-4 bg-white dark:bg-gray-900">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">DNS kaydı</h2>
            <form
              className="space-y-3"
              onSubmit={(ev) => {
                ev.preventDefault()
                const fd = new FormData(ev.currentTarget)
                createM.mutate({
                  type: String(fd.get('type') || 'A'),
                  name: String(fd.get('name') || '@'),
                  value: String(fd.get('value') || ''),
                  ttl: fd.get('ttl') ? Number(fd.get('ttl')) : 3600,
                  priority: fd.get('priority') ? Number(fd.get('priority')) : undefined,
                })
              }}
            >
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="label">Tip</label>
                  <select name="type" className="input w-full">
                    {dnsTypes.map((x) => (
                      <option key={x} value={x}>
                        {x}
                      </option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="label">İsim</label>
                  <input name="name" className="input w-full" defaultValue="@" />
                </div>
              </div>
              <div>
                <label className="label">Değer</label>
                <input name="value" className="input w-full" required />
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="label">TTL</label>
                  <input name="ttl" type="number" className="input w-full" defaultValue={3600} />
                </div>
                <div>
                  <label className="label">Öncelik (MX)</label>
                  <input name="priority" type="number" className="input w-full" placeholder="10" />
                </div>
              </div>
              <div className="flex justify-end gap-2 pt-2">
                <button type="button" className="btn-secondary" onClick={() => setShowAdd(false)}>
                  {t('common.cancel')}
                </button>
                <button type="submit" className="btn-primary" disabled={createM.isPending}>
                  {t('common.save')}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      <div className="card overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-gray-50 dark:bg-gray-800/80">
            <tr>
              <th className="text-left px-4 py-2">Tip</th>
              <th className="text-left px-4 py-2">İsim</th>
              <th className="text-left px-4 py-2">Değer</th>
              <th className="text-left px-4 py-2">TTL</th>
              <th className="text-right px-4 py-2">{t('common.actions')}</th>
            </tr>
          </thead>
          <tbody>
            {!domainId && (
              <tr>
                <td colSpan={5} className="px-4 py-8 text-center text-gray-500">
                  {t('common.select')}
                </td>
              </tr>
            )}
            {domainId &&
              records.map((r) => (
                <tr key={r.id} className="border-t border-gray-100 dark:border-gray-800">
                  <td className="px-4 py-2 font-mono">{r.type}</td>
                  <td className="px-4 py-2 font-mono">{r.name}</td>
                  <td className="px-4 py-2 font-mono break-all">{r.value}</td>
                  <td className="px-4 py-2">{r.ttl ?? '—'}</td>
                  <td className="px-4 py-2 text-right">
                    <button
                      type="button"
                      className="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-gray-500"
                      onClick={() => {
                        if (window.confirm(t('common.confirm_delete'))) deleteM.mutate(r.id)
                      }}
                    >
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </td>
                </tr>
              ))}
          </tbody>
        </table>
        {domainId && !recordsQ.isLoading && records.length === 0 && (
          <p className="p-6 text-center text-gray-500">{t('common.no_data')}</p>
        )}
      </div>

      {domainId &&
        Array.isArray(recordsQ.data?.engine_preview) &&
        recordsQ.data.engine_preview.length > 0 && (
          <div className="card overflow-hidden">
            <h3 className="px-4 py-3 text-sm font-semibold border-b border-gray-100 dark:border-gray-800">
              Engine (senkron) kayıtlar
            </h3>
            <table className="w-full text-sm">
              <thead className="bg-gray-50 dark:bg-gray-800/80">
                <tr>
                  <th className="text-left px-4 py-2">ID</th>
                  <th className="text-left px-4 py-2">Tip</th>
                  <th className="text-left px-4 py-2">İsim</th>
                  <th className="text-left px-4 py-2">Değer</th>
                  <th className="text-left px-4 py-2">TTL</th>
                </tr>
              </thead>
              <tbody>
                {(recordsQ.data.engine_preview as Record<string, unknown>[]).map((r, i) => (
                  <tr key={String(r.id ?? i)} className="border-t border-gray-100 dark:border-gray-800">
                    <td className="px-4 py-2 font-mono text-xs">{r.id != null ? String(r.id) : '—'}</td>
                    <td className="px-4 py-2 font-mono">{r.type != null ? String(r.type) : '—'}</td>
                    <td className="px-4 py-2 font-mono">{r.name != null ? String(r.name) : '—'}</td>
                    <td className="px-4 py-2 font-mono break-all">{r.value != null ? String(r.value) : '—'}</td>
                    <td className="px-4 py-2">{r.ttl != null ? String(r.ttl) : '—'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
    </div>
  )
}
