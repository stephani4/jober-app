import { defineStore } from 'pinia'
import { ref } from 'vue'
import { adminOrderService } from '@/services/AdminOrderService'
import type { Order, OrderStatus } from '@/schemas/order'

export const useOrdersStore = defineStore('orders', () => {
  const items = ref<Order[]>([])
  const nextCursor = ref<number | null>(null)
  const loading = ref(false)
  const loadingMore = ref(false)
  const status = ref<OrderStatus | 'all'>('moderate')

  async function fetchFirst(nextStatus: OrderStatus | 'all' = status.value): Promise<void> {
    status.value = nextStatus
    loading.value = true
    try {
      const page = await adminOrderService.list(nextStatus)
      items.value = page.items
      nextCursor.value = page.next_cursor
    } finally {
      loading.value = false
    }
  }

  async function loadMore(): Promise<void> {
    if (nextCursor.value == null || loadingMore.value || loading.value) {
      return
    }
    loadingMore.value = true
    try {
      const page = await adminOrderService.list(status.value, nextCursor.value)
      const known = new Set(items.value.map((item) => item.id))
      items.value = [...items.value, ...page.items.filter((item) => !known.has(item.id))]
      nextCursor.value = page.next_cursor
    } finally {
      loadingMore.value = false
    }
  }

  function replace(order: Order): void {
    items.value = items.value.filter((item) => item.id !== order.id)
  }

  return {
    items,
    nextCursor,
    loading,
    loadingMore,
    status,
    fetchFirst,
    loadMore,
    replace,
  }
})
