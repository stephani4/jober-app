import { computed, onBeforeUnmount, ref, watch, type Ref } from 'vue'
import { orderService } from '@/services/OrderService'
import { realtimeService } from '@/services/RealtimeService'
import { useExecuteRoute } from '@/composables/useExecuteRoute'
import type { GeoPosition } from '@/composables/useGeolocation'
import type { OrderExecuting } from '@/schemas/order'

/**
 * Наблюдение автора за исполнителем: снимок, координаты и маршрут в realtime.
 */
export function useOrderWatchingDriver(orderId: Ref<number | null>) {
  const executing = ref<OrderExecuting | null>(null)
  const position = ref<GeoPosition | null>(null)
  const loading = ref(false)
  const error = ref('')

  const currentPoint = computed(() => {
    const points = executing.value?.points ?? []
    return points.find((point) => point.status === 'process') ?? null
  })

  /** Этап подтверждения кодом: все точки пройдены, заказ ждёт проверки автором. */
  const awaitingConfirmation = computed(() => executing.value?.status === 'confirmation')

  /** Код подтверждения виден только автору и только на этапе confirmation. */
  const confirmationNumber = computed(() =>
    awaitingConfirmation.value ? executing.value?.order?.confirmation_number ?? null : null,
  )

  const destination = computed(() => {
    const point = currentPoint.value?.order_point
    if (point?.lat == null || point.lon == null) {
      return null
    }
    return { lat: point.lat, lon: point.lon }
  })

  const { remainingRoute, loading: routeLoading, error: routeError } = useExecuteRoute(
    destination,
    position,
  )

  function applyLocation(lat: number, lon: number): void {
    position.value = { lat, lon, accuracy: 8 }
  }

  async function load(id: number): Promise<void> {
    loading.value = true
    error.value = ''
    executing.value = null
    position.value = null
    try {
      const snapshot = await orderService.watching(id)
      executing.value = snapshot
      if (snapshot.lat != null && snapshot.lon != null) {
        applyLocation(snapshot.lat, snapshot.lon)
      }
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Не удалось открыть наблюдение.'
    } finally {
      loading.value = false
    }
  }

  watch(
    orderId,
    (id) => {
      if (id) {
        void load(id)
        return
      }
      executing.value = null
      position.value = null
      error.value = ''
    },
    { immediate: true },
  )

  const stopLocation = realtimeService.onExecutorLocation((event) => {
    if (event.order_id !== orderId.value) {
      return
    }
    applyLocation(event.lat, event.lon)
  })

  const stopExecuting = realtimeService.onOrderExecuting((event) => {
    if (event.executing.order_id !== orderId.value) {
      return
    }
    executing.value = event.executing
    if (event.executing.status === 'complete') {
      error.value = 'Заказ исполнен.'
    }
  })

  const stopStatus = realtimeService.onOrderStatus((event) => {
    if (event.order.id !== orderId.value) {
      return
    }
    if (event.order.status === 'cancel') {
      error.value = 'Заказ отменён заказчиком.'
    }
  })

  onBeforeUnmount(() => {
    stopLocation()
    stopExecuting()
    stopStatus()
  })

  return {
    executing,
    position,
    remainingRoute,
    currentPoint,
    destination,
    awaitingConfirmation,
    confirmationNumber,
    loading,
    error,
    routeError,
    routeLoading,
  }
}
