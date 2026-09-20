import { http } from '@/services/HttpClient'
import {
  adminCatalogSchema,
  adminListSchema,
  adminSchema,
  type Admin,
  type AdminCatalog,
  type AdminList,
  type AdminRole,
} from '@/schemas/admin'

/** Параметры фильтрации списка сотрудников. */
export interface AdminListFilters {
  name?: string
  email?: string
  role?: AdminRole
}

export interface AdminWritePayload {
  name: string
  email: string
  password?: string
  roles: string[]
  permissions: string[]
}

/**
 * Сотрудники админки в контуре /api/admin/admins.
 */
export class AdminAdministratorService {
  async list(filters: AdminListFilters, cursor?: number | null): Promise<AdminList> {
    const { data } = await http.client.get('/admin/admins', {
      params: {
        name: filters.name || undefined,
        email: filters.email || undefined,
        role: filters.role ?? undefined,
        cursor: cursor ?? undefined,
      },
    })
    return adminListSchema.parse(data)
  }

  async getCatalog(): Promise<AdminCatalog> {
    const { data } = await http.client.get('/admin/admins/catalog')
    return adminCatalogSchema.parse(data)
  }

  async getById(id: number): Promise<Admin> {
    const { data } = await http.client.get(`/admin/admins/${id}`)
    return adminSchema.parse(data)
  }

  async create(payload: AdminWritePayload): Promise<Admin> {
    const { data } = await http.client.post('/admin/admins', payload)
    return adminSchema.parse(data)
  }

  async update(id: number, payload: AdminWritePayload): Promise<Admin> {
    const { data } = await http.client.put(`/admin/admins/${id}`, payload)
    return adminSchema.parse(data)
  }

  async delete(id: number): Promise<void> {
    await http.client.delete(`/admin/admins/${id}`)
  }
}

export const adminAdministratorService = new AdminAdministratorService()
