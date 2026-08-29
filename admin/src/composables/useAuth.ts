import { storeToRefs } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import type { LoginPayload } from '@/schemas/auth'

export function useAuth() {
  const store = useAuthStore()
  const { admin, bootstrapped, isAuthenticated } = storeToRefs(store)

  return {
    admin,
    bootstrapped,
    isAuthenticated,
    can: (permission: string) => store.can(permission),
    login: (payload: LoginPayload) => store.login(payload),
    logout: () => store.logout(),
    bootstrap: () => store.bootstrap(),
  }
}
