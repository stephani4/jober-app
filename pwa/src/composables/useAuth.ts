import { storeToRefs } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import type { LoginPayload, RegisterPayload } from '@/schemas/auth'

export function useAuth() {
  const store = useAuthStore()
  const { user, bootstrapped, isAuthenticated } = storeToRefs(store)

  return {
    user,
    bootstrapped,
    isAuthenticated,
    login: (payload: LoginPayload) => store.login(payload),
    register: (payload: RegisterPayload) => store.register(payload),
    logout: () => store.logout(),
    bootstrap: () => store.bootstrap(),
  }
}
