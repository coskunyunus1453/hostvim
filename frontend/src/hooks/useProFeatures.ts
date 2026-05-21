import { useQuery } from '@tanstack/react-query'
import api from '../services/api'

export type ProModuleMeta = {
  label: string
  ui_paths: string[]
  api_route_prefixes: string[]
  enabled: boolean
  requires_pro: boolean
}

export type UiLinksPayload = {
  phpmyadmin_url?: string
  adminer_url?: string
  features?: {
    phpmyadmin_auto_login?: boolean
    license_valid?: boolean
    license_pro?: boolean
    plan?: string | null
    expires_at?: string | null
  }
  modules?: Record<string, ProModuleMeta>
}

export function useProFeatures() {
  const q = useQuery({
    queryKey: ['config-ui-links'],
    queryFn: async () => (await api.get('/config/ui-links')).data as UiLinksPayload,
    staleTime: 60_000,
  })

  const modules = q.data?.modules ?? {}
  const licensePro = Boolean(q.data?.features?.license_pro)
  const licenseValid = Boolean(q.data?.features?.license_valid)

  const isModuleEnabled = (key: string | undefined): boolean => {
    if (!key) return true
    const mod = modules[key]
    if (!mod) return true

    return Boolean(mod.enabled)
  }

  const moduleKeyForPath = (path: string): string | null => {
    const normalized = '/' + path.replace(/^\/+/, '').split('?')[0]
    for (const [key, meta] of Object.entries(modules)) {
      for (const uiPath of meta.ui_paths ?? []) {
        const base = '/' + uiPath.replace(/^\/+/, '')
        if (normalized === base || normalized.startsWith(base + '/')) {
          return key
        }
      }
    }

    return null
  }

  const canAccessPath = (path: string): boolean => {
    const key = moduleKeyForPath(path)
    if (!key) return true

    return isModuleEnabled(key)
  }

  return {
    ...q,
    modules,
    licensePro,
    licenseValid,
    isModuleEnabled,
    moduleKeyForPath,
    canAccessPath,
  }
}
