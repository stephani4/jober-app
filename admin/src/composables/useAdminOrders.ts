import { storeToRefs } from 'pinia'
import { useOrdersStore } from '@/stores/orders'
import { adminOrderService } from '@/services/AdminOrderService'
import type { OrderStatus } from '@/schemas/order'

/**
 * Список заказов и действия модерации.
 */
export function useAdminOrders() {
  const store = useOrdersStore()
  const { items, loading, loadingMore, nextCursor, status } = storeToRefs(store)

  async function approve(orderId: number): Promise<void> {
    const order = await adminOrderService.approve(orderId)
    store.replace(order)
  }

  async function cancel(orderId: number, reason: string): Promise<void> {
    const order = await adminOrderService.cancel(orderId, reason)
    store.replace(order)
  }

  return {
    items,
    loading,
    loadingMore,
    nextCursor,
    status,
    fetchFirst: (next?: OrderStatus | 'all') => store.fetchFirst(next),
    loadMore: () => store.loadMore(),
    approve,
    cancel,
  }
}
