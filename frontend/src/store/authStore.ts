import { create } from 'zustand'
import { createJSONStorage, persist } from 'zustand/middleware'
import type { User, WhiteLabelUi } from '../types'

interface AuthState {
  user: User | null
  token: string | null
  portal: 'customer' | 'vendor'
  isAuthenticated: boolean
  /** Admin impersonation: geri dönüş için saklanan yönetici oturumu */
  impersonationBackup: {
    user: User
    token: string
    portal: 'customer' | 'vendor'
  } | null
  /** Bayi white-label tema verisi (giriş /auth/me). */
  whiteLabel: WhiteLabelUi | null
  /** Aktif eklenti slug listesi (/auth/me). */
  activePluginSlugs: string[]
  /** Sunucu politikası: admin/vendor operatörlerde 2FA zorunlu (null = henüz /auth/me ile bilinmiyor). */
  enforceAdmin2fa: boolean | null
  setAuth: (
    user: User,
    token: string,
    portal: 'customer' | 'vendor',
    extras?: {
      enforce_admin_2fa?: boolean
      white_label?: WhiteLabelUi | null
      active_plugin_slugs?: string[]
    },
  ) => void
  startImpersonation: (
    customer: User,
    customerToken: string,
    backup: { user: User; token: string; portal: 'customer' | 'vendor' },
  ) => void
  endImpersonation: () => boolean
  setEnforceAdmin2fa: (v: boolean | null) => void
  setWhiteLabelUi: (w: WhiteLabelUi | null) => void
  setActivePluginSlugs: (slugs: string[]) => void
  logout: () => void
  updateUser: (user: Partial<User>) => void
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      user: null,
      token: null,
      portal: 'customer',
      isAuthenticated: false,
      impersonationBackup: null,
      whiteLabel: null,
      activePluginSlugs: [],
      enforceAdmin2fa: null,
      setAuth: (user, token, portal, extras) =>
        set({
          user,
          token,
          portal,
          isAuthenticated: true,
          whiteLabel: extras?.white_label !== undefined ? extras.white_label ?? null : null,
          activePluginSlugs: extras?.active_plugin_slugs ?? [],
          enforceAdmin2fa:
            extras?.enforce_admin_2fa !== undefined ? extras.enforce_admin_2fa : null,
        }),
      startImpersonation: (customer, customerToken, backup) =>
        set({
          impersonationBackup: backup,
          user: customer,
          token: customerToken,
          portal: 'customer',
          isAuthenticated: true,
        }),
      endImpersonation: () => {
        const backup = useAuthStore.getState().impersonationBackup
        if (!backup) return false
        set({
          user: backup.user,
          token: backup.token,
          portal: backup.portal,
          isAuthenticated: true,
          impersonationBackup: null,
        })
        return true
      },
      setEnforceAdmin2fa: (v) => set({ enforceAdmin2fa: v }),
      setWhiteLabelUi: (w) => set({ whiteLabel: w }),
      setActivePluginSlugs: (slugs) => set({ activePluginSlugs: slugs }),
      logout: () =>
        set({
          user: null,
          token: null,
          portal: 'customer',
          isAuthenticated: false,
          impersonationBackup: null,
          whiteLabel: null,
          activePluginSlugs: [],
          enforceAdmin2fa: null,
        }),
      updateUser: (updates) =>
        set((state) => ({
          user: state.user ? { ...state.user, ...updates } : null,
        })),
    }),
    {
      name: 'panelze-auth',
      storage: createJSONStorage(() => sessionStorage),
      partialize: (state) => ({
        token: state.token,
        user: state.user,
        portal: state.portal,
        isAuthenticated: state.isAuthenticated,
        impersonationBackup: state.impersonationBackup,
        whiteLabel: state.whiteLabel,
        activePluginSlugs: state.activePluginSlugs,
        enforceAdmin2fa: state.enforceAdmin2fa,
      }),
    },
  ),
)
