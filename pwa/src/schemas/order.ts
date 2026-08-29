import { z } from 'zod'
import { userSchema } from '@/schemas/user'

export const orderStatusSchema = z.enum(['moderate', 'wait', 'process', 'complete', 'cancel'])
export const orderExecutingStatusSchema = z.enum(['wait', 'process', 'complete'])

export const orderPointSchema = z.object({
  id: z.number().int().positive(),
  description: z.string(),
  address: z.string().nullable(),
  lat: z.number().nullable(),
  lon: z.number().nullable(),
  position: z.number().int().positive(),
  cost: z.number().optional(),
})

export const orderSchema = z.object({
  id: z.number().int().positive(),
  user_id: z.number().int().positive(),
  description: z.string(),
  cost: z.number(),
  status: orderStatusSchema.default('wait'),
  reason: z.string().nullable().optional(),
  created_at: z.string().nullable().optional(),
  updated_at: z.string().nullable().optional(),
  user: userSchema.pick({ id: true, name: true, email: true, role: true }).optional(),
  points: z.array(orderPointSchema).default([]),
})

export const orderCreatedEventSchema = z.object({
  type: z.literal('order.created'),
  order: orderSchema,
})

export const orderTakenEventSchema = z.object({
  type: z.literal('order.taken'),
  order_id: z.number().int().positive(),
})

export const orderModeratedEventSchema = z.object({
  type: z.literal('order.moderated'),
  order: orderSchema,
})

export const orderStatusEventSchema = z.object({
  type: z.literal('order.status'),
  order: orderSchema,
})

export const createOrderPointPayloadSchema = z.object({
  description: z.string().trim().min(1, 'Опишите, что нужно сделать'),
  address: z.string().nullable().optional(),
  lat: z.number(),
  lon: z.number(),
  position: z.number().int().positive().optional(),
})

export const createOrderPayloadSchema = z.object({
  description: z.string().trim(),
  cost: z.number().positive('Укажите стоимость заказа'),
  points: z.array(createOrderPointPayloadSchema).min(1, 'Добавьте хотя бы одну точку'),
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

export const orderExecutingSchema = z.object({
  id: z.number().int().positive(),
  order_id: z.number().int().positive(),
  executor_id: z.number().int().positive(),
  status: orderExecutingStatusSchema,
  process_at: z.string().nullable().optional(),
  complete_at: z.string().nullable().optional(),
  lat: z.number().nullable().optional(),
  lon: z.number().nullable().optional(),
  location_at: z.string().nullable().optional(),
  order: orderSchema.optional(),
  points: z.array(orderExecutingPointSchema).default([]),
})

export const orderExecutingEventSchema = z.object({
  type: z.literal('order.executing'),
  executing: orderExecutingSchema,
})

export const executorLocationEventSchema = z.object({
  type: z.literal('executor.location'),
  order_id: z.number().int().positive(),
  lat: z.number(),
  lon: z.number(),
})

export const activeExecutionSchema = z.object({
  order_id: z.number().int().positive().nullable(),
  view: z.enum(['execute', 'watch']).nullable(),
})

export const orderHistoryListSchema = z.object({
  items: z.array(orderSchema),
  next_cursor: z.number().int().positive().nullable(),
})

export type OrderPoint = z.infer<typeof orderPointSchema>
export type Order = z.infer<typeof orderSchema>
export type OrderCreatedEvent = z.infer<typeof orderCreatedEventSchema>
export type OrderTakenEvent = z.infer<typeof orderTakenEventSchema>
export type OrderStatusEvent = z.infer<typeof orderStatusEventSchema>
export type OrderExecutingEvent = z.infer<typeof orderExecutingEventSchema>
export type ExecutorLocationEvent = z.infer<typeof executorLocationEventSchema>
export type ActiveExecution = z.infer<typeof activeExecutionSchema>
export type OrderHistoryList = z.infer<typeof orderHistoryListSchema>
export type CreateOrderPayload = z.infer<typeof createOrderPayloadSchema>
export type CreateOrderPointPayload = z.infer<typeof createOrderPointPayloadSchema>
export type OrderStatus = z.infer<typeof orderStatusSchema>
export type OrderExecutingStatus = z.infer<typeof orderExecutingStatusSchema>
export type OrderModeratedEvent = z.infer<typeof orderModeratedEventSchema>
export type OrderExecutingPoint = z.infer<typeof orderExecutingPointSchema>
export type OrderExecuting = z.infer<typeof orderExecutingSchema>

export const orderStatusLabel: Record<OrderStatus, string> = {
  moderate: 'На модерации',
  wait: 'Ожидает исполнителя',
  process: 'Выполняется',
  complete: 'Исполнено',
  cancel: 'Отклонён',
}
