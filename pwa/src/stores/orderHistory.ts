import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { orderService } from '@/services/OrderService'
import type { Order } from '@/schemas/order'

export const useOrderHistoryStore = defineStore('orderHistory', () => {
  const items = ref<Order[]>([])
  const nextCursor = ref<number | null>(null)
  const loading = ref(false)
  const loadingMore = ref(false)
  const loaded = ref(false)

  const hasMore = computed(() => nextCursor.value != null)

  function ingest(order: Order): void {
    if (order.status !== 'complete' && order.status !== 'cancel') {
      return
    }
    if (items.value.some((item) => item.id === order.id)) {
      items.value = items.value.map((item) => (item.id === order.id ? order : item))
      return
    }
    items.value = [order, ...items.value]
  }

  async function fetchFirst(): Promise<void> {
    loading.value = true
    try {
      const page = await orderService.listHistory()
      items.value = page.items
      nextCursor.value = page.next_cursor
      loaded.value = true
    } catch {
      loaded.value = true
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
      const page = await orderService.listHistory(nextCursor.value)
      const known = new Set(items.value.map((item) => item.id))
      items.value = [...items.value, ...page.items.filter((item) => !known.has(item.id))]
      nextCursor.value = page.next_cursor
    } catch {
      // оставляем уже загруженный список
    } finally {
      loadingMore.value = false
    }
  }

  function reset(): void {
    items.value = []
    nextCursor.value = null
    loading.value = false
    loadingMore.value = false
    loaded.value = false
  }

  return {
    items,
    nextCursor,
    loading,
    loadingMore,
    loaded,
    hasMore,
    ingest,
    fetchFirst,
    loadMore,
    reset,
  }
})
