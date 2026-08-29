import { http, HttpClient } from '@/services/HttpClient'
import {
  loginResponseSchema,
  type AuthAdmin,
  type LoginPayload,
  type LoginResponse,
} from '@/schemas/auth'
import { adminSchema } from '@/schemas/admin'

/**
 * Логин и сессия сотрудника админки.
 */
export class AuthService {
  async login(payload: LoginPayload): Promise<LoginResponse> {
    const { data } = await http.client.post('/admin/auth/login', payload)
    const parsed = loginResponseSchema.parse(data)
    HttpClient.setToken(parsed.token)
    return parsed
  }

  async me(): Promise<AuthAdmin> {
    const { data } = await http.client.get('/admin/auth/me')
    return adminSchema.parse(data)
  }

  async logout(): Promise<void> {
    try {
      await http.client.post('/admin/auth/logout')
    } finally {
      HttpClient.clearToken()
    }
  }
}

export const authService = new AuthService()
