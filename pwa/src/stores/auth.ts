import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { authService } from '@/services/AuthService'
import { HttpClient } from '@/services/HttpClient'
import type { AuthUser, LoginPayload, RegisterPayload } from '@/schemas/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<AuthUser | null>(null)
  const bootstrapped = ref(false)

  const isAuthenticated = computed(() => Boolean(user.value && HttpClient.getToken()))

  async function login(payload: LoginPayload): Promise<void> {
    const response = await authService.login(payload)
    user.value = response.user
  }

  async function register(payload: RegisterPayload): Promise<string> {
    const response = await authService.register(payload)
    return response.message
  }

  async function logout(): Promise<void> {
    await authService.logout()
    user.value = null
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
      user.value = await authService.me()
    } catch {
      HttpClient.clearToken()
      user.value = null
    } finally {
      bootstrapped.value = true
    }
  }

  return {
    user,
    bootstrapped,
    isAuthenticated,
    login,
    register,
    logout,
    bootstrap,
  }
})
