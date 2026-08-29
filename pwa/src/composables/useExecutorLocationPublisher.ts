import { onBeforeUnmount, watch, type Ref } from 'vue'
import { haversineMeters } from '@/utils/geo'
import { orderService } from '@/services/OrderService'
import type { GeoPosition } from '@/composables/useGeolocation'
import type { OrderExecuting } from '@/schemas/order'

const MIN_INTERVAL_MS = 2000
const MIN_DISTANCE_M = 8

/**
 * Шлёт координаты исполнителя на сервер (Centrifugo RPC), чтобы автор видел движение.
 */
export function useExecutorLocationPublisher(
  orderId: Ref<number | null>,
  executing: Ref<OrderExecuting | null>,
  position: Ref<GeoPosition | null>,
) {
  let lastSent: { at: number; lat: number; lon: number } | null = null
  let inflight = false

  watch(
    [orderId, executing, position],
    ([id, current, from]) => {
      if (!id || current?.status !== 'process' || !from) {
        return
      }

      const now = Date.now()
      if (lastSent) {
        const elapsed = now - lastSent.at
        const moved = haversineMeters([lastSent.lon, lastSent.lat], [from.lon, from.lat])
        if (elapsed < MIN_INTERVAL_MS && moved < MIN_DISTANCE_M) {
          return
        }
      }

      if (inflight) {
        return
      }

      lastSent = { at: now, lat: from.lat, lon: from.lon }
      inflight = true
      void orderService
        .publishLocation(id, { lat: from.lat, lon: from.lon })
        .catch(() => {
          lastSent = null
        })
        .finally(() => {
          inflight = false
        })
    },
    { immediate: true },
  )

  onBeforeUnmount(() => {
    lastSent = null
  })
}
