import { z } from 'zod'
import { uploadedFileSchema } from '@/schemas/file'

export const orderStatusSchema = z.enum(['moderate', 'wait', 'process', 'complete', 'cancel'])
export const orderExecutingStatusSchema = z.enum(['wait', 'process', 'complete', 'cancel', 'confirmation'])

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
  // Данные доступа в здание (заполняются при создании заказа).
  entrance: z.number().int().nullable().optional(),
  floor: z.number().int().nullable().optional(),
  apartment: z.string().nullable().optional(),
  intercom: z.number().int().nullable().optional(),
  files: z.array(uploadedFileSchema).default([]),
})

export const orderUserSchema = z.object({
  id: z.number().int().positive(),
  name: z.string(),
  email: z.string().email(),
  role: z.string().nullable().optional(),
})

/** Исполнитель, принявший заказ в работу: имя и аватар. */
export const orderExecutorSchema = z.object({
  id: z.number().int().positive(),
  name: z.string(),
  avatar_id: z.number().int().positive().nullable().optional(),
  avatar_url: z.string().nullable().optional(),
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
  complete_at: z.string().nullable().optional(),
  user: orderUserSchema.optional(),
  // Исполнитель заказа: приходит, когда заказ взят в работу.
  executor: orderExecutorSchema.nullable().optional(),
  points: z.array(orderPointSchema).default([]),
})

export const orderExecutingPointSchema = z.object({
  id: z.number().int().positive(),
  order_executing_id: z.number().int().positive(),
  order_point_id: z.number().int().positive(),
  status: orderExecutingStatusSchema,
  process_at: z.string().nullable().optional(),
  complete_at: z.string().nullable().optional(),
  order_point: orderPointSchema.nullable().optional(),
})

/** Снимок выполнения заказа: статусы точек и координаты исполнителя. */
export const orderExecutingSchema = z.object({
  id: z.number().int().positive(),
  order_id: z.number().int().positive(),
  executor_id: z.number().int().positive(),
  status: orderExecutingStatusSchema,
  process_at: z.string().nullable().optional(),
  complete_at: z.string().nullable().optional(),
  canceled_at: z.string().nullable().optional(),
  confirmation_at: z.string().nullable().optional(),
  lat: z.number().nullable().optional(),
  lon: z.number().nullable().optional(),
  location_at: z.string().nullable().optional(),
  order: orderSchema.optional(),
  executor: orderExecutorSchema.nullable().optional(),
  points: z.array(orderExecutingPointSchema).default([]),
})

/** Событие координат исполнителя из канала наблюдения. */
export const executorLocationEventSchema = z.object({
  type: z.literal('executor.location'),
  order_id: z.number().int().positive(),
  lat: z.number(),
  lon: z.number(),
})

/** Событие обновления выполнения (статусы точек) из канала наблюдения. */
export const orderExecutingEventSchema = z.object({
  type: z.literal('order.executing'),
  executing: orderExecutingSchema,
})

export const orderListSchema = z.object({
  items: z.array(orderSchema),
  next_cursor: z.number().int().positive().nullable(),
})

export const orderTypeListSchema = z.object({
  types: z.array(orderTypeSchema),
})

export const orderActionSchema = z.object({
  order: orderSchema,
})

export const orderShowSchema = z.object({
  order: orderSchema,
})

export const orderExecutingPayloadSchema = z.object({
  executing: orderExecutingSchema.nullable(),
})

export const realtimeTokenSchema = z.object({
  token: z.string().min(1),
})

export type OrderStatus = z.infer<typeof orderStatusSchema>
export type OrderExecutingStatus = z.infer<typeof orderExecutingStatusSchema>
export type OrderType = z.infer<typeof orderTypeSchema>
export type Order = z.infer<typeof orderSchema>
export type OrderExecuting = z.infer<typeof orderExecutingSchema>
export type OrderExecutor = z.infer<typeof orderExecutorSchema>
export type OrderList = z.infer<typeof orderListSchema>
export type OrderTypeList = z.infer<typeof orderTypeListSchema>
export type ExecutorLocationEvent = z.infer<typeof executorLocationEventSchema>
export type OrderExecutingEvent = z.infer<typeof orderExecutingEventSchema>

export const orderStatusLabel: Record<OrderStatus, string> = {
  moderate: 'На модерации',
  wait: 'Ожидает исполнителя',
  process: 'Выполняется',
  complete: 'Исполнено',
  cancel: 'Отклонён',
}

export const orderExecutingStatusLabel: Record<OrderExecutingStatus, string> = {
  wait: 'Ожидает исполнителя',
  process: 'Выполняется',
  complete: 'Исполнено',
  cancel: 'Отменено',
  confirmation: 'Подтверждение',
}
