import { useEffect, useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '../../services/api'
import toast from 'react-hot-toast'
import clsx from 'clsx'
import { AlertTriangle, CheckCircle2, Info, Loader2, Sparkles, Wrench } from 'lucide-react'
import type { DomainQuickRow } from './DomainQuickSettingsModal'

type StackIssue = {
  code: string
  severity: string
  message: string
  fixable: boolean
  fix_id?: string
}

type StackScan = {
  profile: string
  profile_label?: string
  runtime: string
  confidence: string
  signals: string[]
  recommended_variant: string
  recommended_doc_root: string
  recommended_custom_path?: string
  current_doc_root: string
  docroot_aligned?: boolean
  current_server_type: string
  index_path?: string
  guidance?: string
  issues: StackIssue[]
  suggested_php_version?: string
}

type ScanResponse = {
  scan: StackScan
  issues: StackIssue[]
  issue_count: number
  fixable_count: number
  summary?: string
  document_root?: string
  server_type?: string
  php_version?: string
  docroot_aligned?: boolean
}

type Props = {
  domain: DomainQuickRow
  open: boolean
}

function profileLabel(t: (k: string) => string, profile: string): string {
  const key = `domains.auto_profile_${profile.replace(/[^a-z0-9]/gi, '_')}`
  const tr = t(key)
  if (tr !== key) return tr
  const fallback: Record<string, string> = {
    codeigniter3: 'CodeIgniter 3',
    codeigniter4: 'CodeIgniter 4',
    php: 'PHP',
    static: 'Statik HTML',
  }
  return fallback[profile] ?? profile
}

function severityIcon(sev: string) {
  if (sev === 'critical') return <AlertTriangle className="h-4 w-4 text-red-500 shrink-0" />
  if (sev === 'warning') return <AlertTriangle className="h-4 w-4 text-amber-500 shrink-0" />
  return <Info className="h-4 w-4 text-blue-500 shrink-0" />
}

export default function SiteStackAdvisorPanel({ domain, open }: Props) {
  const { t } = useTranslation()
  const qc = useQueryClient()
  const [lastApplied, setLastApplied] = useState<string[]>([])

  const scanQ = useQuery({
    queryKey: ['domain-stack-scan', domain.id],
    enabled: open && domain.id > 0,
    queryFn: async () => (await api.get<ScanResponse>(`/domains/${domain.id}/stack-scan`)).data,
    staleTime: 8_000,
  })

  useEffect(() => {
    if (open) {
      setLastApplied([])
      void scanQ.refetch()
    }
  }, [open, domain.id])

  const fixM = useMutation({
    mutationFn: async () => {
      const { data } = await api.post<{ applied?: string[]; errors?: Record<string, string> }>(
        `/domains/${domain.id}/stack-fix`,
        { fix_ids: [] },
      )
      return data
    },
    onSuccess: (data) => {
      const applied = data.applied ?? []
      setLastApplied(applied)
      void qc.invalidateQueries({ queryKey: ['domains'] })
      void qc.invalidateQueries({ queryKey: ['domain-stack-scan', domain.id] })
      void qc.invalidateQueries({
        queryKey: ['domain-vhost-editor', domain.id, domain.server_type ?? 'nginx'],
      })
      void scanQ.refetch()
      if (Object.keys(data.errors ?? {}).length > 0) {
        toast.error(t('domains.stack_fix_partial'))
      } else {
        toast.success(t('domains.stack_fix_ok', { count: applied.length }))
      }
    },
    onError: (err: unknown) => {
      const ax = err as { response?: { data?: { message?: string } } }
      toast.error(ax.response?.data?.message ?? String(err))
    },
  })

  const scan = scanQ.data?.scan
  const issues = scanQ.data?.issues ?? scan?.issues ?? []
  const fixableCount = scanQ.data?.fixable_count ?? issues.filter((i) => i.fixable).length

  const confidenceText = useMemo(() => {
    const c = scan?.confidence ?? 'low'
    if (c === 'high') return t('domains.auto_confidence_high')
    if (c === 'medium') return t('domains.auto_confidence_medium')
    return t('domains.auto_confidence_low')
  }, [scan?.confidence, t])

  const docrootAligned = useMemo(() => {
    if (!scan) return false
    if (scanQ.data?.docroot_aligned === true || scan.docroot_aligned === true) return true
    const cur = scan.current_doc_root?.replace(/\/+$/, '') ?? ''
    const rec = scan.recommended_doc_root?.replace(/\/+$/, '') ?? ''
    return cur !== '' && rec !== '' && cur === rec
  }, [scan, scanQ.data?.docroot_aligned])

  const docrootMismatchIssue = issues.some((i) => i.code === 'docroot_mismatch')

  return (
    <div className="mb-4 rounded-xl border border-violet-200 bg-gradient-to-br from-violet-50/90 to-primary-50/40 p-4 dark:border-violet-900/50 dark:from-violet-950/30 dark:to-primary-950/20">
      <div className="flex items-start gap-2">
        <Sparkles className="h-5 w-5 text-violet-600 dark:text-violet-400 shrink-0 mt-0.5" />
        <div className="min-w-0 flex-1">
          <p className="text-sm font-semibold text-violet-950 dark:text-violet-100">{t('domains.stack_advisor_title')}</p>
          <p className="mt-1 text-xs text-violet-900/80 dark:text-violet-200/80">{t('domains.stack_advisor_hint')}</p>
        </div>
      </div>

      {scanQ.isLoading && (
        <p className="mt-3 flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
          <Loader2 className="h-3.5 w-3.5 animate-spin" />
          {t('domains.stack_scanning')}
        </p>
      )}

      {scanQ.isError && (
        <p className="mt-3 text-xs text-red-600 dark:text-red-400">{t('domains.stack_scan_failed')}</p>
      )}

      {scan && !scanQ.isLoading && (
        <div className="mt-3 space-y-3 text-xs">
          <div className="grid gap-2 sm:grid-cols-2">
            <div className="rounded-lg border border-white/60 bg-white/70 px-3 py-2 dark:border-gray-700 dark:bg-gray-900/50">
              <span className="text-gray-500">{t('domains.stack_detected_app')}</span>
              <p className="font-semibold text-gray-900 dark:text-white">{scan.profile_label ?? profileLabel(t, scan.profile)}</p>
              <p className="text-gray-500">
                {t('domains.stack_runtime')}: {scan.runtime} · {confidenceText}
              </p>
            </div>
            <div className="rounded-lg border border-white/60 bg-white/70 px-3 py-2 dark:border-gray-700 dark:bg-gray-900/50">
              <span className="text-gray-500">{t('domains.stack_web_server')}</span>
              <p className="font-semibold text-gray-900 dark:text-white uppercase">{scan.current_server_type || domain.server_type}</p>
              <p className="text-gray-500">
                {t('domains.stack_docroot_variant')}: {scan.recommended_variant}
              </p>
            </div>
          </div>

          <div className="rounded-lg border border-white/60 bg-white/60 px-3 py-2 font-mono text-[11px] dark:border-gray-700 dark:bg-gray-900/40">
            <p>
              <span className="text-gray-500">{t('domains.stack_current_root')}: </span>
              <span
                className={clsx(
                  docrootAligned && 'text-emerald-700 dark:text-emerald-400',
                  !docrootAligned && docrootMismatchIssue && 'text-red-600 dark:text-red-400',
                )}
              >
                {scan.current_doc_root}
              </span>
            </p>
            {docrootAligned ? (
              <p className="mt-1 flex items-center gap-1 text-emerald-700 dark:text-emerald-400">
                <CheckCircle2 className="h-3.5 w-3.5 shrink-0" />
                {t('domains.stack_docroot_ok')}
              </p>
            ) : (
              <p className="mt-1">
                <span className="text-gray-500">{t('domains.stack_recommended_root')}: </span>
                <span className="text-amber-700 dark:text-amber-400">{scan.recommended_doc_root}</span>
              </p>
            )}
            {scan.index_path ? (
              <p className="mt-1">
                <span className="text-gray-500">{t('domains.stack_entry')}: </span>
                {scan.index_path}
              </p>
            ) : null}
          </div>

          {scan.guidance ? (
            <p className="rounded-lg border border-violet-200/80 bg-violet-50/60 dark:border-violet-900/40 dark:bg-violet-950/25 px-2.5 py-2 text-xs text-violet-950 dark:text-violet-100">
              {scan.guidance}
            </p>
          ) : null}

          {scan.signals?.length > 0 && (
            <p className="text-gray-600 dark:text-gray-400">
              {t('domains.auto_web_detected_by', { list: scan.signals.slice(0, 10).join(', ') })}
            </p>
          )}

          {issues.length > 0 && (
            <ul className="space-y-2">
              {issues.map((issue) => (
                <li
                  key={issue.code + issue.message}
                  className="flex gap-2 rounded-lg border border-gray-200/80 bg-white/80 px-2.5 py-2 dark:border-gray-700 dark:bg-gray-900/60"
                >
                  {severityIcon(issue.severity)}
                  <div className="min-w-0">
                    <p className="text-gray-800 dark:text-gray-200">{issue.message}</p>
                    {issue.fixable && (
                      <span className="text-emerald-600 dark:text-emerald-400">{t('domains.stack_auto_fixable')}</span>
                    )}
                  </div>
                </li>
              ))}
            </ul>
          )}

          {issues.length === 0 && docrootAligned && (
            <p className="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400">
              <CheckCircle2 className="h-4 w-4" />
              {t('domains.stack_all_ok')}
            </p>
          )}
        </div>
      )}

      <div className="mt-3 flex flex-wrap gap-2">
        <button
          type="button"
          className="btn-primary inline-flex items-center gap-2 text-sm"
          disabled={fixM.isPending || scanQ.isLoading || fixableCount === 0}
          onClick={() => fixM.mutate()}
        >
          {fixM.isPending ? <Loader2 className="h-4 w-4 animate-spin" /> : <Wrench className="h-4 w-4" />}
          {t('domains.stack_fix_all')}
        </button>
        <button
          type="button"
          className="btn-secondary text-sm"
          disabled={scanQ.isFetching}
          onClick={() => void scanQ.refetch()}
        >
          {t('domains.stack_rescan')}
        </button>
      </div>

      {lastApplied.length > 0 && (
        <p className="mt-2 text-[11px] text-gray-600 dark:text-gray-400">
          {t('domains.stack_applied', { list: lastApplied.join(', ') })}
        </p>
      )}
    </div>
  )
}
