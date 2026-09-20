import { z } from 'zod'
import { uploadedFileSchema } from '@/schemas/file'
import { userSchema } from '@/schemas/user'

export const BUY_AND_DELIVER_TYPE_ID = 1
export const HELP_CARRY_TYPE_ID = 2
export const ERRAND_TYPE_ID = 3

export const orderStatusSchema = z.enum(['moderate', 'wait', 'process', 'complete', 'cancel'])
export const orderExecutingStatusSchema = z.enum(['wait', 'process', 'complete', 'cancel', 'confirmation'])

export const orderTypeSchema = z.object({
  id: z.number().int().positive(),
  name: z.string(),
  description: z.string(),
  max_points: z.number().int().positive(),
})

export const orderPointSchema = z.object({
  id: z.number().int().positive(),
  description: z.string(),
  address: z.string().nullable(),
  lat: z.number().nullable(),
  lon: z.number().nullable(),
  position: z.number().int().positive(),
  cost: z.number().optional(),
  // Данные доступа в здание: заполняются при создании, если выбран дом.
  entrance: z.number().int().nullable().optional(),
  floor: z.number().int().nullable().optional(),
  apartment: z.string().nullable().optional(),
  intercom: z.number().int().nullable().optional(),
  files: z.array(uploadedFileSchema).optional().default([]),
})

/** Исполнитель, принявший заказ в работу: имя и аватар для карточек заказа. */
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
  // Код подтверждения приходит только автору заказа (personal-канал, mine).
  confirmation_number: z.string().nullable().optional(),
  created_at: z.string().nullable().optional(),
  updated_at: z.string().nullable().optional(),
  user: userSchema.pick({ id: true, name: true, email: true, role: true }).optional(),
  // Исполнитель заказа: приходит, когда заказ взят в работу.
  executor: orderExecutorSchema.nullable().optional(),
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

export const orderCancelledEventSchema = z.object({
  type: z.literal('order.cancelled'),
  order_id: z.number().int().positive(),
  order: orderSchema.optional(),
})

export const orderDeclinedEventSchema = z.object({
  type: z.literal('order.declined'),
  order_id: z.number().int().positive(),
  order: orderSchema.optional(),
})

export const createOrderPointPayloadSchema = z.object({
  description: z.string().trim().min(1, 'Опишите, что нужно сделать'),
  address: z.string().nullable().optional(),
  lat: z.number(),
  lon: z.number(),
  position: z.number().int().positive().optional(),
  entrance: z.number().int().min(0).nullable().optional(),
  floor: z.number().int().min(0).nullable().optional(),
  apartment: z.string().trim().max(20).nullable().optional(),
  intercom: z.number().int().min(0).nullable().optional(),
  file_ids: z.array(z.number().int().positive()).max(10).optional().default([]),
})

export const createOrderPayloadSchema = z.object({
  order_type_id: z.number().int().positive(),
  description: z.string().trim(),
  cost: z.number().positive('Укажите стоимость заказа'),
  points: z.array(createOrderPointPayloadSchema).min(1, 'Добавьте хотя бы одну точку'),
}).superRefine((payload, ctx) => {
  if (payload.order_type_id === BUY_AND_DELIVER_TYPE_ID && payload.points.length > 1) {
    ctx.addIssue({
      code: z.ZodIssueCode.custom,
      message: 'Для этого вида заказа нужна одна точка доставки.',
      path: ['points'],
    })
  }
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
  canceled_at: z.string().nullable().optional(),
  confirmation_at: z.string().nullable().optional(),
  lat: z.number().nullable().optional(),
  lon: z.number().nullable().optional(),
  location_at: z.string().nullable().optional(),
  order: orderSchema.optional(),
  // Исполнитель текущего назначения — для страницы наблюдения.
  executor: orderExecutorSchema.nullable().optional(),
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
export type OrderExecutor = z.infer<typeof orderExecutorSchema>
export type OrderType = z.infer<typeof orderTypeSchema>
export type Order = z.infer<typeof orderSchema>
export type OrderCreatedEvent = z.infer<typeof orderCreatedEventSchema>
export type OrderTakenEvent = z.infer<typeof orderTakenEventSchema>
export type OrderStatusEvent = z.infer<typeof orderStatusEventSchema>
export type OrderCancelledEvent = z.infer<typeof orderCancelledEventSchema>
export type OrderDeclinedEvent = z.infer<typeof orderDeclinedEventSchema>
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
