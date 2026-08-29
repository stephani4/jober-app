import { watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useSearchOrdersStore } from '@/stores/searchOrders'
import { useRealtimeStore } from '@/stores/realtime'

export function useSearchOrders() {
  const store = useSearchOrdersStore()
  const realtime = useRealtimeStore()
  const { items, loaded } = storeToRefs(store)
  const { status } = storeToRefs(realtime)

  watch(
    status,
    (value) => {
      if (value === 'connected') {
        void store.fetchFeed()
      }
    },
    { immediate: true },
  )

  return {
    items,
    loaded,
    fetchFeed: () => store.fetchFeed(),
    upsert: store.upsert,
  }
}
