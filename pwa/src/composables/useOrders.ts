import { watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useOrdersStore } from '@/stores/orders'
import { useRealtimeStore } from '@/stores/realtime'

export function useOrders() {
  const store = useOrdersStore()
  const realtime = useRealtimeStore()
  const { items, responsesCount, availableExecutorsCount, loaded } = storeToRefs(store)
  const { status } = storeToRefs(realtime)

  watch(
    status,
    (value) => {
      if (value === 'connected' && !loaded.value) {
        void store.fetchMine()
        void store.fetchResponsesCount()
        void store.fetchAvailableExecutorsCount()
      }
    },
    { immediate: true },
  )

  return {
    items,
    responsesCount,
    availableExecutorsCount,
    loaded,
    fetchMine: () => store.fetchMine(),
    fetchResponsesCount: () => store.fetchResponsesCount(),
    fetchAvailableExecutorsCount: () => store.fetchAvailableExecutorsCount(),
    upsert: store.upsert,
  }
}
