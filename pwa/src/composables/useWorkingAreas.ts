import { ref } from 'vue'
import { realtimeService } from '@/services/RealtimeService'
import type { WorkingArea } from '@/schemas/workingArea'
import { parseWktPolygon, pointInPolygon, type LngLat } from '@/utils/geo'

/**
 * Рабочая зона с уже разобранным контуром для проверки попадания точек.
 */
export interface WorkingAreaZone {
  id: number
  name: string
  polygon: LngLat[]
}

/** Кэш запроса списка зон на уровне модуля: зоны меняются редко. */
let zonesPromise: ReturnType<typeof realtimeService.listWorkingAreaZones> | null = null

/**
 * Контур зоны: предпочитаем `points` (JSON-массив [lng, lat]),
 * иначе разбираем `geometry` (WKT).
 */
function polygonOf(area: WorkingArea): LngLat[] | null {
  if (area.points) {
    try {
      const raw = JSON.parse(area.points) as unknown
      if (Array.isArray(raw) && raw.length >= 3) {
        const coords: LngLat[] = raw.map((vertex) => [
          Number((vertex as [number, number])[0]),
          Number((vertex as [number, number])[1]),
        ])
        if (coords.every(([lng, lat]) => Number.isFinite(lng) && Number.isFinite(lat))) {
          return coords
        }
      }
    } catch {
      // Невалидный points — пробуем WKT.
    }
  }
  if (area.geometry) {
    const wkt = parseWktPolygon(area.geometry)
    if (wkt && wkt.length >= 3) {
      return wkt
    }
  }
  return null
}

/**
 * Рабочие зоны: загрузка списка и проверка попадания точки в любую из них.
 * Список зон кэшируется на уровне модуля, повторные вызовы `load()` недороги.
 */
export function useWorkingAreas() {
  const zones = ref<WorkingAreaZone[]>([])
  const loading = ref(false)

  async function load(): Promise<void> {
    if (zones.value.length > 0) {
      return
    }
    if (zonesPromise === null) {
      zonesPromise = realtimeService.listWorkingAreaZones().catch((err) => {
        zonesPromise = null
        throw err
      })
    }
    loading.value = true
    try {
      const areas = await zonesPromise
      zones.value = areas
        .map((area) => {
          const polygon = polygonOf(area)
          return polygon ? { id: area.id, name: area.name, polygon } : null
        })
        .filter((zone): zone is WorkingAreaZone => zone !== null)
    } finally {
      loading.value = false
    }
  }

  /**
   * Попадает ли точка в одну из загруженных зон.
   * Если зон нет (не настроены или не загрузились) — точка считается допустимой.
   */
  function isInside(lat: number, lon: number): boolean {
    if (zones.value.length === 0) {
      return true
    }
    return zones.value.some((zone) => pointInPolygon([lon, lat], zone.polygon))
  }

  return {
    zones,
    loading,
    load,
    isInside,
  }
}