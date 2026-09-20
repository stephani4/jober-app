import { ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useOrdersStore } from '@/stores/orders'
import { adminOrderService, type OrderListFilters } from '@/services/AdminOrderService'
import { adminOrderTypeService } from '@/services/AdminOrderTypeService'
import type { OrderType } from '@/schemas/order'
import { Permission } from '@/permissions'
import { withPermission } from '@/middleware/permission'

/**
 * Список заказов: фильтры, пагинация и действия модерации.
 */
export function useAdminOrders() {
  const store = useOrdersStore()
  const { items, loading, loadingMore, nextCursor, filters } = storeToRefs(store)

  /** Справочник видов заказа для выпадающего списка фильтра. */
  const types = ref<OrderType[]>([])

  async function loadTypes(): Promise<void> {
    if (types.value.length > 0) {
      return
    }
    types.value = (await adminOrderTypeService.list()).types
  }

  const approve = withPermission(Permission.OrdersApprove, async (orderId: number) => {
    const order = await adminOrderService.approve(orderId)
    store.replace(order)
  })

  const cancel = withPermission(Permission.OrdersCancel, async (orderId: number, reason: string) => {
    const order = await adminOrderService.cancel(orderId, reason)
    store.replace(order)
  })

  return {
    items,
    loading,
    loadingMore,
    nextCursor,
    filters,
    types,
    loadTypes,
    fetchFirst: (next?: OrderListFilters) => store.fetchFirst(next),
    loadMore: () => store.loadMore(),
    approve,
    cancel,
  }
}
