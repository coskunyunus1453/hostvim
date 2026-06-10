import type { QueryClient } from '@tanstack/react-query'
import api from '../services/api'
import type { DashboardData } from '../types'

export function prefetchDashboard(queryClient: QueryClient): void {
  void queryClient.prefetchQuery({
    queryKey: ['dashboard'],
    queryFn: async () => (await api.get('/dashboard')).data.dashboard as DashboardData,
    staleTime: 15_000,
  })
}
