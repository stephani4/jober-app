import axios, { type AxiosInstance } from 'axios'

const TOKEN_KEY = 'jober_admin_token'

/**
 * HTTP-клиент админки. Токен отдельный от PWA, чтобы сессии на localhost не пересекались.
 */
export class HttpClient {
  readonly client: AxiosInstance

  constructor(baseURL = '/api') {
    this.client = axios.create({
      baseURL,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
    })

    this.client.interceptors.request.use((config) => {
      const token = localStorage.getItem(TOKEN_KEY)
      if (token) {
        config.headers.Authorization = `Bearer ${token}`
      }
      return config
    })
  }

  static getToken(): string | null {
    return localStorage.getItem(TOKEN_KEY)
  }

  static setToken(token: string): void {
    localStorage.setItem(TOKEN_KEY, token)
  }

  static clearToken(): void {
    localStorage.removeItem(TOKEN_KEY)
  }
}

export const http = new HttpClient()
