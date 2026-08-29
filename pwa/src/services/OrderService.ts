import { realtimeService } from '@/services/RealtimeService'
import type { ActiveExecution, CreateOrderPayload, Order, OrderExecuting, OrderHistoryList } from '@/schemas/order'

/**
 * Сервис заказов: все запросы идут через Centrifugo RPC.
 */
export class OrderService {
  create(payload: CreateOrderPayload): Promise<Order> {
    return realtimeService.createOrder(payload)
  }

  listMine(): Promise<Order[]> {
    return realtimeService.listMine()
  }

  listHistory(cursor?: number | null): Promise<OrderHistoryList> {
    return realtimeService.listHistory(cursor)
  }

  listFeed(): Promise<Order[]> {
    return realtimeService.listFeed()
  }

  start(orderId: number): Promise<OrderExecuting> {
    return realtimeService.startOrder(orderId)
  }

  executing(orderId: number): Promise<OrderExecuting> {
    return realtimeService.getExecuting(orderId)
  }

  watching(orderId: number): Promise<OrderExecuting> {
    return realtimeService.getWatching(orderId)
  }

  active(): Promise<ActiveExecution> {
    return realtimeService.getActive()
  }

  publishLocation(orderId: number, position: { lat: number; lon: number }): Promise<void> {
    return realtimeService.publishLocation(orderId, position)
  }

  completePoint(orderId: number, orderPointId: number): Promise<OrderExecuting> {
    return realtimeService.completePoint(orderId, orderPointId)
  }
}

export const orderService = new OrderService()
