import { computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useSearchOrdersStore } from '@/stores/searchOrders'
import { useRealtimeStore } from '@/stores/realtime'
import { useAuth } from '@/composables/useAuth'

export function useSearchOrders() {
  const store = useSearchOrdersStore()
  const realtime = useRealtimeStore()
  const { user, hasRole } = useAuth()
  const { items, loaded } = storeToRefs(store)
  const { status } = storeToRefs(realtime)

  watch(
    status,
    (value) => {
      if (value === 'connected' && !loaded.value && hasRole(['executor'])) {
        void store.fetchFeed()
      }
    },
    { immediate: true },
  )

  /** Заказы в поиске, на которые текущий исполнитель может откликнуться. */
  const availableCount = computed(
    () => items.value.filter((order) => order.status === 'wait' && order.user_id !== user.value?.id).length,
  )

  return {
    items,
    loaded,
    availableCount,
    fetchFeed: () => store.fetchFeed(),
    upsert: store.upsert,
  }
}
