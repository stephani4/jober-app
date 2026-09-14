import { defineStore } from 'pinia'
import { ref } from 'vue'
import { adminOrderService, type OrderListFilters } from '@/services/AdminOrderService'
import type { Order } from '@/schemas/order'

export const useOrdersStore = defineStore('orders', () => {
  const items = ref<Order[]>([])
  const nextCursor = ref<number | null>(null)
  const loading = ref(false)
  const loadingMore = ref(false)
  const filters = ref<OrderListFilters>({ status: 'moderate' })

  /** Загружает первую страницу с новыми (или текущими) фильтрами. */
  async function fetchFirst(next?: OrderListFilters): Promise<void> {
    if (next !== undefined) {
      filters.value = next
    }
    loading.value = true
    try {
      const page = await adminOrderService.list(filters.value)
      items.value = page.items
      nextCursor.value = page.next_cursor
    } finally {
      loading.value = false
    }
  }

  /** Догружает следующую страницу (по 15 записей). */
  async function loadMore(): Promise<void> {
    if (nextCursor.value == null || loadingMore.value || loading.value) {
      return
    }
    loadingMore.value = true
    try {
      const page = await adminOrderService.list(filters.value, nextCursor.value)
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
    filters,
    fetchFirst,
    loadMore,
    replace,
  }
})
