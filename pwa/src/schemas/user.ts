import { z } from 'zod'

export const userRoleSchema = z.enum(['customer', 'executor'])

/** Все роли PWA: нужны для проверки ролей в middleware маршрутов и в UI. */
export const userRoles = userRoleSchema.options

export const userSchema = z.object({
  id: z.number().int().positive(),
  name: z.string().min(1),
  email: z.string().email(),
  role: userRoleSchema,
  birth_date: z.string().nullable().optional(),
  working_area_id: z.number().int().positive().nullable().optional(),
  created_at: z.string().nullable().optional(),
})

export type UserRole = z.infer<typeof userRoleSchema>
export type User = z.infer<typeof userSchema>
