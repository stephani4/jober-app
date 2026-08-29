import type { LngLat } from '@/utils/geo'

/**
 * Декодирует encoded polyline (Valhalla / VK Maps, precision 6) в координаты [lon, lat].
 */
export function decodePolyline(encoded: string, precision = 6): LngLat[] {
  const factor = 10 ** precision
  let index = 0
  let lat = 0
  let lon = 0
  const coordinates: LngLat[] = []

  while (index < encoded.length) {
    let result = 0
    let shift = 0
    let byte = 0
    do {
      byte = encoded.charCodeAt(index++) - 63
      result |= (byte & 0x1f) << shift
      shift += 5
    } while (byte >= 0x20)
    lat += result & 1 ? ~(result >> 1) : result >> 1

    result = 0
    shift = 0
    do {
      byte = encoded.charCodeAt(index++) - 63
      result |= (byte & 0x1f) << shift
      shift += 5
    } while (byte >= 0x20)
    lon += result & 1 ? ~(result >> 1) : result >> 1

    coordinates.push([lon / factor, lat / factor])
  }

  return coordinates
}
