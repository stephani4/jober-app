import { http, HttpClient } from '@/services/HttpClient'
import {
  loginResponseSchema,
  registerResponseSchema,
  type AuthUser,
  type LoginPayload,
  type LoginResponse,
  type RegisterPayload,
  type RegisterResponse,
} from '@/schemas/auth'
import { userSchema } from '@/schemas/user'

export class AuthService {
  async login(payload: LoginPayload): Promise<LoginResponse> {
    const { data } = await http.client.post('/auth/login', payload)
    const parsed = loginResponseSchema.parse(data)
    HttpClient.setToken(parsed.token)
    return parsed
  }

  async register(payload: RegisterPayload): Promise<RegisterResponse> {
    const { data } = await http.client.post('/auth/register', payload)
    return registerResponseSchema.parse(data)
  }

  async me(): Promise<AuthUser> {
    const { data } = await http.client.get('/auth/me')
    return userSchema.parse(data)
  }

  async logout(): Promise<void> {
    try {
      await http.client.post('/auth/logout')
    } finally {
      HttpClient.clearToken()
    }
  }
}

export const authService = new AuthService()
