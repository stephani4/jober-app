import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import type { LoginPayload, RegisterPayload } from '@/schemas/auth'
import type { UserRole } from '@/schemas/user'

export function useAuth() {
  const store = useAuthStore()
  const { user, bootstrapped, isAuthenticated } = storeToRefs(store)

  const role = computed<UserRole | null>(() => user.value?.role ?? null)

  /**
   * Проверяет роль пользователя.
   *
   * @param roles Список допустимых ролей; без значения или пустой — доступ есть у всех.
   */
  function hasRole(roles?: UserRole[]): boolean {
    if (!roles || roles.length === 0) {
      return true
    }

    return role.value !== null && roles.includes(role.value)
  }

  return {
    user,
    role,
    bootstrapped,
    isAuthenticated,
    hasRole,
    login: (payload: LoginPayload) => store.login(payload),
    register: (payload: RegisterPayload) => store.register(payload),
    logout: () => store.logout(),
    bootstrap: () => store.bootstrap(),
  }
}
