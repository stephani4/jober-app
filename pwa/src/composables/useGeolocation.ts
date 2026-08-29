import { onBeforeUnmount, onMounted, ref } from 'vue'

export type GeoPosition = {
  lat: number
  lon: number
  accuracy: number
}

/**
 * Следит за позицией исполнителя через Geolocation API.
 * В DEV можно перехватить точку вручную (перетаскивание на карте).
 */
export function useGeolocation() {
  const position = ref<GeoPosition | null>(null)
  const error = ref('')
  const simulated = ref(false)
  let watchId: number | null = null

  /**
   * Подменяет GPS-позицию — дальше watchPosition её не перезаписывает.
   */
  function applySimulated(next: { lat: number; lon: number; accuracy?: number }): void {
    simulated.value = true
    error.value = ''
    position.value = {
      lat: next.lat,
      lon: next.lon,
      accuracy: next.accuracy ?? 8,
    }
  }

  function start(): void {
    if (!navigator.geolocation) {
      error.value = 'Геолокация недоступна в этом браузере.'
      return
    }

    watchId = navigator.geolocation.watchPosition(
      (next) => {
        if (simulated.value) {
          return
        }
        error.value = ''
        position.value = {
          lat: next.coords.latitude,
          lon: next.coords.longitude,
          accuracy: next.coords.accuracy,
        }
      },
      (err) => {
        if (simulated.value) {
          return
        }
        error.value =
          err.code === err.PERMISSION_DENIED
            ? 'Разрешите доступ к геолокации, чтобы построить маршрут.'
            : 'Не удалось определить местоположение.'
      },
      { enableHighAccuracy: true, maximumAge: 1000, timeout: 15000 },
    )
  }

  function stop(): void {
    if (watchId != null) {
      navigator.geolocation.clearWatch(watchId)
      watchId = null
    }
  }

  onMounted(start)
  onBeforeUnmount(stop)

  return {
    position,
    error,
    simulated,
    applySimulated,
    start,
    stop,
  }
}
