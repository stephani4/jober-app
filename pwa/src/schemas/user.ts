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
  avatar_id: z.number().int().positive().nullable().optional(),
  avatar_url: z.string().nullable().optional(),
  created_at: z.string().nullable().optional(),
})

/** Ответ profile:update — поля профиля, которые RPC возвращает после сохранения. */
export const profileSchema = userSchema.pick({
  id: true,
  name: true,
  email: true,
  birth_date: true,
  working_area_id: true,
  avatar_id: true,
  avatar_url: true,
})

/** Данные формы редактирования профиля. */
export const profileUpdatePayloadSchema = z.object({
  name: z.string().trim().min(1, 'Укажите имя'),
  birth_date: z.string().nullable(),
  working_area_id: z.number().int().positive().nullable(),
  avatar_id: z.number().int().positive().nullable(),
})

export type UserRole = z.infer<typeof userRoleSchema>
export type User = z.infer<typeof userSchema>
export type Profile = z.infer<typeof profileSchema>
export type ProfileUpdatePayload = z.infer<typeof profileUpdatePayloadSchema>
