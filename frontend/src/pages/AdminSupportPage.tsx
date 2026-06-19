import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '../services/api'
import { Headphones, RefreshCw, ArrowLeft, Send } from 'lucide-react'
import toast from 'react-hot-toast'

type TicketMessage = { id: number; is_staff: boolean; body: string; created_at: string; user?: { name: string } }
type Ticket = {
  id: number
  number: string
  subject: string
  status: string
  priority: string
  department: string
  last_reply_at: string | null
  messages_count?: number
  messages?: TicketMessage[]
  user?: { name: string; email: string }
}

const statusMeta: Record<string, { label: string; cls: string }> = {
  open: { label: 'Açık', cls: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200' },
  answered: { label: 'Yanıtlandı', cls: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' },
  customer_reply: { label: 'Müşteri yanıtı', cls: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' },
  on_hold: { label: 'Beklemede', cls: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' },
  closed: { label: 'Kapalı', cls: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' },
}

function fmt(iso?: string | null): string {
  if (!iso) return '—'
  try { return new Date(iso).toLocaleString('tr-TR') } catch { return iso }
}

export default function AdminSupportPage() {
  const qc = useQueryClient()
  const [activeId, setActiveId] = useState<number | null>(null)
  const [status, setStatus] = useState('')
  const [reply, setReply] = useState('')

  const listQ = useQuery({
    queryKey: ['admin-tickets', status],
    queryFn: async () => (await api.get('/admin/support/tickets', { params: status ? { status } : {} })).data as { data: Ticket[] },
  })
  const ticketQ = useQuery({
    queryKey: ['admin-ticket', activeId],
    queryFn: async () => (await api.get(`/admin/support/tickets/${activeId}`)).data as Ticket,
    enabled: activeId !== null,
  })

  const replyM = useMutation({
    mutationFn: async () => api.post(`/admin/support/tickets/${activeId}/reply`, { body: reply }),
    onSuccess: () => { setReply(''); void qc.invalidateQueries({ queryKey: ['admin-ticket', activeId] }); void qc.invalidateQueries({ queryKey: ['admin-tickets'] }) },
    onError: () => toast.error('Yanıt gönderilemedi.'),
  })
  const updateM = useMutation({
    mutationFn: async (newStatus: string) => api.patch(`/admin/support/tickets/${activeId}`, { status: newStatus }),
    onSuccess: () => { toast.success('Durum güncellendi.'); void qc.invalidateQueries({ queryKey: ['admin-ticket', activeId] }); void qc.invalidateQueries({ queryKey: ['admin-tickets'] }) },
  })

  const tickets = listQ.data?.data ?? []
  const ticket = ticketQ.data

  if (activeId !== null) {
    return (
      <div className="space-y-4">
        <button className="btn-secondary text-sm" onClick={() => setActiveId(null)}><ArrowLeft className="h-4 w-4" /> Tüm talepler</button>
        {ticketQ.isLoading || !ticket ? (
          <p className="text-gray-500">Yükleniyor…</p>
        ) : (
          <>
            <div className="card p-4 flex items-center justify-between gap-3 flex-wrap">
              <div>
                <h1 className="text-xl font-bold text-gray-900 dark:text-white">{ticket.subject}</h1>
                <p className="text-sm text-gray-500">{ticket.number} · {ticket.user?.name} ({ticket.user?.email})</p>
              </div>
              <select value={ticket.status} onChange={(e) => updateM.mutate(e.target.value)} className="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                {Object.entries(statusMeta).map(([k, v]) => <option key={k} value={k}>{v.label}</option>)}
              </select>
            </div>

            <div className="space-y-3">
              {(ticket.messages ?? []).map((m) => (
                <div key={m.id} className={`card p-4 ${m.is_staff ? 'border-l-4 border-l-primary-500' : 'border-l-4 border-l-amber-400'}`}>
                  <div className="flex items-center justify-between text-xs text-gray-500 mb-2">
                    <span className="font-medium text-gray-700 dark:text-gray-300">{m.is_staff ? 'Destek Ekibi' : m.user?.name ?? 'Müşteri'}</span>
                    <span>{fmt(m.created_at)}</span>
                  </div>
                  <p className="whitespace-pre-wrap text-sm text-gray-800 dark:text-gray-200">{m.body}</p>
                </div>
              ))}
            </div>

            <div className="card p-4 space-y-3">
              <textarea value={reply} onChange={(e) => setReply(e.target.value)} rows={4} placeholder="Yanıtınızı yazın…" className="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" />
              <div className="flex justify-end">
                <button className="btn-primary text-sm" onClick={() => replyM.mutate()} disabled={replyM.isPending || !reply.trim()}>
                  <Send className="h-4 w-4" /> Yanıtla
                </button>
              </div>
            </div>
          </>
        )}
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Headphones className="h-8 w-8 text-emerald-500" />
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Destek Talepleri</h1>
          <p className="text-gray-500 dark:text-gray-400 text-sm">Müşteri destek taleplerini yönetin.</p>
        </div>
        <div className="ml-auto flex items-center gap-2">
          <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            <option value="">Tümü</option>
            {Object.entries(statusMeta).map(([k, v]) => <option key={k} value={k}>{v.label}</option>)}
          </select>
          <button className="btn-secondary text-sm" onClick={() => void listQ.refetch()}><RefreshCw className="h-4 w-4" /></button>
        </div>
      </div>

      <div className="card overflow-x-auto">
        <table className="w-full text-sm">
          <thead className="bg-gray-50 dark:bg-gray-800/80">
            <tr>
              <th className="text-left px-4 py-2">No</th>
              <th className="text-left px-4 py-2">Müşteri</th>
              <th className="text-left px-4 py-2">Konu</th>
              <th className="text-left px-4 py-2">Son Yanıt</th>
              <th className="text-left px-4 py-2">Durum</th>
            </tr>
          </thead>
          <tbody>
            {tickets.map((tk) => (
              <tr key={tk.id} className="border-t border-gray-100 dark:border-gray-800 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50" onClick={() => setActiveId(tk.id)}>
                <td className="px-4 py-2 font-medium text-gray-900 dark:text-white">{tk.number}</td>
                <td className="px-4 py-2">{tk.user?.name ?? '—'}</td>
                <td className="px-4 py-2">{tk.subject}</td>
                <td className="px-4 py-2">{fmt(tk.last_reply_at)}</td>
                <td className="px-4 py-2"><span className={`rounded-full px-2 py-0.5 text-xs font-medium ${(statusMeta[tk.status] ?? statusMeta.open).cls}`}>{(statusMeta[tk.status] ?? statusMeta.open).label}</span></td>
              </tr>
            ))}
          </tbody>
        </table>
        {listQ.isLoading && <p className="p-4 text-gray-500">Yükleniyor…</p>}
        {!listQ.isLoading && tickets.length === 0 && <p className="p-6 text-center text-gray-500">Kayıt yok.</p>}
      </div>
    </div>
  )
}
