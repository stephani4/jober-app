import { watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useOrderHistoryStore } from '@/stores/orderHistory'
import { useRealtimeStore } from '@/stores/realtime'

/**
 * История завершённых заказов автора: первая страница и догрузка.
 */
export function useOrderHistory() {
  const store = useOrderHistoryStore()
  const realtime = useRealtimeStore()
  const { items, loading, loadingMore, loaded, hasMore } = storeToRefs(store)
  const { status } = storeToRefs(realtime)

  watch(
    status,
    (value) => {
      if (value === 'connected' && !loaded.value) {
        void store.fetchFirst()
      }
    },
    { immediate: true },
  )

  return {
    items,
    loading,
    loadingMore,
    loaded,
    hasMore,
    loadMore: store.loadMore,
  }
}
