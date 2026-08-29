import { realtimeService } from '@/services/RealtimeService'
import type { OrderMessage } from '@/schemas/orderMessage'

/**
 * Чат заказа: список и отправка через Centrifugo RPC.
 */
export class OrderMessageService {
  list(orderId: number): Promise<OrderMessage[]> {
    return realtimeService.listOrderMessages(orderId)
  }

  send(orderId: number, body: string): Promise<OrderMessage> {
    return realtimeService.sendOrderMessage(orderId, body)
  }
}

export const orderMessageService = new OrderMessageService()
