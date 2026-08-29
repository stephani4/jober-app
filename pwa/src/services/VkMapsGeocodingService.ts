import { vkMapsApiKey, vkMapsApiOrigin } from '@/config/vkMaps'

export type VkMapsLatLon = {
  lat: number
  lon: number
}

export type VkMapsSuggestItem = {
  name: string | null
  address: string | null
  type: string | null
  pin: VkMapsLatLon | null
}

type ApiResultItem = {
  name?: string
  address?: string
  type?: string
  pin?: unknown
}

type ResultsResponse = {
  results?: ApiResultItem[]
}

type ReverseGeocodeResponse = {
  result?: { name?: string; address?: string }
}

/**
 * Поиск и геокодирование через VK Maps: Suggest, Places, Search и обратное геокодирование.
 */
export class VkMapsGeocodingService {
  /**
   * Подсказки: адреса через Suggest API, названия зданий/ТЦ — через Places.
   */
  async suggest(
    query: string,
    options?: { location?: VkMapsLatLon | null; signal?: AbortSignal },
  ): Promise<VkMapsSuggestItem[]> {
    const [addresses, places] = await Promise.all([
      this.fetchResults('suggest', query, options),
      this.fetchResults('places', query, options),
    ])
    return this.dedupe([...addresses, ...places])
  }

  /**
   * Прямое геокодирование адреса или названия в координаты.
   */
  async geocode(
    query: string,
    options?: { location?: VkMapsLatLon | null; signal?: AbortSignal },
  ): Promise<(VkMapsLatLon & { address: string | null }) | null> {
    const results = await this.fetchResults('search', query, { ...options, limit: 1 })
    const first = results[0]
    if (!first?.pin) {
      return null
    }
    return {
      ...first.pin,
      address: this.formatLabel(first.name, first.address),
    }
  }

  /**
   * Адрес по координатам выбранной на карте точки.
   */
  async reverseGeocode(lat: number, lon: number, signal?: AbortSignal): Promise<string | null> {
    const fallback = `${lat.toFixed(6)}, ${lon.toFixed(6)}`
    try {
      const params = new URLSearchParams({
        api_key: vkMapsApiKey(),
        lat: String(lat),
        lon: String(lon),
      })
      const response = await fetch(`${vkMapsApiOrigin}/geocode?${params}`, { signal })
      if (!response.ok) {
        return fallback
      }
      const json = (await response.json()) as ReverseGeocodeResponse
      return json.result?.address || json.result?.name || fallback
    } catch (error) {
      if (error instanceof DOMException && error.name === 'AbortError') {
        throw error
      }
      return fallback
    }
  }

  /**
   * Координаты выбранной подсказки: pin из ответа либо геокодирование адреса/названия.
   */
  async resolveSuggest(
    item: VkMapsSuggestItem,
    options?: { location?: VkMapsLatLon | null; signal?: AbortSignal },
  ): Promise<(VkMapsLatLon & { address: string | null }) | null> {
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
    options?: { location?: VkMapsLatLon | null; signal?: AbortSignal; limit?: number },
  ): Promise<VkMapsSuggestItem[]> {
    const params = new URLSearchParams({
      api_key: vkMapsApiKey(),
      q: query,
      lang: 'ru',
      limit: String(options?.limit ?? 8),
      fields: 'name,address,type,pin',
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
