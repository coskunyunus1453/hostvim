import { useAuthStore } from '../store/authStore'

/** Sidebar ve rota görünürlüğü — /plugins/store yerine /auth/me slug listesi. */
export function useActivePluginSlugs(): string[] {
  return useAuthStore((s) => s.activePluginSlugs)
}
