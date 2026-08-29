/** Конфиг VK Карт: SDK ждёт origin без /api, HTTP API — с /api. */
export const vkMapsOrigin = 'https://maps.vk.com'
export const vkMapsApiOrigin = `${vkMapsOrigin}/api`
export const vkMapsStyle = 'mmr://api/styles/main_style.json'

/** Город по умолчанию на карте (Кемерово). */
export const defaultMapLocation = { lat: 55.3545, lon: 86.0893 } as const

/** Центр карты в формате [lon, lat] для MMR GL. */
export const defaultMapCenter: [number, number] = [defaultMapLocation.lon, defaultMapLocation.lat]

export function vkMapsApiKey(): string {
  return import.meta.env.VITE_VK_MAPS_API_KEY
}

/**
 * Настраивает глобальный MMR GL JS (подключается из CDN в index.html).
 */
export function configureVkMapsSdk(sdk: typeof window.mmrgl): void {
  sdk.accessToken = vkMapsApiKey()
  sdk.baseApiUrl = vkMapsOrigin
}
