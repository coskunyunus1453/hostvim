import { useQuery } from '@tanstack/react-query'
import api from '../services/api'

export type HostingTarget = {
  key: string
  kind: 'domain' | 'subdomain'
  domain_id: number
  subdomain_id: number | null
  hostname: string
  parent_domain: string
  path_segment?: string | null
  document_root?: string
  php_version?: string
  server_type?: string
  ssl_enabled?: boolean
  ssl_expiry?: string | null
  ssl_status?: string | null
  status?: string
}

export function useHostingTargets() {
  return useQuery({
    queryKey: ['hosting-targets'],
    queryFn: async () => {
      const { data } = await api.get('/hosting/targets')
      return (data?.targets ?? []) as HostingTarget[]
    },
    staleTime: 60_000,
  })
}

export function targetSelectValue(t: HostingTarget): string {
  return t.subdomain_id ? `s:${t.domain_id}:${t.subdomain_id}` : `d:${t.domain_id}`
}

export function parseTargetSelectValue(value: string): { domainId: number; subdomainId?: number } {
  if (value.startsWith('s:')) {
    const parts = value.split(':')
    return { domainId: Number(parts[1]), subdomainId: Number(parts[2]) }
  }
  if (value.startsWith('d:')) {
    return { domainId: Number(value.split(':')[1]) }
  }
  const n = Number(value)
  return { domainId: n }
}
