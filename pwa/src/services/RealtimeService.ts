import { notificationCreatedEventSchema, notificationListSchema, notificationUnreadCountSchema, type NotificationCreatedEvent, type NotificationFilter, type NotificationList } from '@/schemas/notification'
import {
  activeExecutionSchema,
  executorLocationEventSchema,
  orderCancelledEventSchema,
  orderCreatedEventSchema,
  orderDeclinedEventSchema,
  orderExecutingEventSchema,
  orderExecutingSchema,
  orderHistoryListSchema,
  orderModeratedEventSchema,
  orderSchema,
  orderStatusEventSchema,
  orderTakenEventSchema,
  type ActiveExecution,
  type ExecutorLocationEvent,
  type Order,
  type OrderCancelledEvent,
  type OrderDeclinedEvent,
  type OrderExecuting,
  type OrderExecutingEvent,
  type OrderHistoryList,
  type OrderModeratedEvent,
  type OrderStatusEvent,
} from '@/schemas/order'
import { orderMessageEventSchema, orderMessageSchema, type OrderMessage, type OrderMessageEvent } from '@/schemas/orderMessage'
import { profileSchema, type Profile, type ProfileUpdatePayload } from '@/schemas/user'
import { workingAreaOptionListSchema, type WorkingAreaOption } from '@/schemas/workingArea'
import { centrifugoClient } from '@/services/CentrifugoClient'

/**
 * Доменные realtime-события поверх Centrifugo.
 */
export class RealtimeService {
  /**
   * Маршрутизирует publication в обработчик заказов.
   */
  onOrderCreated(handler: (order: Order) => void): () => void {
    return centrifugoClient.onPublication((_channel, data) => {
      const parsed = orderCreatedEventSchema.safeParse(data)
      if (parsed.success) {
        handler(parsed.data.order)
      }
    })
  }

  onOrderTaken(handler: (orderId: number) => void): () => void {
    return centrifugoClient.onPublication((_channel, data) => {
      const parsed = orderTakenEventSchema.safeParse(data)
      if (parsed.success) {
        handler(parsed.data.order_id)
      }
    })
  }

  onOrderStatus(handler: (event: OrderStatusEvent) => void): () => void {
    return centrifugoClient.onPublication((_channel, data) => {
      const parsed = orderStatusEventSchema.safeParse(data)
      if (parsed.success) {
        handler(parsed.data)
      }
    })
  }

  onOrderModerated(handler: (event: OrderModeratedEvent) => void): () => void {
    return centrifugoClient.onPublication((_channel, data) => {
      const parsed = orderModeratedEventSchema.safeParse(data)
      if (parsed.success) {
        handler(parsed.data)
      }
    })
  }

  onOrderCancelled(handler: (event: OrderCancelledEvent) => void): () => void {
    return centrifugoClient.onPublication((_channel, data) => {
      const parsed = orderCancelledEventSchema.safeParse(data)
      if (parsed.success) {
        handler(parsed.data)
      }
    })
  }

  onOrderDeclined(handler: (event: OrderDeclinedEvent) => void): () => void {
    return centrifugoClient.onPublication((_channel, data) => {
      const parsed = orderDeclinedEventSchema.safeParse(data)
      if (parsed.success) {
        handler(parsed.data)
      }
    })
  }

  onOrderExecuting(handler: (event: OrderExecutingEvent) => void): () => void {
    return centrifugoClient.onPublication((_channel, data) => {
      const parsed = orderExecutingEventSchema.safeParse(data)
      if (parsed.success) {
        handler(parsed.data)
      }
    })
  }

  onExecutorLocation(handler: (event: ExecutorLocationEvent) => void): () => void {
    return centrifugoClient.onPublication((_channel, data) => {
      const parsed = executorLocationEventSchema.safeParse(data)
      if (parsed.success) {
        handler(parsed.data)
      }
    })
  }

  async createOrder(payload: unknown): Promise<Order> {
    const data = await centrifugoClient.rpc<unknown>('order:create', payload)
    return orderSchema.parse(data)
  }

  async listMine(): Promise<Order[]> {
    const data = await centrifugoClient.rpc<unknown>('order:mine')
    return zArray(data)
  }

  async listHistory(cursor?: number | null): Promise<OrderHistoryList> {
    const data = await centrifugoClient.rpc<unknown>('order:history', {
      cursor: cursor ?? null,
    })
    return orderHistoryListSchema.parse(data)
  }

  async listFeed(): Promise<Order[]> {
    const data = await centrifugoClient.rpc<unknown>('order:feed')
    return zArray(data)
  }

  async startOrder(orderId: number): Promise<OrderExecuting> {
    const data = await centrifugoClient.rpc<unknown>('order:start', { order_id: orderId })
    return orderExecutingSchema.parse(data)
  }

  async getExecuting(orderId: number): Promise<OrderExecuting> {
    const data = await centrifugoClient.rpc<unknown>('order:executing', { order_id: orderId })
    return orderExecutingSchema.parse(data)
  }

  async getWatching(orderId: number): Promise<OrderExecuting> {
    const data = await centrifugoClient.rpc<unknown>('order:watching', { order_id: orderId })
    return orderExecutingSchema.parse(data)
  }

  async getActive(): Promise<ActiveExecution> {
    const data = await centrifugoClient.rpc<unknown>('order:active')
    return activeExecutionSchema.parse(data)
  }

  async publishLocation(orderId: number, position: { lat: number; lon: number }): Promise<void> {
    await centrifugoClient.rpc('order:location', {
      order_id: orderId,
      lat: position.lat,
      lon: position.lon,
    })
  }

  onNotificationCreated(handler: (event: NotificationCreatedEvent) => void): () => void {
    return centrifugoClient.onPublication((_channel, data) => {
      const parsed = notificationCreatedEventSchema.safeParse(data)
      if (parsed.success) {
        handler(parsed.data)
      }
    })
  }

  async completePoint(orderId: number, orderPointId: number): Promise<OrderExecuting> {
    const data = await centrifugoClient.rpc<unknown>('order:completePoint', {
      order_id: orderId,
      order_point_id: orderPointId,
    })
    return orderExecutingSchema.parse(data)
  }

  async confirmCompletion(orderId: number, code: string): Promise<OrderExecuting> {
    const data = await centrifugoClient.rpc<unknown>('order:confirm', {
      order_id: orderId,
      code,
    })
    return orderExecutingSchema.parse(data)
  }

  async cancelOrder(orderId: number): Promise<Order> {
    const data = await centrifugoClient.rpc<unknown>('order:cancel', {
      order_id: orderId,
    })
    return orderSchema.parse(data)
  }

  async declineOrder(orderId: number): Promise<OrderExecuting> {
    const data = await centrifugoClient.rpc<unknown>('order:decline', {
      order_id: orderId,
    })
    return orderExecutingSchema.parse(data)
  }

  async responsesCount(): Promise<number> {
    return await centrifugoClient.rpc<number>('order:responses_count')
  }

  async getAvailableExecutorsCount(): Promise<number> {
    return await centrifugoClient.rpc<number>('order:available_executors_count')
  }

  onOrderMessage(handler: (event: OrderMessageEvent) => void): () => void {
    return centrifugoClient.onPublication((_channel, data) => {
      const parsed = orderMessageEventSchema.safeParse(data)
      if (parsed.success) {
        handler(parsed.data)
      }
    })
  }

  async listOrderMessages(orderId: number): Promise<OrderMessage[]> {
    const data = await centrifugoClient.rpc<unknown>('order:messages', { order_id: orderId })
    if (!Array.isArray(data)) {
      return []
    }
    return data.map((item) => orderMessageSchema.parse(item))
  }

  async sendOrderMessage(orderId: number, body: string): Promise<OrderMessage> {
    const data = await centrifugoClient.rpc<unknown>('order:message:send', {
      order_id: orderId,
      body,
    })
    return orderMessageSchema.parse(data)
  }

  async listNotifications(payload: {
    filter: NotificationFilter
    cursor?: string | null
  }): Promise<NotificationList> {
    const data = await centrifugoClient.rpc<unknown>('notification:list', payload)
    return notificationListSchema.parse(data)
  }

  async markNotificationsRead(ids: string[]): Promise<{ unread_count: number }> {
    const data = await centrifugoClient.rpc<unknown>('notification:read', { ids })
    return notificationUnreadCountSchema.parse(data)
  }

  async unreadNotificationCount(): Promise<number> {
    const data = await centrifugoClient.rpc<unknown>('notification:unreadCount')
    return notificationUnreadCountSchema.parse(data).unread_count
  }

  async listWorkingAreas(): Promise<WorkingAreaOption[]> {
    const data = await centrifugoClient.rpc<unknown>('profile:areas')
    const parsed = workingAreaOptionListSchema.safeParse(Array.isArray(data) ? data : [])

    return parsed.success ? parsed.data : []
  }

  async updateProfile(payload: ProfileUpdatePayload): Promise<Profile> {
    const data = await centrifugoClient.rpc<unknown>('profile:update', payload)
    return profileSchema.parse(data)
  }
}

function zArray(data: unknown): Order[] {
  if (!Array.isArray(data)) {
    return []
  }

  return data.map((item) => orderSchema.parse(item))
}

export const realtimeService = new RealtimeService()
