import { http } from '@/services/HttpClient'
import { userListSchema, type UserList, type UserRole } from '@/schemas/user'

/** Параметры фильтрации списка пользователей. */
export interface UserListFilters {
  name?: string
  email?: string
  birth_date?: string
  role?: UserRole | ''
}

/**
 * Пользователи приложения в контуре /api/admin.
 */
export class AdminUserService {
  async list(filters: UserListFilters, cursor?: number | null): Promise<UserList> {
    const { data } = await http.client.get('/admin/users', {
      params: {
        name: filters.name || undefined,
        email: filters.email || undefined,
        birth_date: filters.birth_date || undefined,
        role: filters.role || undefined,
        cursor: cursor ?? undefined,
      },
    })
    return userListSchema.parse(data)
  }
}

export const adminUserService = new AdminUserService()