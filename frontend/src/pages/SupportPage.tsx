import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '../services/api'
import { LifeBuoy, Plus, RefreshCw, ArrowLeft, Send } from 'lucide-react'
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
  try {
    return new Date(iso).toLocaleString('tr-TR')
  } catch {
    return iso
  }
}

export default function SupportPage() {
  const qc = useQueryClient()
  const [activeId, setActiveId] = useState<number | null>(null)
  const [creating, setCreating] = useState(false)
  const [form, setForm] = useState({ subject: '', body: '', department: 'general', priority: 'medium' })
  const [reply, setReply] = useState('')

  const listQ = useQuery({
    queryKey: ['support-tickets'],
    queryFn: async () => (await api.get('/support/tickets')).data as { data: Ticket[] },
  })
  const ticketQ = useQuery({
    queryKey: ['support-ticket', activeId],
    queryFn: async () => (await api.get(`/support/tickets/${activeId}`)).data as Ticket,
    enabled: activeId !== null,
  })

  const createM = useMutation({
    mutationFn: async () => (await api.post('/support/tickets', form)).data as Ticket,
    onSuccess: (t) => {
      toast.success('Talebiniz oluşturuldu.')
      setCreating(false)
      setForm({ subject: '', body: '', department: 'general', priority: 'medium' })
      void qc.invalidateQueries({ queryKey: ['support-tickets'] })
      setActiveId(t.id)
    },
    onError: (e: unknown) => toast.error((e as { response?: { data?: { message?: string } } })?.response?.data?.message || 'Talep oluşturulamadı.'),
  })

  const replyM = useMutation({
    mutationFn: async () => (await api.post(`/support/tickets/${activeId}/reply`, { body: reply })).data,
    onSuccess: () => {
      setReply('')
      void qc.invalidateQueries({ queryKey: ['support-ticket', activeId] })
      void qc.invalidateQueries({ queryKey: ['support-tickets'] })
    },
    onError: (e: unknown) => toast.error((e as { response?: { data?: { message?: string } } })?.response?.data?.message || 'Yanıt gönderilemedi.'),
  })

  const closeM = useMutation({
    mutationFn: async () => (await api.post(`/support/tickets/${activeId}/close`)).data,
    onSuccess: () => {
      toast.success('Talep kapatıldı.')
      void qc.invalidateQueries({ queryKey: ['support-ticket', activeId] })
      void qc.invalidateQueries({ queryKey: ['support-tickets'] })
    },
  })

  const tickets = listQ.data?.data ?? []
  const ticket = ticketQ.data

  if (activeId !== null) {
    return (
      <div className="space-y-4">
        <button className="btn-secondary text-sm" onClick={() => setActiveId(null)}>
          <ArrowLeft className="h-4 w-4" /> Tüm talepler
        </button>
        {ticketQ.isLoading || !ticket ? (
          <p className="text-gray-500">Yükleniyor…</p>
        ) : (
          <>
            <div className="card p-4">
              <div className="flex items-center justify-between gap-3">
                <div>
                  <h1 className="text-xl font-bold text-gray-900 dark:text-white">{ticket.subject}</h1>
                  <p className="text-sm text-gray-500">{ticket.number}</p>
                </div>
                <span className={`rounded-full px-3 py-1 text-xs font-medium ${(statusMeta[ticket.status] ?? statusMeta.open).cls}`}>
                  {(statusMeta[ticket.status] ?? statusMeta.open).label}
                </span>
              </div>
            </div>

            <div className="space-y-3">
              {(ticket.messages ?? []).map((m) => (
                <div key={m.id} className={`card p-4 ${m.is_staff ? 'border-l-4 border-l-primary-500' : ''}`}>
                  <div className="flex items-center justify-between text-xs text-gray-500 mb-2">
                    <span className="font-medium text-gray-700 dark:text-gray-300">
                      {m.is_staff ? 'Destek Ekibi' : m.user?.name ?? 'Siz'}
                    </span>
                    <span>{fmt(m.created_at)}</span>
                  </div>
                  <p className="whitespace-pre-wrap text-sm text-gray-800 dark:text-gray-200">{m.body}</p>
                </div>
              ))}
            </div>

            {ticket.status !== 'closed' ? (
              <div className="card p-4 space-y-3">
                <textarea
                  value={reply}
                  onChange={(e) => setReply(e.target.value)}
                  rows={4}
                  placeholder="Yanıtınızı yazın…"
                  className="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                />
                <div className="flex justify-between">
                  <button className="btn-secondary text-sm" onClick={() => closeM.mutate()} disabled={closeM.isPending}>
                    Talebi Kapat
                  </button>
                  <button className="btn-primary text-sm" onClick={() => replyM.mutate()} disabled={replyM.isPending || !reply.trim()}>
                    <Send className="h-4 w-4" /> Gönder
                  </button>
                </div>
              </div>
            ) : (
              <p className="text-center text-sm text-gray-500 py-3">Bu talep kapatılmıştır.</p>
            )}
          </>
        )}
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <LifeBuoy className="h-8 w-8 text-emerald-500" />
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Destek</h1>
          <p className="text-gray-500 dark:text-gray-400 text-sm">Sorularınız için destek talebi oluşturun.</p>
        </div>
        <div className="ml-auto flex gap-2">
          <button className="btn-secondary text-sm" onClick={() => void listQ.refetch()}><RefreshCw className="h-4 w-4" /></button>
          <button className="btn-primary text-sm" onClick={() => setCreating((v) => !v)}><Plus className="h-4 w-4" /> Yeni Talep</button>
        </div>
      </div>

      {creating && (
        <div className="card p-5 space-y-3">
          <input
            value={form.subject}
            onChange={(e) => setForm({ ...form, subject: e.target.value })}
            placeholder="Konu"
            className="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
          />
          <div className="flex gap-3">
            <select value={form.department} onChange={(e) => setForm({ ...form, department: e.target.value })} className="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
              <option value="general">Genel</option>
              <option value="technical">Teknik</option>
              <option value="billing">Faturalama</option>
            </select>
            <select value={form.priority} onChange={(e) => setForm({ ...form, priority: e.target.value })} className="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
              <option value="low">Düşük</option>
              <option value="medium">Orta</option>
              <option value="high">Yüksek</option>
            </select>
          </div>
          <textarea
            value={form.body}
            onChange={(e) => setForm({ ...form, body: e.target.value })}
            rows={5}
            placeholder="Sorununuzu detaylı anlatın…"
            className="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
          />
          <div className="flex justify-end gap-2">
            <button className="btn-secondary text-sm" onClick={() => setCreating(false)}>Vazgeç</button>
            <button className="btn-primary text-sm" disabled={createM.isPending || !form.subject.trim() || !form.body.trim()} onClick={() => createM.mutate()}>
              Talebi Gönder
            </button>
          </div>
        </div>
      )}

      <div className="card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 dark:bg-gray-800/80">
              <tr>
                <th className="text-left px-4 py-2">No</th>
                <th className="text-left px-4 py-2">Konu</th>
                <th className="text-left px-4 py-2">Son Yanıt</th>
                <th className="text-left px-4 py-2">Durum</th>
              </tr>
            </thead>
            <tbody>
              {tickets.map((tk) => (
                <tr key={tk.id} className="border-t border-gray-100 dark:border-gray-800 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50" onClick={() => setActiveId(tk.id)}>
                  <td className="px-4 py-2 font-medium text-gray-900 dark:text-white">{tk.number}</td>
                  <td className="px-4 py-2">{tk.subject}</td>
                  <td className="px-4 py-2">{fmt(tk.last_reply_at)}</td>
                  <td className="px-4 py-2">
                    <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${(statusMeta[tk.status] ?? statusMeta.open).cls}`}>
                      {(statusMeta[tk.status] ?? statusMeta.open).label}
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          {listQ.isLoading && <p className="p-4 text-gray-500">Yükleniyor…</p>}
          {!listQ.isLoading && tickets.length === 0 && <p className="p-6 text-center text-gray-500">Henüz destek talebiniz yok.</p>}
        </div>
      </div>
    </div>
  )
}
