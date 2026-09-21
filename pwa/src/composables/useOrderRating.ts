import { ref } from 'vue'
import { orderService } from '@/services/OrderService'
import { useOrderHistoryStore } from '@/stores/orderHistory'
import { useOrdersStore } from '@/stores/orders'
import type { Order } from '@/schemas/order'

/**
 * Оценка автором качества выполнения (1–5). Обновляет списки заказов и историю.
 */
export function useOrderRating() {
  const busy = ref(false)

  async function rate(orderId: number, rating: number): Promise<Order> {
    busy.value = true
    try {
      const order = await orderService.rate(orderId, rating)
      useOrdersStore().upsert(order)
      useOrderHistoryStore().ingest(order)
      return order
    } finally {
      busy.value = false
    }
  }

  return {
    busy,
    rate,
  }
}
