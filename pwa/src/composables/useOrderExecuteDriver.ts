import { computed, onBeforeUnmount, watch, type Ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useOrderExecuteStore } from '@/stores/orderExecute'
import { useGeolocation } from '@/composables/useGeolocation'
import { useExecuteRoute } from '@/composables/useExecuteRoute'
import { useExecutorLocationPublisher } from '@/composables/useExecutorLocationPublisher'

/**
 * Driver экрана выполнения: текущая точка, геолокация, маршрут и завершение шага.
 */
export function useOrderExecuteDriver(orderId: Ref<number | null>) {
  const store = useOrderExecuteStore()
  const router = useRouter()
  const { executing, loading, submitting, error } = storeToRefs(store)
  const { position, error: geoError, applySimulated } = useGeolocation()
  const simulateGeo = import.meta.env.DEV

  const currentPoint = computed(() => {
    const points = executing.value?.points ?? []
    return points.find((point) => point.status === 'process') ?? null
  })

  const stepIndex = computed(() => {
    const points = executing.value?.points ?? []
    const current = currentPoint.value
    if (!current) {
      return 0
    }
    return points.findIndex((point) => point.id === current.id)
  })

  const isLast = computed(() => {
    const points = executing.value?.points ?? []
    if (!currentPoint.value || points.length === 0) {
      return false
    }
    return stepIndex.value === points.length - 1
  })

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

  useExecutorLocationPublisher(orderId, executing, position)

  let seedTimer: ReturnType<typeof setTimeout> | null = null

  function seedNearDestination(to: { lat: number; lon: number }): void {
    if (!simulateGeo || position.value) {
      return
    }
    applySimulated({ lat: to.lat + 0.0018, lon: to.lon - 0.0012 })
  }

  watch(
    orderId,
    (id) => {
      if (id) {
        void store.start(id)
      } else {
        store.reset()
      }
    },
    { immediate: true },
  )

  // Если GPS нет (ошибка или таймаут) — ставим точку рядом с заказом, чтобы её тащить мышью.
  watch(
    [destination, geoError],
    ([to, err]) => {
      if (!simulateGeo || !to || position.value) {
        return
      }
      if (err) {
        seedNearDestination(to)
        return
      }
      if (seedTimer) {
        clearTimeout(seedTimer)
      }
      seedTimer = setTimeout(() => seedNearDestination(to), 4000)
    },
    { immediate: true },
  )

  onBeforeUnmount(() => {
    if (seedTimer) {
      clearTimeout(seedTimer)
    }
    store.reset()
  })

  async function completeCurrent(): Promise<void> {
    const ok = await store.completeCurrentPoint()
    if (ok && executing.value?.status === 'complete') {
      await router.replace({ name: 'orders' })
    }
  }

  return {
    executing,
    loading,
    submitting,
    error,
    geoError,
    routeError,
    routeLoading,
    position,
    remainingRoute,
    currentPoint,
    stepIndex,
    isLast,
    destination,
    simulateGeo,
    applySimulated,
    completeCurrent,
  }
}
