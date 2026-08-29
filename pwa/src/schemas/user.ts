import { z } from 'zod'

export const userRoleSchema = z.enum(['customer', 'executor'])

export const userSchema = z.object({
  id: z.number().int().positive(),
  name: z.string().min(1),
  email: z.string().email(),
  role: userRoleSchema,
  birth_date: z.string().nullable().optional(),
})

export type UserRole = z.infer<typeof userRoleSchema>
export type User = z.infer<typeof userSchema>
