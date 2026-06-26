import { useState } from 'react'
import { useTranslation } from 'react-i18next'
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

const statusKeys: Record<string, string> = {
  open: 'support_tickets.status_open',
  answered: 'support_tickets.status_answered',
  customer_reply: 'support_tickets.status_customer_reply',
  on_hold: 'support_tickets.status_on_hold',
  closed: 'support_tickets.status_closed',
}

const statusCls: Record<string, string> = {
  open: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
  answered: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
  customer_reply: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
  on_hold: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
  closed: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
}

function fmt(iso?: string | null, locale?: string): string {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleString(locale || 'tr-TR')
  } catch {
    return iso
  }
}

export default function SupportPage() {
  const { t, i18n } = useTranslation()
  const locale = i18n.language?.startsWith('en') ? 'en-US' : 'tr-TR'
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
    onSuccess: (ticket) => {
      toast.success(t('support_tickets.created'))
      setCreating(false)
      setForm({ subject: '', body: '', department: 'general', priority: 'medium' })
      void qc.invalidateQueries({ queryKey: ['support-tickets'] })
      setActiveId(ticket.id)
    },
    onError: (e: unknown) => toast.error((e as { response?: { data?: { message?: string } } })?.response?.data?.message || t('support_tickets.create_failed')),
  })

  const replyM = useMutation({
    mutationFn: async () => (await api.post(`/support/tickets/${activeId}/reply`, { body: reply })).data,
    onSuccess: () => {
      setReply('')
      void qc.invalidateQueries({ queryKey: ['support-ticket', activeId] })
      void qc.invalidateQueries({ queryKey: ['support-tickets'] })
    },
    onError: (e: unknown) => toast.error((e as { response?: { data?: { message?: string } } })?.response?.data?.message || t('support_tickets.reply_failed')),
  })

  const closeM = useMutation({
    mutationFn: async () => (await api.post(`/support/tickets/${activeId}/close`)).data,
    onSuccess: () => {
      toast.success(t('support_tickets.closed'))
      void qc.invalidateQueries({ queryKey: ['support-ticket', activeId] })
      void qc.invalidateQueries({ queryKey: ['support-tickets'] })
    },
  })

  const tickets = listQ.data?.data ?? []
  const ticket = ticketQ.data

  const statusLabel = (status: string) => t(statusKeys[status] ?? statusKeys.open)

  if (activeId !== null) {
    return (
      <div className="space-y-4">
        <button className="btn-secondary text-sm" onClick={() => setActiveId(null)}>
          <ArrowLeft className="h-4 w-4" /> {t('support_tickets.back')}
        </button>
        {ticketQ.isLoading || !ticket ? (
          <p className="text-gray-500">{t('support_tickets.loading')}</p>
        ) : (
          <>
            <div className="card p-4">
              <div className="flex items-center justify-between gap-3">
                <div>
                  <h1 className="text-xl font-bold text-gray-900 dark:text-white">{ticket.subject}</h1>
                  <p className="text-sm text-gray-500">{ticket.number}</p>
                </div>
                <span className={`rounded-full px-3 py-1 text-xs font-medium ${statusCls[ticket.status] ?? statusCls.open}`}>
                  {statusLabel(ticket.status)}
                </span>
              </div>
            </div>

            <div className="space-y-3">
              {(ticket.messages ?? []).map((m) => (
                <div key={m.id} className={`card p-4 ${m.is_staff ? 'border-l-4 border-l-primary-500' : ''}`}>
                  <div className="flex items-center justify-between text-xs text-gray-500 mb-2">
                    <span className="font-medium text-gray-700 dark:text-gray-300">
                      {m.is_staff ? t('support_tickets.staff_team') : m.user?.name ?? t('support_tickets.you')}
                    </span>
                    <span>{fmt(m.created_at, locale)}</span>
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
                  placeholder={t('support_tickets.reply_placeholder')}
                  className="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                />
                <div className="flex justify-between">
                  <button className="btn-secondary text-sm" onClick={() => closeM.mutate()} disabled={closeM.isPending}>
                    {t('support_tickets.close')}
                  </button>
                  <button className="btn-primary text-sm" onClick={() => replyM.mutate()} disabled={replyM.isPending || !reply.trim()}>
                    <Send className="h-4 w-4" /> {t('support_tickets.send')}
                  </button>
                </div>
              </div>
            ) : (
              <p className="text-center text-sm text-gray-500 py-3">{t('support_tickets.ticket_closed')}</p>
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
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{t('support_tickets.title')}</h1>
          <p className="text-gray-500 dark:text-gray-400 text-sm">{t('support_tickets.subtitle')}</p>
        </div>
        <div className="ml-auto flex gap-2">
          <button className="btn-secondary text-sm" onClick={() => void listQ.refetch()}><RefreshCw className="h-4 w-4" /></button>
          <button className="btn-primary text-sm" onClick={() => setCreating((v) => !v)}><Plus className="h-4 w-4" /> {t('support_tickets.new_ticket')}</button>
        </div>
      </div>

      {creating && (
        <div className="card p-5 space-y-3">
          <input
            value={form.subject}
            onChange={(e) => setForm({ ...form, subject: e.target.value })}
            placeholder={t('support_tickets.subject')}
            className="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
          />
          <div className="flex gap-3">
            <select value={form.department} onChange={(e) => setForm({ ...form, department: e.target.value })} className="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
              <option value="general">{t('support_tickets.dept_general')}</option>
              <option value="technical">{t('support_tickets.dept_technical')}</option>
              <option value="billing">{t('support_tickets.dept_billing')}</option>
            </select>
            <select value={form.priority} onChange={(e) => setForm({ ...form, priority: e.target.value })} className="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
              <option value="low">{t('support_tickets.prio_low')}</option>
              <option value="medium">{t('support_tickets.prio_medium')}</option>
              <option value="high">{t('support_tickets.prio_high')}</option>
            </select>
          </div>
          <textarea
            value={form.body}
            onChange={(e) => setForm({ ...form, body: e.target.value })}
            rows={5}
            placeholder={t('support_tickets.message')}
            className="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
          />
          <div className="flex justify-end gap-2">
            <button className="btn-secondary text-sm" onClick={() => setCreating(false)}>{t('invoices.cancel')}</button>
            <button className="btn-primary text-sm" disabled={createM.isPending || !form.subject.trim() || !form.body.trim()} onClick={() => createM.mutate()}>
              {t('support_tickets.send')}
            </button>
          </div>
        </div>
      )}

      <div className="card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 dark:bg-gray-800/80">
              <tr>
                <th className="text-left px-4 py-2">#</th>
                <th className="text-left px-4 py-2">{t('support_tickets.subject')}</th>
                <th className="text-left px-4 py-2">{t('support_tickets.last_reply')}</th>
                <th className="text-left px-4 py-2">{t('invoices.col_status')}</th>
              </tr>
            </thead>
            <tbody>
              {tickets.map((tk) => (
                <tr key={tk.id} className="border-t border-gray-100 dark:border-gray-800 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50" onClick={() => setActiveId(tk.id)}>
                  <td className="px-4 py-2 font-medium text-gray-900 dark:text-white">{tk.number}</td>
                  <td className="px-4 py-2">{tk.subject}</td>
                  <td className="px-4 py-2">{fmt(tk.last_reply_at, locale)}</td>
                  <td className="px-4 py-2">
                    <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${statusCls[tk.status] ?? statusCls.open}`}>
                      {statusLabel(tk.status)}
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          {listQ.isLoading && <p className="p-4 text-gray-500">{t('support_tickets.loading')}</p>}
          {!listQ.isLoading && tickets.length === 0 && <p className="p-6 text-center text-gray-500">{t('support_tickets.no_tickets')}</p>}
        </div>
      </div>
    </div>
  )
}
