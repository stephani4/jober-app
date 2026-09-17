import { vkMapsApiKey, vkMapsApiOrigin } from '@/config/vkMaps'

export type VkMapsLatLon = {
  lat: number
  lon: number
}

export type VkMapsAddressDetails = {
  /** Город/населённый пункт. */
  locality?: string
  /** Улица. */
  street?: string
  /** Номер дома. */
  building?: string
}

export type VkMapsSuggestItem = {
  name: string | null
  address: string | null
  type: string | null
  pin: VkMapsLatLon | null
  details: VkMapsAddressDetails | null
}

export type VkMapsSearchOptions = {
  location?: VkMapsLatLon | null
  signal?: AbortSignal
  /** Режим «только здания»: отсекает улицы, города и районы из результатов поиска. */
  kind?: 'buildings'
}

/** Типы, которые не являются зданием и не могут быть точкой доставки. */
const NON_BUILDING_TYPES = new Set([
  'street',
  'city',
  'neighbourhood',
  'locality',
  'area',
  'district',
  'region',
  'province',
  'country',
  'territory',
  'airport',
  'station',
  'water',
  'waterway',
  'forest',
  'park',
])

/**
 * Является ли результат поиска зданием: тип `building` либо категория POI (например `shop/general/mall`),
 * которая физически находится внутри здания.
 */
export function isBuildingPoint(type: string | null | undefined): boolean {
  if (type == null) {
    return true
  }
  if (type === 'building') {
    return true
  }
  if (NON_BUILDING_TYPES.has(type)) {
    return false
  }
  return type.includes('/')
}

type ApiResultItem = {
  name?: string
  address?: string
  type?: string
  pin?: unknown
  address_details?: VkMapsAddressDetails
}

type ResultsResponse = {
  results?: ApiResultItem[]
}

/** Сколько ближайших объектов запрашиваем при обратном геокодировании. */
const REVERSE_SEARCH_LIMIT = 10
/** Поля выдачи `/search`: `address_details` — части адреса, `pin` — координаты найденного объекта. */
const REVERSE_SEARCH_FIELDS = 'name,address,type,pin,address_details'

/**
 * Поиск и геокодирование через VK Maps: Suggest, Places, Search и обратное геокодирование.
 */
export class VkMapsGeocodingService {
  /**
   * Подсказки: адреса через Suggest API, названия зданий/ТЦ — через Places.
   */
  async suggest(
    query: string,
    options?: VkMapsSearchOptions,
  ): Promise<VkMapsSuggestItem[]> {
    const [addresses, places] = await Promise.all([
      this.fetchResults('suggest', query, options),
      this.fetchResults('places', query, options),
    ])
    const merged = this.dedupe([...addresses, ...places])
    return options?.kind === 'buildings'
      ? merged.filter((item) => isBuildingPoint(item.type))
      : merged
  }

  /**
   * Прямое геокодирование адреса или названия в координаты.
   */
  async geocode(
    query: string,
    options?: VkMapsSearchOptions,
  ): Promise<(VkMapsLatLon & { address: string | null }) | null> {
    const onlyBuildings = options?.kind === 'buildings'
    const results = await this.fetchResults('search', query, {
      ...options,
      limit: onlyBuildings ? 8 : 1,
    })
    const first = onlyBuildings ? results.find((item) => isBuildingPoint(item.type)) : results[0]
    if (!first?.pin) {
      return null
    }
    return {
      ...first.pin,
      address: this.formatLabel(first.name, first.address),
    }
  }

  /**
   * Радиус, в котором найденный адрес считаем адресом самой точки (метры):
   * дальше начинается соседний квартал, такой адрес сохранять нельзя.
   */
  private static readonly MATCH_RADIUS_METERS = 150

  /**
   * Адрес по координатам выбранной на карте точки.
   *
   * У VK Maps нет отдельного reverse-метода: обратное геокодирование — это поиск
   * (`/search`) со строкой `q=lat,lon`, который возвращает ближайшие объекты с готовым
   * адресом (`address_details`) и своими координатами (`pin`).
   *
   * Возвращаем `null`, если рядом адреса нет: координаты вместо адреса не подставляем.
   */
  async reverseGeocode(lat: number, lon: number, signal?: AbortSignal): Promise<string | null> {
    const results = await this.fetchResults('search', `${lat},${lon}`, {
      signal,
      limit: REVERSE_SEARCH_LIMIT,
      fields: REVERSE_SEARCH_FIELDS,
    })

    const match = this.nearestMatch(results, lat, lon)

    return match === null ? null : this.formatAddress(match)
  }

  /**
   * Ближайший к точке объект с адресом; здания (дома с номером) предпочтительнее.
   */
  private nearestMatch(items: VkMapsSuggestItem[], lat: number, lon: number): VkMapsSuggestItem | null {
    const candidates = items
      .filter(
        (item): item is VkMapsSuggestItem & { address: string; pin: VkMapsLatLon } =>
          Boolean(item.address && item.pin),
      )
      .map((item) => ({
        item,
        distance: this.distanceMeters(lat, lon, item.pin.lat, item.pin.lon),
      }))
      .filter((candidate) => candidate.distance <= VkMapsGeocodingService.MATCH_RADIUS_METERS)

    if (candidates.length === 0) {
      return null
    }

    candidates.sort((a, b) => {
      const aBuilding = a.item.type === 'building' ? 0 : 1
      const bBuilding = b.item.type === 'building' ? 0 : 1

      return aBuilding - bBuilding || a.distance - b.distance
    })

    return candidates[0].item
  }

  /**
   * Компактный адрес «город, улица, дом»: полная строка API дублирует регион и район.
   */
  private formatAddress(item: VkMapsSuggestItem): string | null {
    const parts = [item.details?.locality, item.details?.street, item.details?.building]
      .map((part) => part?.trim())
      .filter((part): part is string => Boolean(part))

    return parts.length > 0 ? parts.join(', ') : item.address
  }

  /** Расстояние между координатами по формуле гаверсинуса, метры. */
  private distanceMeters(latA: number, lonA: number, latB: number, lonB: number): number {
    const earthRadiusMeters = 6371000
    const toRadians = (degrees: number) => (degrees * Math.PI) / 180
    const deltaLat = toRadians(latB - latA)
    const deltaLon = toRadians(lonB - lonA)
    const a =
      Math.sin(deltaLat / 2) ** 2 +
      Math.cos(toRadians(latA)) * Math.cos(toRadians(latB)) * Math.sin(deltaLon / 2) ** 2

    return 2 * earthRadiusMeters * Math.asin(Math.sqrt(a))
  }

  /**
   * Координаты выбранной подсказки: pin из ответа либо геокодирование адреса/названия.
   */
  async resolveSuggest(
    item: VkMapsSuggestItem,
    options?: VkMapsSearchOptions,
  ): Promise<(VkMapsLatLon & { address: string | null }) | null> {
    if (options?.kind === 'buildings' && !isBuildingPoint(item.type)) {
      return null
    }

    const label = this.formatLabel(item.name, item.address)
    if (item.pin) {
      return { ...item.pin, address: label }
    }

    const query = item.address || item.name
    if (!query) {
      return null
    }

    const geo = await this.geocode(query, options)
    if (!geo) {
      return null
    }

    return {
      lat: geo.lat,
      lon: geo.lon,
      address: label || geo.address,
    }
  }

  /**
   * Подпись точки: название здания, если оно не дублирует адрес.
   */
  formatLabel(name: string | null | undefined, address: string | null | undefined): string | null {
    const title = name?.trim() || null
    const line = address?.trim() || null
    if (title && line && !line.includes(title)) {
      return `${title}, ${line}`
    }
    return line || title
  }

  private async fetchResults(
    endpoint: 'suggest' | 'places' | 'search',
    query: string,
    options?: { location?: VkMapsLatLon | null; signal?: AbortSignal; limit?: number; fields?: string },
  ): Promise<VkMapsSuggestItem[]> {
    const params = new URLSearchParams({
      api_key: vkMapsApiKey(),
      q: query,
      lang: 'ru',
      limit: String(options?.limit ?? 8),
      fields: options?.fields ?? 'name,address,type,pin',
    })
    const location = this.formatLocation(options?.location)
    if (location) {
      params.set('location', location)
    }

    try {
      const response = await fetch(`${vkMapsApiOrigin}/${endpoint}?${params}`, {
        signal: options?.signal,
      })
      if (!response.ok) {
        return []
      }
      const json = (await response.json()) as ResultsResponse
      return (json.results ?? [])
        .map((item) => this.mapResult(item))
        .filter((item): item is VkMapsSuggestItem => Boolean(item.name || item.address))
    } catch (error) {
      if (error instanceof DOMException && error.name === 'AbortError') {
        throw error
      }
      return []
    }
  }

  private mapResult(item: ApiResultItem): VkMapsSuggestItem {
    return {
      name: item.name?.trim() || null,
      address: item.address?.trim() || null,
      type: item.type ?? null,
      pin: this.parsePin(item.pin),
      details: item.address_details ?? null,
    }
  }

  private dedupe(items: VkMapsSuggestItem[]): VkMapsSuggestItem[] {
    const byKey = new Map<string, VkMapsSuggestItem>()
    for (const item of items) {
      const key = `${(item.name ?? '').toLowerCase()}|${(item.address ?? '').toLowerCase()}`
      const existing = byKey.get(key)
      if (!existing || (!existing.pin && item.pin)) {
        byKey.set(key, item)
      }
    }
    return [...byKey.values()].slice(0, 10)
  }

  private formatLocation(location?: VkMapsLatLon | null): string | null {
    if (!location) {
      return null
    }
    return `${location.lat.toFixed(6)},${location.lon.toFixed(6)}`
  }

  private parsePin(pin: unknown): VkMapsLatLon | null {
    if (!Array.isArray(pin) || pin.length < 2) {
      return null
    }
    const lon = Number(pin[0])
    const lat = Number(pin[1])
    if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
      return null
    }
    return { lat, lon }
  }
}

export const vkMapsGeocodingService = new VkMapsGeocodingService()
