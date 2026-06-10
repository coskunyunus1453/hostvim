import { useAuthStore } from '../store/authStore'

export function useIsAdmin(): boolean {
  return useAuthStore((s) => s.user?.roles?.some((r) => r.name === 'admin') ?? false)
}
