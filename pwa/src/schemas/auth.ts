import { z } from 'zod'
import { userRoleSchema, userSchema } from '@/schemas/user'

export const loginPayloadSchema = z.object({
  login: z.string().trim().min(1, 'Укажите логин'),
  password: z.string().min(1, 'Укажите пароль'),
})

export const registerPayloadSchema = z
  .object({
    name: z.string().trim().min(2, 'Укажите ФИО'),
    email: z.string().trim().email('Некорректный email'),
    password: z.string().min(8, 'Пароль не короче 8 символов'),
    password_confirmation: z.string().min(1, 'Повторите пароль'),
    birth_date: z.string().min(1, 'Укажите дату рождения'),
    role: userRoleSchema,
    personal_data_consent: z.boolean().refine((value) => value === true, {
      message: 'Необходимо согласие на обработку персональных данных',
    }),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: 'Пароли не совпадают',
    path: ['password_confirmation'],
  })

export const loginResponseSchema = z.object({
  token: z.string().min(1),
  token_type: z.string(),
  expires_in: z.number(),
  user: userSchema,
})

export const registerResponseSchema = z.object({
  message: z.string(),
  user: userSchema.pick({ id: true, name: true, email: true, role: true }),
})

export type LoginPayload = z.infer<typeof loginPayloadSchema>
export type RegisterPayload = z.infer<typeof registerPayloadSchema>
export type LoginResponse = z.infer<typeof loginResponseSchema>
export type RegisterResponse = z.infer<typeof registerResponseSchema>
export type AuthUser = z.infer<typeof userSchema>
