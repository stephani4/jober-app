import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { authService } from '@/services/AuthService'
import { HttpClient } from '@/services/HttpClient'
import type { AuthAdmin, LoginPayload } from '@/schemas/auth'

export const useAuthStore = defineStore('auth', () => {
  const admin = ref<AuthAdmin | null>(null)
  const bootstrapped = ref(false)

  const isAuthenticated = computed(() => Boolean(admin.value && HttpClient.getToken()))

  function can(permission: string): boolean {
    return Boolean(admin.value?.permissions.includes(permission) || admin.value?.roles.includes('super-admin'))
  }

  async function login(payload: LoginPayload): Promise<void> {
    const response = await authService.login(payload)
    admin.value = response.admin
  }

  async function logout(): Promise<void> {
    await authService.logout()
    admin.value = null
  }

  async function bootstrap(): Promise<void> {
    if (bootstrapped.value) {
      return
    }

    const token = HttpClient.getToken()
    if (!token) {
      bootstrapped.value = true
      return
    }

    try {
      admin.value = await authService.me()
    } catch {
      HttpClient.clearToken()
      admin.value = null
    } finally {
      bootstrapped.value = true
    }
  }

  return {
    admin,
    bootstrapped,
    isAuthenticated,
    can,
    login,
    logout,
    bootstrap,
  }
})
