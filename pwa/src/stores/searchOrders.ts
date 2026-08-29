import { defineStore } from 'pinia'
import { ref } from 'vue'
import { orderService } from '@/services/OrderService'
import type { Order } from '@/schemas/order'

function upsertOrder(list: Order[], order: Order): Order[] {
  const index = list.findIndex((item) => item.id === order.id)
  if (index === -1) {
    return [order, ...list]
  }

  const next = [...list]
  next[index] = order
  return next
}

export const useSearchOrdersStore = defineStore('searchOrders', () => {
  const items = ref<Order[]>([])
  const loaded = ref(false)

  function upsert(order: Order): void {
    if (order.status === 'wait') {
      items.value = upsertOrder(items.value, order)
      return
    }
    items.value = items.value.filter((item) => item.id !== order.id)
  }

  function remove(orderId: number): void {
    items.value = items.value.filter((item) => item.id !== orderId)
  }

  async function fetchFeed(): Promise<void> {
    items.value = await orderService.listFeed()
    loaded.value = true
  }

  function reset(): void {
    items.value = []
    loaded.value = false
  }

  return {
    items,
    loaded,
    upsert,
    remove,
    fetchFeed,
    reset,
  }
})
