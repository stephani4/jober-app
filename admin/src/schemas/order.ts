import { z } from 'zod'

export const orderStatusSchema = z.enum(['moderate', 'wait', 'process', 'complete', 'cancel'])

export const orderTypeSchema = z.object({
  id: z.number().int().positive(),
  name: z.string(),
  description: z.string(),
  max_points: z.number().int().positive().optional(),
})

export const orderPointSchema = z.object({
  id: z.number().int().positive(),
  description: z.string(),
  address: z.string().nullable().optional(),
  lat: z.number().nullable().optional(),
  lon: z.number().nullable().optional(),
  position: z.number().int().positive(),
})

export const orderUserSchema = z.object({
  id: z.number().int().positive(),
  name: z.string(),
  email: z.string().email(),
  role: z.string().nullable().optional(),
})

export const orderSchema = z.object({
  id: z.number().int().positive(),
  user_id: z.number().int().positive(),
  order_type_id: z.number().int().positive().nullable().optional(),
  order_type: orderTypeSchema.nullable().optional(),
  description: z.string(),
  cost: z.number(),
  status: orderStatusSchema.default('wait'),
  reason: z.string().nullable().optional(),
  created_at: z.string().nullable().optional(),
  user: orderUserSchema.optional(),
  points: z.array(orderPointSchema).default([]),
})

export const orderListSchema = z.object({
  items: z.array(orderSchema),
  next_cursor: z.number().int().positive().nullable(),
})

export const orderActionSchema = z.object({
  order: orderSchema,
})

export type OrderStatus = z.infer<typeof orderStatusSchema>
export type Order = z.infer<typeof orderSchema>
export type OrderList = z.infer<typeof orderListSchema>

export const orderStatusLabel: Record<OrderStatus, string> = {
  moderate: 'На модерации',
  wait: 'Ожидает исполнителя',
  process: 'Выполняется',
  complete: 'Исполнено',
  cancel: 'Отклонён',
}
