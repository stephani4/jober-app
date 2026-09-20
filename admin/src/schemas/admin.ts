import { z } from 'zod'

export const adminRoleSchema = z.enum(['super-admin', 'moderator', 'users', 'admin-worker'])

export const adminOptionSchema = z.object({
  value: z.string().min(1),
  label: z.string().min(1),
})

export const adminCatalogSchema = z.object({
  roles: z.array(adminOptionSchema),
  permissions: z.array(adminOptionSchema),
})

export const adminSchema = z.object({
  id: z.number().int().positive(),
  name: z.string().min(1),
  email: z.string().email(),
  roles: z.array(z.string()).default([]),
  role_labels: z.array(z.string()).optional().default([]),
  permissions: z.array(z.string()).default([]),
  direct_permissions: z.array(z.string()).optional().default([]),
  created_at: z.string().nullable().optional(),
})

export const adminListSchema = z.object({
  items: z.array(adminSchema),
  next_cursor: z.number().int().positive().nullable(),
})

export type AdminRole = z.infer<typeof adminRoleSchema>
export type AdminOption = z.infer<typeof adminOptionSchema>
export type AdminCatalog = z.infer<typeof adminCatalogSchema>
export type Admin = z.infer<typeof adminSchema>
export type AdminList = z.infer<typeof adminListSchema>

export const adminRoleLabel: Record<AdminRole, string> = {
  'super-admin': 'Суперадмин',
  moderator: 'Модератор',
  users: 'Пользователи',
  'admin-worker': 'Работа с администраторами',
}

export function formatAdminRoles(admin: Admin): string {
  if (admin.role_labels.length > 0) {
    return admin.role_labels.join(', ')
  }

  return admin.roles
    .map((role) => (role in adminRoleLabel ? adminRoleLabel[role as AdminRole] : role))
    .join(', ')
}
