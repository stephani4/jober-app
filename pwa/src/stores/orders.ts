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

export const useOrdersStore = defineStore('orders', () => {
  const items = ref<Order[]>([])
  const loaded = ref(false)

  function upsert(order: Order): void {
    if (order.status === 'complete' || order.status === 'cancel') {
      items.value = items.value.filter((item) => item.id !== order.id)
      return
    }
    items.value = upsertOrder(items.value, order)
  }

  async function fetchMine(): Promise<void> {
    items.value = await orderService.listMine()
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
    fetchMine,
    reset,
  }
})
