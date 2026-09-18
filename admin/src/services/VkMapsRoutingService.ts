import { vkMapsApiKey, vkMapsApiOrigin } from '@/config/vkMaps'
import { decodePolyline } from '@/utils/polyline'
import type { LngLat } from '@/utils/geo'

type DirectionsResponse = {
  trips?: Array<{
    trip?: {
      legs?: Array<{ shape?: string }>
    }
  }>
  trip?: {
    legs?: Array<{ shape?: string }>
  }
}

/**
 * Построение маршрута через VK Maps Directions API.
 */
export class VkMapsRoutingService {
  /**
   * Маршрут от точки до точки. При ошибке API возвращает прямую линию.
   */
  async route(
    from: { lat: number; lon: number },
    to: { lat: number; lon: number },
    signal?: AbortSignal,
  ): Promise<LngLat[]> {
    const fallback: LngLat[] = [
      [from.lon, from.lat],
      [to.lon, to.lat],
    ]

    try {
      const url = `${vkMapsApiOrigin}/directions?api_key=${encodeURIComponent(vkMapsApiKey())}`
      const response = await fetch(url, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          locations: [
            { lat: from.lat, lon: from.lon },
            { lat: to.lat, lon: to.lon },
          ],
          costing: 'auto',
          language: 'ru-RU',
          units: 'kilometers',
          directions_type: 'none',
        }),
        signal,
      })

      if (!response.ok) {
        return fallback
      }

      const json = (await response.json()) as DirectionsResponse
      const shapes = this.collectShapes(json)
      const decoded = shapes.flatMap((shape) => decodePolyline(shape))
      return decoded.length >= 2 ? decoded : fallback
    } catch (error) {
      if (error instanceof DOMException && error.name === 'AbortError') {
        throw error
      }
      return fallback
    }
  }

  private collectShapes(payload: DirectionsResponse): string[] {
    const legs =
      payload.trips?.[0]?.trip?.legs ??
      payload.trip?.legs ??
      []

    return legs.map((leg) => leg.shape).filter((shape): shape is string => Boolean(shape))
  }
}

export const vkMapsRoutingService = new VkMapsRoutingService()
