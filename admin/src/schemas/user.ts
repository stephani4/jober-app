import { z } from 'zod'

export const userRoleSchema = z.enum(['customer', 'executor'])

export const userSchema = z.object({
  id: z.number().int().positive(),
  name: z.string(),
  email: z.string().email(),
  birth_date: z.string().nullable().optional(),
  role: userRoleSchema.nullable().optional(),
  role_label: z.string().nullable().optional(),
  created_at: z.string().nullable().optional(),
})

export const userListSchema = z.object({
  items: z.array(userSchema),
  next_cursor: z.number().int().positive().nullable(),
})

export type UserRole = z.infer<typeof userRoleSchema>
export type User = z.infer<typeof userSchema>
export type UserList = z.infer<typeof userListSchema>

export const userRoleLabel: Record<UserRole, string> = {
  customer: 'Заказчик',
  executor: 'Исполнитель',
}