import { useEffect, useMemo, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  BarChart3,
  Bot,
  CheckCircle2,
  Loader2,
  MessageSquare,
  Plus,
  Send,
  Settings2,
  Sparkles,
  Trash2,
  Wrench,
  XCircle,
  Zap,
} from 'lucide-react'
import toast from 'react-hot-toast'
import clsx from 'clsx'
import api from '../services/api'
import { useDomainsList } from '../hooks/useDomains'
import {
  Bar,
  BarChart,
  CartesianGrid,
  Legend,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'

type TabId = 'chat' | 'settings' | 'usage' | 'insights'

type ProviderRow = {
  provider: string
  label: string
  models: string[]
  default_model: string
  enabled: boolean
  is_default: boolean
  model: string
  api_key_set: boolean
  api_key_hint?: string | null
  last_test_at?: string | null
  last_test_ok?: boolean | null
  last_test_message?: string | null
}

type ChatMessage = {
  id: number
  role: 'user' | 'assistant' | 'system'
  content: string
  meta?: { actions?: { fixes?: FixAction[]; tips?: string[] } }
  provider?: string
  model?: string
  created_at?: string
}

type FixAction = {
  domain_id: number
  path: string
  content: string
  summary?: string
}

type SessionRow = {
  id: number
  title: string
  context_mode?: string
  messages_count?: number
  domain?: { id: number; name: string }
  updated_at?: string
}

const PROVIDER_ICONS: Record<string, string> = {
  openai: '🤖',
  gemini: '✨',
  anthropic: '🧠',
}

function stripActionsBlock(text: string): string {
  return text.replace(/```hostvim-actions[\s\S]*?```/gi, '').trim()
}

function MarkdownLite({ text }: { text: string }) {
  const clean = stripActionsBlock(text)
  const parts = clean.split(/(```[\s\S]*?```|`[^`]+`|\*\*[^*]+\*\*)/g)
  return (
    <div className="space-y-2 text-sm leading-relaxed whitespace-pre-wrap break-words">
      {parts.map((part, i) => {
        if (part.startsWith('```')) {
          const inner = part.replace(/^```\w*\n?/, '').replace(/```$/, '')
          return (
            <pre key={i} className="overflow-x-auto rounded-lg bg-black/80 p-3 text-xs text-green-200">
              {inner}
            </pre>
          )
        }
        if (part.startsWith('**') && part.endsWith('**')) {
          return (
            <strong key={i} className="font-semibold">
              {part.slice(2, -2)}
            </strong>
          )
        }
        if (part.startsWith('`') && part.endsWith('`')) {
          return (
            <code key={i} className="rounded bg-black/10 px-1 py-0.5 font-mono text-xs dark:bg-white/10">
              {part.slice(1, -1)}
            </code>
          )
        }
        return <span key={i}>{part}</span>
      })}
    </div>
  )
}

export default function AiAdvisorPage() {
  const { t } = useTranslation()
  const qc = useQueryClient()
  const [tab, setTab] = useState<TabId>('chat')
  const [sessionId, setSessionId] = useState<number | null>(null)
  const [input, setInput] = useState('')
  const [domainId, setDomainId] = useState<number | ''>('')
  const [contextMode, setContextMode] = useState<'server' | 'site' | 'file'>('server')
  const [filePath, setFilePath] = useState('')
  const [providerDraft, setProviderDraft] = useState<Record<string, { api_key: string; model: string; enabled: boolean; is_default: boolean }>>({})
  const chatEndRef = useRef<HTMLDivElement>(null)

  const domainsQ = useDomainsList()

  const settingsQ = useQuery({
    queryKey: ['ai-assistant-settings'],
    queryFn: async () => (await api.get('/ai-assistant/settings')).data as { providers: ProviderRow[]; has_active_provider: boolean },
  })

  const sessionsQ = useQuery({
    queryKey: ['ai-assistant-sessions'],
    queryFn: async () => (await api.get('/ai-assistant/sessions')).data as { sessions: SessionRow[] },
  })

  const messagesQ = useQuery({
    queryKey: ['ai-assistant-messages', sessionId],
    enabled: sessionId !== null,
    queryFn: async () =>
      (await api.get(`/ai-assistant/sessions/${sessionId}/messages`)).data as {
        messages: ChatMessage[]
        session: SessionRow
      },
  })

  const usageQ = useQuery({
    queryKey: ['ai-assistant-usage'],
    queryFn: async () => (await api.get('/ai-assistant/usage', { params: { days: 30 } })).data,
    enabled: tab === 'usage',
  })

  useEffect(() => {
    const rows = settingsQ.data?.providers ?? []
    if (rows.length === 0) return
    setProviderDraft((prev) => {
      const next = { ...prev }
      for (const p of rows) {
        if (!next[p.provider]) {
          next[p.provider] = {
            api_key: '',
            model: p.model || p.default_model,
            enabled: p.enabled,
            is_default: p.is_default,
          }
        } else {
          next[p.provider] = {
            ...next[p.provider],
            model: p.model || p.default_model,
            enabled: p.enabled,
            is_default: p.is_default,
          }
        }
      }
      return next
    })
  }, [settingsQ.data?.providers])

  useEffect(() => {
    chatEndRef.current?.scrollIntoView({ behavior: 'smooth' })
  }, [messagesQ.data?.messages, tab])

  const saveSettingsM = useMutation({
    mutationFn: async () => {
      const providers = (settingsQ.data?.providers ?? []).map((p) => ({
        provider: p.provider,
        api_key: providerDraft[p.provider]?.api_key?.trim() || undefined,
        model: providerDraft[p.provider]?.model || p.default_model,
        enabled: providerDraft[p.provider]?.enabled ?? false,
        is_default: providerDraft[p.provider]?.is_default ?? false,
      }))
      return api.put('/ai-assistant/settings', { providers })
    },
    onSuccess: () => {
      toast.success(t('ai.settings_saved'))
      qc.invalidateQueries({ queryKey: ['ai-assistant-settings'] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const testM = useMutation({
    mutationFn: async (provider: string) => (await api.post('/ai-assistant/settings/test', { provider })).data,
    onSuccess: (data: { ok?: boolean; message?: string }) => {
      if (data.ok) toast.success(data.message ?? t('ai.test_ok'))
      else toast.error(data.message ?? t('ai.test_fail'))
      qc.invalidateQueries({ queryKey: ['ai-assistant-settings'] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const chatM = useMutation({
    mutationFn: async (message: string) =>
      (
        await api.post('/ai-assistant/chat', {
          message,
          session_id: sessionId,
          domain_id: domainId !== '' ? domainId : undefined,
          context_mode: contextMode,
          file_path: contextMode === 'file' && filePath.trim() ? filePath.trim() : undefined,
        })
      ).data as {
        session: SessionRow
        assistant_message: ChatMessage
      },
    onSuccess: (data) => {
      setSessionId(data.session.id)
      setInput('')
      qc.invalidateQueries({ queryKey: ['ai-assistant-sessions'] })
      qc.invalidateQueries({ queryKey: ['ai-assistant-messages', data.session.id] })
      qc.invalidateQueries({ queryKey: ['ai-assistant-usage'] })
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const applyFixM = useMutation({
    mutationFn: async (fix: FixAction) =>
      api.post('/ai-assistant/apply-fix', {
        domain_id: fix.domain_id,
        path: fix.path,
        content: fix.content,
      }),
    onSuccess: () => toast.success(t('ai.fix_applied')),
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const deleteSessionM = useMutation({
    mutationFn: async (id: number) => api.delete(`/ai-assistant/sessions/${id}`),
    onSuccess: (_, id) => {
      if (sessionId === id) setSessionId(null)
      qc.invalidateQueries({ queryKey: ['ai-assistant-sessions'] })
      toast.success(t('ai.session_deleted'))
    },
  })

  const quickPrompts = useMemo(
    () => [
      t('ai.prompt_server_health'),
      t('ai.prompt_security'),
      t('ai.prompt_slow_site'),
      t('ai.prompt_analyze_logs'),
    ],
    [t],
  )

  const usageChart = useMemo(() => {
    const daily = (usageQ.data?.daily ?? []) as Array<{ date: string; requests: number }>
    return daily.map((d) => ({ ...d, label: d.date.slice(5) }))
  }, [usageQ.data?.daily])

  const providerUsage = (usageQ.data?.by_provider ?? []) as Array<{
    provider: string
    requests: number
    prompt_tokens: number
    completion_tokens: number
  }>

  const hasProvider = settingsQ.data?.has_active_provider

  const tabs = [
    { id: 'chat' as const, icon: MessageSquare, label: t('ai.tab_chat') },
    { id: 'settings' as const, icon: Settings2, label: t('ai.tab_settings') },
    { id: 'usage' as const, icon: BarChart3, label: t('ai.tab_usage') },
    { id: 'insights' as const, icon: Zap, label: t('ai.tab_insights') },
  ]

  return (
    <div className="flex h-[calc(100vh-7rem)] min-h-[560px] flex-col gap-4 lg:h-[calc(100vh-5.5rem)]">
      <div className="flex flex-wrap items-start justify-between gap-4 shrink-0">
        <div className="flex items-center gap-3">
          <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-fuchsia-500/20 to-violet-600/20">
            <Sparkles className="h-7 w-7 text-fuchsia-600 dark:text-fuchsia-400" />
          </div>
          <div>
            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{t('nav.ai_advisor')}</h1>
            <p className="text-sm text-gray-500 dark:text-gray-400">{t('ai.subtitle_new')}</p>
          </div>
        </div>
        <div className="flex flex-wrap gap-1 rounded-xl border border-gray-200 bg-white p-1 dark:border-gray-700 dark:bg-gray-900/60">
          {tabs.map(({ id, icon: Icon, label }) => (
            <button
              key={id}
              type="button"
              onClick={() => setTab(id)}
              className={clsx(
                'flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                tab === id
                  ? 'bg-fuchsia-600 text-white shadow-sm'
                  : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800',
              )}
            >
              <Icon className="h-4 w-4" />
              <span className="hidden sm:inline">{label}</span>
            </button>
          ))}
        </div>
      </div>

      {tab === 'chat' && (
        <div className="grid min-h-0 flex-1 gap-4 lg:grid-cols-[240px_1fr]">
          <aside className="flex flex-col rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900/60 overflow-hidden">
            <div className="border-b border-gray-200 p-3 dark:border-gray-700">
              <button
                type="button"
                className="btn-primary flex w-full items-center justify-center gap-2 text-sm"
                onClick={() => {
                  setSessionId(null)
                  qc.removeQueries({ queryKey: ['ai-assistant-messages'] })
                }}
              >
                <Plus className="h-4 w-4" />
                {t('ai.new_chat')}
              </button>
            </div>
            <div className="flex-1 overflow-y-auto p-2 space-y-1">
              {(sessionsQ.data?.sessions ?? []).map((s) => (
                <div
                  key={s.id}
                  className={clsx(
                    'group flex items-start gap-2 rounded-xl px-3 py-2 cursor-pointer transition-colors',
                    sessionId === s.id
                      ? 'bg-fuchsia-100 dark:bg-fuchsia-950/40'
                      : 'hover:bg-gray-100 dark:hover:bg-gray-800',
                  )}
                  onClick={() => setSessionId(s.id)}
                >
                  <Bot className="mt-0.5 h-4 w-4 shrink-0 text-fuchsia-500" />
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-medium text-gray-900 dark:text-white">{s.title}</p>
                    <p className="truncate text-[11px] text-gray-500">
                      {s.domain?.name ?? t('ai.context_server')} · {s.messages_count ?? 0} msg
                    </p>
                  </div>
                  <button
                    type="button"
                    className="opacity-0 group-hover:opacity-100 p-1 text-gray-400 hover:text-red-500"
                    onClick={(e) => {
                      e.stopPropagation()
                      if (window.confirm(t('ai.session_delete_confirm'))) deleteSessionM.mutate(s.id)
                    }}
                  >
                    <Trash2 className="h-3.5 w-3.5" />
                  </button>
                </div>
              ))}
            </div>
          </aside>

          <div className="flex min-h-0 flex-col rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900/60">
            {!hasProvider && (
              <div className="border-b border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200">
                {t('ai.no_provider_hint')}{' '}
                <button type="button" className="font-semibold underline" onClick={() => setTab('settings')}>
                  {t('ai.tab_settings')}
                </button>
              </div>
            )}

            <div className="flex flex-wrap gap-2 border-b border-gray-200 p-3 dark:border-gray-700">
              <select
                className="input min-w-[140px] text-sm"
                value={contextMode}
                onChange={(e) => setContextMode(e.target.value as 'server' | 'site' | 'file')}
              >
                <option value="server">{t('ai.context_server')}</option>
                <option value="site">{t('ai.context_site')}</option>
                <option value="file">{t('ai.context_file')}</option>
              </select>
              {(contextMode === 'site' || contextMode === 'file') && (
                <select
                  className="input min-w-[160px] text-sm"
                  value={domainId}
                  onChange={(e) => setDomainId(e.target.value ? Number(e.target.value) : '')}
                >
                  <option value="">{t('domains.name')}</option>
                  {(domainsQ.data ?? []).map((d) => (
                    <option key={d.id} value={d.id}>
                      {d.name}
                    </option>
                  ))}
                </select>
              )}
              {contextMode === 'file' && (
                <input
                  className="input min-w-[180px] flex-1 font-mono text-sm"
                  placeholder="public_html/index.php"
                  value={filePath}
                  onChange={(e) => setFilePath(e.target.value)}
                />
              )}
            </div>

            <div className="flex-1 overflow-y-auto p-4 space-y-4">
              {!sessionId && (messagesQ.data?.messages ?? []).length === 0 && (
                <div className="flex h-full flex-col items-center justify-center text-center px-4">
                  <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-fuchsia-100 dark:bg-fuchsia-950/40">
                    <Sparkles className="h-8 w-8 text-fuchsia-600" />
                  </div>
                  <h2 className="text-lg font-semibold text-gray-900 dark:text-white">{t('ai.welcome_title')}</h2>
                  <p className="mt-2 max-w-md text-sm text-gray-500">{t('ai.welcome_desc')}</p>
                  <div className="mt-6 flex flex-wrap justify-center gap-2">
                    {quickPrompts.map((p) => (
                      <button
                        key={p}
                        type="button"
                        className="rounded-full border border-fuchsia-200 bg-fuchsia-50 px-3 py-1.5 text-xs text-fuchsia-800 hover:bg-fuchsia-100 dark:border-fuchsia-800 dark:bg-fuchsia-950/30 dark:text-fuchsia-200"
                        onClick={() => setInput(p)}
                      >
                        {p}
                      </button>
                    ))}
                  </div>
                </div>
              )}

              {(messagesQ.data?.messages ?? []).map((m) => {
                const fixes = m.meta?.actions?.fixes ?? []
                const isUser = m.role === 'user'
                return (
                  <div key={m.id} className={clsx('flex', isUser ? 'justify-end' : 'justify-start')}>
                    <div
                      className={clsx(
                        'max-w-[92%] rounded-2xl px-4 py-3 shadow-sm sm:max-w-[80%]',
                        isUser
                          ? 'bg-fuchsia-600 text-white'
                          : 'border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/80',
                      )}
                    >
                      {!isUser && (
                        <p className="mb-1 text-[10px] font-semibold uppercase tracking-wide text-fuchsia-600 dark:text-fuchsia-400">
                          {m.provider ?? 'AI'} · {m.model ?? ''}
                        </p>
                      )}
                      {isUser ? (
                        <p className="text-sm whitespace-pre-wrap">{m.content}</p>
                      ) : (
                        <MarkdownLite text={m.content} />
                      )}
                      {fixes.length > 0 && (
                        <div className="mt-3 space-y-2 border-t border-gray-200 pt-3 dark:border-gray-600">
                          <p className="text-xs font-semibold text-gray-700 dark:text-gray-300">{t('ai.suggested_fixes')}</p>
                          {fixes.map((fix, idx) => (
                            <div
                              key={idx}
                              className="rounded-lg border border-emerald-200 bg-emerald-50/80 p-3 dark:border-emerald-900/50 dark:bg-emerald-950/20"
                            >
                              <p className="text-xs font-medium text-emerald-900 dark:text-emerald-200">
                                {fix.summary ?? fix.path}
                              </p>
                              <p className="mt-1 font-mono text-[11px] text-gray-600 dark:text-gray-400">{fix.path}</p>
                              <button
                                type="button"
                                className="mt-2 inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                                disabled={applyFixM.isPending}
                                onClick={() => {
                                  if (!window.confirm(t('ai.fix_confirm', { path: fix.path }))) return
                                  applyFixM.mutate(fix)
                                }}
                              >
                                <Wrench className="h-3.5 w-3.5" />
                                {t('ai.apply_fix')}
                              </button>
                            </div>
                          ))}
                        </div>
                      )}
                    </div>
                  </div>
                )
              })}
              {chatM.isPending && (
                <div className="flex items-center gap-2 text-sm text-gray-500">
                  <Loader2 className="h-4 w-4 animate-spin" />
                  {t('ai.thinking')}
                </div>
              )}
              <div ref={chatEndRef} />
            </div>

            <div className="border-t border-gray-200 p-3 dark:border-gray-700">
              <form
                className="flex gap-2"
                onSubmit={(e) => {
                  e.preventDefault()
                  if (!input.trim() || chatM.isPending) return
                  if (!hasProvider) {
                    toast.error(t('ai.no_provider_hint'))
                    setTab('settings')
                    return
                  }
                  chatM.mutate(input.trim())
                }}
              >
                <textarea
                  className="input min-h-[48px] max-h-32 flex-1 resize-y text-sm"
                  rows={1}
                  placeholder={t('ai.input_placeholder')}
                  value={input}
                  onChange={(e) => setInput(e.target.value)}
                  onKeyDown={(e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                      e.preventDefault()
                      e.currentTarget.form?.requestSubmit()
                    }
                  }}
                />
                <button
                  type="submit"
                  className="btn-primary flex shrink-0 items-center gap-2 px-4"
                  disabled={chatM.isPending || !input.trim()}
                >
                  {chatM.isPending ? <Loader2 className="h-4 w-4 animate-spin" /> : <Send className="h-4 w-4" />}
                  <span className="hidden sm:inline">{t('ai.send')}</span>
                </button>
              </form>
            </div>
          </div>
        </div>
      )}

      {tab === 'settings' && (
        <div className="overflow-y-auto space-y-4 pb-4">
          <p className="text-sm text-gray-600 dark:text-gray-400">{t('ai.settings_intro')}</p>
          <div className="grid gap-4 lg:grid-cols-3">
            {(settingsQ.data?.providers ?? []).map((p) => {
              const draft = providerDraft[p.provider] ?? {
                api_key: '',
                model: p.default_model,
                enabled: false,
                is_default: false,
              }
              return (
                <div
                  key={p.provider}
                  className="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900/60"
                >
                  <div className="flex items-center justify-between gap-2">
                    <h3 className="text-base font-semibold text-gray-900 dark:text-white">
                      {PROVIDER_ICONS[p.provider]} {p.label}
                    </h3>
                    {p.last_test_ok === true && <CheckCircle2 className="h-5 w-5 text-emerald-500" />}
                    {p.last_test_ok === false && <XCircle className="h-5 w-5 text-red-500" />}
                  </div>
                  <div className="mt-4 space-y-3">
                    <div>
                      <label className="label">{t('ai.api_key')}</label>
                      <input
                        type="password"
                        className="input w-full font-mono text-sm"
                        placeholder={p.api_key_set ? p.api_key_hint ?? '••••' : 'sk-...'}
                        value={draft.api_key}
                        onChange={(e) =>
                          setProviderDraft((s) => ({
                            ...s,
                            [p.provider]: { ...draft, api_key: e.target.value },
                          }))
                        }
                      />
                      {p.api_key_set && !draft.api_key && (
                        <p className="mt-1 text-[11px] text-gray-500">{t('ai.api_key_keep')}</p>
                      )}
                    </div>
                    <div>
                      <label className="label">{t('ai.model')}</label>
                      <select
                        className="input w-full text-sm"
                        value={draft.model}
                        onChange={(e) =>
                          setProviderDraft((s) => ({
                            ...s,
                            [p.provider]: { ...draft, model: e.target.value },
                          }))
                        }
                      >
                        {p.models.map((m) => (
                          <option key={m} value={m}>
                            {m}
                          </option>
                        ))}
                      </select>
                    </div>
                    <label className="flex items-center gap-2 text-sm">
                      <input
                        type="checkbox"
                        checked={draft.enabled}
                        onChange={(e) =>
                          setProviderDraft((s) => ({
                            ...s,
                            [p.provider]: { ...draft, enabled: e.target.checked },
                          }))
                        }
                      />
                      {t('ai.enabled')}
                    </label>
                    <label className="flex items-center gap-2 text-sm">
                      <input
                        type="radio"
                        name="default_provider"
                        checked={draft.is_default}
                        onChange={() =>
                          setProviderDraft((s) => {
                            const next = { ...s }
                            for (const key of Object.keys(next)) {
                              next[key] = { ...next[key], is_default: key === p.provider }
                            }
                            return next
                          })
                        }
                      />
                      {t('ai.default_provider')}
                    </label>
                    {p.last_test_message && (
                      <p className={clsx('text-xs', p.last_test_ok ? 'text-emerald-600' : 'text-red-600')}>
                        {p.last_test_message}
                      </p>
                    )}
                    <button
                      type="button"
                      className="btn-secondary w-full text-sm"
                      disabled={testM.isPending || (!p.api_key_set && !draft.api_key.trim())}
                      onClick={() => {
                        if (draft.api_key.trim()) {
                          saveSettingsM.mutate(undefined, {
                            onSuccess: () => testM.mutate(p.provider),
                          })
                        } else {
                          testM.mutate(p.provider)
                        }
                      }}
                    >
                      {testM.isPending ? <Loader2 className="mx-auto h-4 w-4 animate-spin" /> : t('ai.test_connection')}
                    </button>
                  </div>
                </div>
              )
            })}
          </div>
          <div className="flex justify-end">
            <button type="button" className="btn-primary" disabled={saveSettingsM.isPending} onClick={() => saveSettingsM.mutate()}>
              {saveSettingsM.isPending ? <Loader2 className="h-4 w-4 animate-spin" /> : t('ai.save_settings')}
            </button>
          </div>
        </div>
      )}

      {tab === 'usage' && (
        <div className="overflow-y-auto space-y-6 pb-4">
          <div className="grid gap-4 sm:grid-cols-3">
            <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900/60">
              <p className="text-xs text-gray-500">{t('ai.usage_requests')}</p>
              <p className="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{usageQ.data?.total_requests ?? 0}</p>
              <p className="text-xs text-gray-400">{t('ai.usage_last_30')}</p>
            </div>
            <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900/60">
              <p className="text-xs text-gray-500">{t('ai.usage_tokens')}</p>
              <p className="mt-1 text-3xl font-bold text-fuchsia-600">{usageQ.data?.total_tokens ?? 0}</p>
            </div>
            <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900/60">
              <p className="text-xs text-gray-500">{t('ai.usage_prompt')}</p>
              <p className="mt-1 text-lg font-semibold">{usageQ.data?.prompt_tokens ?? 0}</p>
              <p className="text-xs text-gray-400">{t('ai.usage_completion')}: {usageQ.data?.completion_tokens ?? 0}</p>
            </div>
          </div>
          <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900/60">
            <h3 className="mb-4 text-sm font-semibold">{t('ai.usage_daily')}</h3>
            <div style={{ height: 260 }}>
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={usageChart}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="label" tick={{ fontSize: 11 }} />
                  <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                  <Tooltip />
                  <Legend />
                  <Bar dataKey="requests" name={t('ai.usage_requests')} fill="#d946ef" radius={[6, 6, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </div>
          {providerUsage.length > 0 && (
            <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900/60">
              <h3 className="mb-3 text-sm font-semibold">{t('ai.usage_by_provider')}</h3>
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="text-left text-xs text-gray-500">
                      <th className="pb-2">{t('ai.provider')}</th>
                      <th className="pb-2">{t('ai.usage_requests')}</th>
                      <th className="pb-2">{t('ai.usage_tokens')}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {providerUsage.map((row) => (
                      <tr key={row.provider} className="border-t border-gray-100 dark:border-gray-800">
                        <td className="py-2 font-medium">{row.provider}</td>
                        <td className="py-2">{row.requests}</td>
                        <td className="py-2">{row.prompt_tokens + row.completion_tokens}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}
        </div>
      )}

      {tab === 'insights' && (
        <div className="overflow-y-auto pb-4">
          <p className="mb-4 text-sm text-gray-500">{t('ai.insights_hint')}</p>
          <LegacyInsights domainId={domainId} setDomainId={setDomainId} domains={domainsQ.data ?? []} />
        </div>
      )}
    </div>
  )
}

function LegacyInsights({
  domainId,
  setDomainId,
  domains,
}: {
  domainId: number | ''
  setDomainId: (v: number | '') => void
  domains: Array<{ id: number; name: string }>
}) {
  const { t } = useTranslation()
  const cronBackupQ = useQuery({
    queryKey: ['ai', 'cron-backup'],
    queryFn: async () => (await api.get('/ai/cron-backup')).data as { suggestions: string[] },
  })
  const monitoringQ = useQuery({
    queryKey: ['ai', 'monitoring'],
    queryFn: async () => (await api.get('/ai/monitoring')).data as { alerts: string[] },
  })
  const slowSiteQ = useQuery({
    queryKey: ['ai', 'slow-site', domainId],
    enabled: domainId !== '',
    queryFn: async () => (await api.get(`/domains/${domainId}/ai/slow-site`)).data as { suggestions: string[] },
  })

  const list = (rows?: string[]) => (
    <ul className="space-y-1 text-sm text-gray-700 dark:text-gray-300">
      {(rows ?? []).map((r, i) => (
        <li key={i}>- {r}</li>
      ))}
    </ul>
  )

  return (
    <div className="space-y-4">
      <select className="input max-w-xs" value={domainId} onChange={(e) => setDomainId(e.target.value ? Number(e.target.value) : '')}>
        <option value="">{t('domains.name')}</option>
        {domains.map((d) => (
          <option key={d.id} value={d.id}>
            {d.name}
          </option>
        ))}
      </select>
      <div className="grid gap-4 md:grid-cols-2">
        <div className="card p-5">
          <h3 className="mb-2 text-sm font-semibold">{t('ai.cron_backup')}</h3>
          {list(cronBackupQ.data?.suggestions)}
        </div>
        <div className="card p-5">
          <h3 className="mb-2 text-sm font-semibold">{t('ai.monitoring')}</h3>
          {list(monitoringQ.data?.alerts)}
        </div>
        <div className="card p-5 md:col-span-2">
          <h3 className="mb-2 text-sm font-semibold">{t('ai.slow_site')}</h3>
          {domainId !== '' ? list(slowSiteQ.data?.suggestions) : <p className="text-sm text-gray-500">{t('ai.select_domain_for_deploy')}</p>}
        </div>
      </div>
    </div>
  )
}
