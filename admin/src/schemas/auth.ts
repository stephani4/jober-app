import { z } from 'zod'
import { adminSchema } from '@/schemas/admin'

export const loginPayloadSchema = z.object({
  login: z.string().trim().min(1, 'Укажите логин'),
  password: z.string().min(1, 'Укажите пароль'),
})

export const loginResponseSchema = z.object({
  token: z.string().min(1),
  token_type: z.string(),
  expires_in: z.number(),
  admin: adminSchema,
})

export type LoginPayload = z.infer<typeof loginPayloadSchema>
export type LoginResponse = z.infer<typeof loginResponseSchema>
export type AuthAdmin = z.infer<typeof adminSchema>
