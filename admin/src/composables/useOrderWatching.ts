import { computed, onBeforeUnmount, ref, watch, type Ref } from 'vue'
import { adminOrderService } from '@/services/AdminOrderService'
import { centrifugoClient } from '@/services/CentrifugoClient'
import { vkMapsRoutingService } from '@/services/VkMapsRoutingService'
import { trimRoute, type LngLat } from '@/utils/geo'
import type { OrderExecuting } from '@/schemas/order'

const DEVIATION_METERS = 15
const REBUILD_COOLDOWN_MS = 4000

type Destination = { lat: number; lon: number }

/**
 * Наблюдение за исполнителем в админке: снимок выполнения,
 * координаты из Centrifugo и оставшийся маршрут VK Maps.
 */
export function useOrderWatching(orderId: Ref<number | null>) {
  const executing = ref<OrderExecuting | null>(null)
  const position = ref<Destination | null>(null)
  const remainingRoute = ref<LngLat[]>([])
  const loading = ref(false)
  const error = ref('')

  const executor = computed(() => executing.value?.executor ?? null)

  /** Текущая точка маршрута исполнителя. */
  const currentPoint = computed(
    () => (executing.value?.points ?? []).find((point) => point.status === 'process') ?? null,
  )

  const destination = computed<Destination | null>(() => {
    const point = currentPoint.value?.order_point
    if (point?.lat == null || point.lon == null) {
      return null
    }
    return { lat: point.lat, lon: point.lon }
  })

  async function load(id: number): Promise<void> {
    loading.value = true
    error.value = ''
    executing.value = null
    position.value = null
    try {
      const snapshot = await adminOrderService.executing(id)
      executing.value = snapshot
      if (snapshot?.lat != null && snapshot.lon != null) {
        position.value = { lat: snapshot.lat, lon: snapshot.lon }
      }
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Не удалось загрузить выполнение.'
    } finally {
      loading.value = false
    }
  }

  watch(
    orderId,
    (id) => {
      if (!id) {
        centrifugoClient.disconnect()
        executing.value = null
        position.value = null
        error.value = ''
        return
      }

      void load(id)
      void centrifugoClient
        .connect(() => adminOrderService.realtimeToken(id))
        .then(() => {
          const stop = centrifugoClient.onPublication((_channel, data) => {
            if (!data || typeof data !== 'object' || !('type' in data)) {
              return
            }

            const payload = data as { type: string; order_id?: number; lat?: number; lon?: number }

            if (payload.type === 'executor.location' && payload.order_id === orderId.value) {
              position.value = { lat: Number(payload.lat), lon: Number(payload.lon) }
            }

            if (payload.type === 'order.executing') {
              void load(orderId.value as number)
            }
          })
          stopHandlers.push(stop)
        })
        .catch(() => {
          // Без realtime наблюдение работает по снимку с сервера.
        })
    },
    { immediate: true },
  )

  // Оставшийся маршрут: строится от позиции исполнителя до текущей точки.
  let fullRoute: LngLat[] = []
  let lastRebuildAt = 0
  let destKey = ''

  watch(
    [destination, position],
    ([to, from]) => {
      if (!to) {
        remainingRoute.value = []
        fullRoute = []
        destKey = ''
        return
      }

      if (!from) {
        return
      }

      const key = `${to.lat.toFixed(6)},${to.lon.toFixed(6)}`
      if (key !== destKey) {
        destKey = key
        fullRoute = []
        void rebuildRoute(from, to)
        return
      }

      if (fullRoute.length < 2) {
        void rebuildRoute(from, to)
        return
      }

      const trimmed = trimRoute(fullRoute, [from.lon, from.lat])
      remainingRoute.value = trimmed.remaining

      const cooledDown = Date.now() - lastRebuildAt > REBUILD_COOLDOWN_MS
      if (trimmed.distanceMeters > DEVIATION_METERS && cooledDown) {
        void rebuildRoute(from, to)
        return
      }

      if (trimmed.distanceMeters <= DEVIATION_METERS) {
        fullRoute = trimmed.remaining
      }
    },
    { immediate: true },
  )

  async function rebuildRoute(from: Destination, to: Destination): Promise<void> {
    try {
      fullRoute = await vkMapsRoutingService.route(from, to)
      lastRebuildAt = Date.now()
      remainingRoute.value = fullRoute
    } catch {
      // Маршрут не обязателен: карта продолжает показывать маркеры.
    }
  }

  const stopHandlers: (() => void)[] = []

  onBeforeUnmount(() => {
    stopHandlers.splice(0).forEach((stop) => stop())
    centrifugoClient.disconnect()
  })

  return {
    executing,
    executor,
    position,
    remainingRoute,
    currentPoint,
    destination,
    loading,
    error,
  }
}
