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
  const responsesCount = ref(0)
  const availableExecutorsCount = ref(0)
  const loaded = ref(false)
  const cancellingId = ref<number | null>(null)

  function upsert(order: Order): void {
    if (order.status === 'complete' || order.status === 'cancel') {
      items.value = items.value.filter((item) => item.id !== order.id)
      return
    }
    items.value = upsertOrder(items.value, order)
  }

  async function cancel(orderId: number): Promise<void> {
    cancellingId.value = orderId
    try {
      const order = await orderService.cancel(orderId)
      upsert(order)
    } finally {
      cancellingId.value = null
    }
  }

  async function fetchMine(): Promise<void> {
    items.value = await orderService.listMine()
    loaded.value = true
  }

  async function fetchResponsesCount(): Promise<void> {
    responsesCount.value = await orderService.getResponsesCount()
  }

  async function fetchAvailableExecutorsCount(): Promise<void> {
    availableExecutorsCount.value = await orderService.getAvailableExecutorsCount()
  }

  function reset(): void {
    items.value = []
    responsesCount.value = 0
    availableExecutorsCount.value = 0
    loaded.value = false
  }

  return {
    items,
    responsesCount,
    availableExecutorsCount,
    loaded,
    cancellingId,
    upsert,
    cancel,
    fetchMine,
    fetchResponsesCount,
    fetchAvailableExecutorsCount,
    reset,
  }
})
