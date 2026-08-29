import { z } from 'zod'

export const orderMessageUserSchema = z.object({
  id: z.number().int().positive(),
  name: z.string(),
  role: z.enum(['customer', 'executor']).nullable().optional(),
})

export const orderMessageSchema = z.object({
  id: z.number().int().positive(),
  order_id: z.number().int().positive(),
  user_id: z.number().int().positive(),
  body: z.string(),
  created_at: z.string().nullable().optional(),
  user: orderMessageUserSchema.nullable().optional(),
})

export const orderMessageEventSchema = z.object({
  type: z.literal('order.message'),
  message: orderMessageSchema,
})

export type OrderMessage = z.infer<typeof orderMessageSchema>
export type OrderMessageEvent = z.infer<typeof orderMessageEventSchema>
