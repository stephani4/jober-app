import { z } from 'zod'

export const notificationFilterSchema = z.enum(['all', 'unread'])

export const appNotificationSchema = z.object({
  id: z.string().min(1),
  type: z.string(),
  title: z.string(),
  body: z.string(),
  order_id: z.number().int().positive().nullable().optional(),
  read_at: z.string().nullable().optional(),
  created_at: z.string().nullable().optional(),
})

export const notificationListSchema = z.object({
  items: z.array(appNotificationSchema),
  next_cursor: z.string().nullable(),
  unread_count: z.number().int().nonnegative(),
})

export const notificationUnreadCountSchema = z.object({
  unread_count: z.number().int().nonnegative(),
})

export const notificationCreatedEventSchema = z.object({
  type: z.literal('notification.created'),
  notification: appNotificationSchema,
  unread_count: z.number().int().nonnegative(),
})

export type NotificationFilter = z.infer<typeof notificationFilterSchema>
export type AppNotification = z.infer<typeof appNotificationSchema>
export type NotificationList = z.infer<typeof notificationListSchema>
export type NotificationCreatedEvent = z.infer<typeof notificationCreatedEventSchema>
