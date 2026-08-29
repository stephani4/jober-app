import { watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useOrdersStore } from '@/stores/orders'
import { useRealtimeStore } from '@/stores/realtime'

export function useOrders() {
  const store = useOrdersStore()
  const realtime = useRealtimeStore()
  const { items, loaded } = storeToRefs(store)
  const { status } = storeToRefs(realtime)

  watch(
    status,
    (value) => {
      if (value === 'connected' && !loaded.value) {
        void store.fetchMine()
      }
    },
    { immediate: true },
  )

  return {
    items,
    loaded,
    fetchMine: () => store.fetchMine(),
    upsert: store.upsert,
  }
}
