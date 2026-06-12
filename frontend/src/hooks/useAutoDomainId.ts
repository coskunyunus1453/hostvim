import { useCallback, useEffect, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { useDomainsList } from './useDomains'

type UseAutoDomainIdOptions = {
  /** URL parametresi adı; `false` ise URL ile senkron yok */
  param?: string | false
}

/**
 * Domain listesinden varsayılan seçim: URL'de domain varsa onu kullanır,
 * yoksa API sırasındaki ilk siteyi otomatik seçer.
 */
export function useAutoDomainId(opts: UseAutoDomainIdOptions = {}) {
  const paramName = opts.param === false ? null : (opts.param ?? 'domain')
  const [searchParams, setSearchParams] = useSearchParams()
  const domainsQ = useDomainsList()
  const domains = domainsQ.data ?? []

  const [domainId, setDomainIdState] = useState<number | ''>(() => {
    if (!paramName) return ''
    const raw = searchParams.get(paramName)
    const n = raw ? Number(raw) : NaN
    return Number.isFinite(n) && n > 0 ? n : ''
  })

  const setDomainId = useCallback(
    (id: number | '') => {
      setDomainIdState(id)
      if (!paramName) return
      setSearchParams(
        (prev) => {
          const next = new URLSearchParams(prev)
          if (id === '') next.delete(paramName)
          else next.set(paramName, String(id))
          return next
        },
        { replace: true },
      )
    },
    [paramName, setSearchParams],
  )

  useEffect(() => {
    if (!paramName || domains.length === 0) return
    const raw = searchParams.get(paramName)
    if (!raw) return
    const id = Number(raw)
    if (Number.isFinite(id) && domains.some((d) => d.id === id)) {
      setDomainIdState(id)
    }
  }, [searchParams, domains, paramName])

  useEffect(() => {
    if (domains.length === 0) return
    if (domainId !== '' && domains.some((d) => d.id === domainId)) return
    const first = domains[0]
    if (!first) return
    if (paramName) {
      setDomainId(first.id)
    } else {
      setDomainIdState(first.id)
    }
  }, [domains, domainId, paramName, setDomainId])

  return { domainId, setDomainId, domains, domainsQ }
}
