import { watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useAuth } from '@/composables/useAuth'
import { useRealtimeStore } from '@/stores/realtime'
import { useOrderStatsStore } from '@/stores/orderStats'

/**
 * Статистика исполнителя для блока «Обзор»: заказы, выполненные сегодня.
 * Подгружается один раз за сессию после подключения к realtime.
 */
export function useOrderStats() {
  const store = useOrderStatsStore()
  const realtime = useRealtimeStore()
  const { hasRole } = useAuth()
  const { completedToday, loaded } = storeToRefs(store)
  const { status } = storeToRefs(realtime)

  watch(
    status,
    (value) => {
      if (value === 'connected' && !loaded.value && hasRole(['executor'])) {
        void store.fetchCompletedToday()
      }
    },
    { immediate: true },
  )

  return {
    completedToday,
    loaded,
    refresh: () => store.fetchCompletedToday(),
    bump: store.bumpCompletedToday,
  }
}