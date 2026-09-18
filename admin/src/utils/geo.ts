/** Координата карты: [долгота, широта]. */
export type LngLat = [number, number]

const EARTH_RADIUS_M = 6_371_000

function toRad(degrees: number): number {
  return (degrees * Math.PI) / 180
}

/** Расстояние по дуге большого круга, метры. */
export function haversineMeters(a: LngLat, b: LngLat): number {
  const dLat = toRad(b[1] - a[1])
  const dLon = toRad(b[0] - a[0])
  const lat1 = toRad(a[1])
  const lat2 = toRad(b[1])
  const h =
    Math.sin(dLat / 2) ** 2 + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLon / 2) ** 2
  return 2 * EARTH_RADIUS_M * Math.asin(Math.min(1, Math.sqrt(h)))
}

function toLocalMeters(point: LngLat, originLat: number): [number, number] {
  const cos = Math.cos(toRad(originLat))
  return [point[0] * 111_320 * cos, point[1] * 110_540]
}

function fromLocalMeters(xy: [number, number], originLat: number): LngLat {
  const cos = Math.cos(toRad(originLat))
  return [xy[0] / (111_320 * cos), xy[1] / 110_540]
}

function closestOnSegment(
  point: LngLat,
  start: LngLat,
  end: LngLat,
): { point: LngLat; distanceMeters: number } {
  const originLat = point[1]
  const p = toLocalMeters(point, originLat)
  const a = toLocalMeters(start, originLat)
  const b = toLocalMeters(end, originLat)
  const abx = b[0] - a[0]
  const aby = b[1] - a[1]
  const length2 = abx * abx + aby * aby
  const t =
    length2 === 0 ? 0 : Math.max(0, Math.min(1, ((p[0] - a[0]) * abx + (p[1] - a[1]) * aby) / length2))
  const projected: [number, number] = [a[0] + abx * t, a[1] + aby * t]
  const closest = fromLocalMeters(projected, originLat)
  return { point: closest, distanceMeters: haversineMeters(point, closest) }
}

/**
 * Ближайшая точка на ломаной и индекс сегмента (вершина начала сегмента).
 */
export function closestPointOnPolyline(
  point: LngLat,
  route: LngLat[],
): { point: LngLat; distanceMeters: number; segmentIndex: number } {
  if (route.length === 0) {
    return { point, distanceMeters: Infinity, segmentIndex: 0 }
  }
  if (route.length === 1) {
    return { point: route[0], distanceMeters: haversineMeters(point, route[0]), segmentIndex: 0 }
  }

  let best = {
    point: route[0],
    distanceMeters: Infinity,
    segmentIndex: 0,
  }

  for (let index = 0; index < route.length - 1; index += 1) {
    const hit = closestOnSegment(point, route[index], route[index + 1])
    if (hit.distanceMeters < best.distanceMeters) {
      best = { point: hit.point, distanceMeters: hit.distanceMeters, segmentIndex: index }
    }
  }

  return best
}

/**
 * Оставляет хвост маршрута от проекции текущей позиции до конца.
 */
export function trimRoute(
  route: LngLat[],
  position: LngLat,
): { remaining: LngLat[]; distanceMeters: number } {
  if (route.length === 0) {
    return { remaining: [], distanceMeters: Infinity }
  }
  if (route.length === 1) {
    return {
      remaining: [position, route[0]],
      distanceMeters: haversineMeters(position, route[0]),
    }
  }

  const closest = closestPointOnPolyline(position, route)
  const remaining: LngLat[] = [closest.point, ...route.slice(closest.segmentIndex + 1)]
  const cleaned: LngLat[] = []
  for (const coord of remaining) {
    const prev = cleaned.at(-1)
    if (!prev || prev[0] !== coord[0] || prev[1] !== coord[1]) {
      cleaned.push(coord)
    }
  }

  return {
    remaining: cleaned.length >= 2 ? cleaned : remaining,
    distanceMeters: closest.distanceMeters,
  }
}
