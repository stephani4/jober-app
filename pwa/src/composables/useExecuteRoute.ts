import { onBeforeUnmount, ref, watch, type Ref } from 'vue'
import { vkMapsRoutingService } from '@/services/VkMapsRoutingService'
import { trimRoute, type LngLat } from '@/utils/geo'
import type { GeoPosition } from '@/composables/useGeolocation'

const DEVIATION_METERS = 10
const REBUILD_COOLDOWN_MS = 4000

type Destination = { lat: number; lon: number }

/**
 * Строит маршрут VK Maps, обрезает пройденное и перестраивает при отклонении > 10 м.
 */
export function useExecuteRoute(
  destination: Ref<Destination | null>,
  position: Ref<GeoPosition | null>,
) {
  const remainingRoute = ref<LngLat[]>([])
  const loading = ref(false)
  const error = ref('')

  let fullRoute: LngLat[] = []
  let lastRebuildAt = 0
  let destKey = ''
  let abort: AbortController | null = null
  let requestId = 0

  async function fetchRoute(from: Destination, to: Destination): Promise<void> {
    const id = ++requestId
    abort?.abort()
    abort = new AbortController()
    loading.value = true
    error.value = ''
    try {
      const nextRoute = await vkMapsRoutingService.route(from, to, abort.signal)
      if (id !== requestId) {
        return
      }
      fullRoute = nextRoute
      lastRebuildAt = Date.now()
      remainingRoute.value = fullRoute
    } catch (err) {
      if (err instanceof DOMException && err.name === 'AbortError') {
        return
      }
      if (id !== requestId) {
        return
      }
      error.value = err instanceof Error ? err.message : 'Не удалось построить маршрут.'
    } finally {
      if (id === requestId) {
        loading.value = false
      }
    }
  }

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
        void fetchRoute(from, to)
        return
      }

      if (fullRoute.length < 2) {
        if (loading.value) {
          return
        }
        void fetchRoute(from, to)
        return
      }

      const trimmed = trimRoute(fullRoute, [from.lon, from.lat])
      remainingRoute.value = trimmed.remaining

      const noisyGps = from.accuracy > 25
      const cooledDown = Date.now() - lastRebuildAt > REBUILD_COOLDOWN_MS
      if (trimmed.distanceMeters > DEVIATION_METERS && cooledDown && !noisyGps) {
        void fetchRoute(from, to)
        return
      }

      if (trimmed.distanceMeters <= DEVIATION_METERS) {
        fullRoute = trimmed.remaining
      }
    },
    { immediate: true },
  )

  onBeforeUnmount(() => {
    abort?.abort()
  })

  return {
    remainingRoute,
    loading,
    error,
  }
}
