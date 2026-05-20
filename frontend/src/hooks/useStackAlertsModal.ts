import { useEffect, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import api from '../services/api'
import { useAuthStore } from '../store/authStore'

/** Saatlik tarama uyarıları varsa modalı otomatik açar (oturum başına bir kez). */
export function useStackAlertsModal() {
  const token = useAuthStore((s) => s.token)
  const [open, setOpen] = useState(false)
  const [checked, setChecked] = useState(false)

  const alertsQ = useQuery({
    queryKey: ['stack-alerts', 'bootstrap'],
    enabled: !!token && !checked,
    queryFn: async () => (await api.get<{ items: unknown[] }>('/domains/stack-alerts')).data.items,
    staleTime: 120_000,
  })

  useEffect(() => {
    if (!token || checked || alertsQ.isLoading) return
    if (alertsQ.isSuccess) {
      setChecked(true)
      const items = alertsQ.data ?? []
      if (items.length > 0) {
        const key = 'hostvim_stack_alerts_shown'
        const last = sessionStorage.getItem(key)
        const now = String(Date.now())
        if (!last || Date.now() - Number(last) > 3600_000) {
          sessionStorage.setItem(key, now)
          setOpen(true)
        }
      }
    }
  }, [token, checked, alertsQ.isLoading, alertsQ.isSuccess, alertsQ.data])

  return { open, setOpen }
}
