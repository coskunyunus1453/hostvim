import { create } from 'zustand'
import { createJSONStorage, persist } from 'zustand/middleware'

export type NotificationItem = {
  id: string
  title: string
  message?: string
  path?: string
  level: 'info' | 'success' | 'error'
  read: boolean
  createdAt: string
}

type ServerRow = {
  id: string
  title: string
  message?: string
  path?: string
  level: 'info' | 'success' | 'error'
  created_at?: string
}

const MAX_DISMISSED = 500

function mergeDismissedIds(current: string[], add: string[]): string[] {
  if (!add.length) return current
  return [...new Set([...current, ...add])].slice(-MAX_DISMISSED)
}

type NotificationsState = {
  items: NotificationItem[]
  /** Sunucu feed + kullanıcı tarafından kapatılan kalıcı id listesi */
  dismissedIds: string[]
  add: (n: Omit<NotificationItem, 'id' | 'read' | 'createdAt'>) => void
  mergeFromServer: (rows: ServerRow[]) => void
  markAllRead: () => void
  remove: (id: string) => void
  clear: (extraDismissIds?: string[]) => void
}

export const useNotificationsStore = create<NotificationsState>()(
  persist(
    (set, get) => ({
      items: [],
      dismissedIds: [],
      add: (n) =>
        set((s) => ({
          items: [
            {
              id: `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
              read: false,
              createdAt: new Date().toISOString(),
              ...n,
            },
            ...s.items,
          ].slice(0, 80),
        })),
      mergeFromServer: (rows) =>
        set((s) => {
          const dismissed = new Set(s.dismissedIds)
          const map = new Map(s.items.map((i) => [i.id, i]))
          for (const r of rows) {
            if (dismissed.has(r.id)) continue
            const prev = map.get(r.id)
            map.set(r.id, {
              id: r.id,
              title: r.title,
              message: r.message,
              path: r.path,
              level: r.level,
              createdAt: r.created_at || new Date().toISOString(),
              read: prev?.read ?? false,
            })
          }
          const merged = Array.from(map.values())
            .filter((i) => !dismissed.has(i.id))
            .sort((a, b) => b.createdAt.localeCompare(a.createdAt))
          return { items: merged.slice(0, 120) }
        }),
      markAllRead: () =>
        set((s) => ({ items: s.items.map((i) => ({ ...i, read: true })) })),
      remove: (id) =>
        set((s) => ({
          dismissedIds: mergeDismissedIds(s.dismissedIds, [id]),
          items: s.items.filter((i) => i.id !== id),
        })),
      clear: (extraDismissIds = []) => {
        const ids = [...get().items.map((i) => i.id), ...extraDismissIds]
        set((s) => ({
          dismissedIds: mergeDismissedIds(s.dismissedIds, ids),
          items: [],
        }))
      },
    }),
    {
      name: 'panelze-notifications',
      storage: createJSONStorage(() => localStorage),
      partialize: (state) => ({
        items: state.items,
        dismissedIds: state.dismissedIds,
      }),
    },
  ),
)
