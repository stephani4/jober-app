import { onBeforeUnmount, ref } from 'vue'
import {
  vkMapsGeocodingService,
  type VkMapsLatLon,
  type VkMapsSuggestItem,
} from '@/services/VkMapsGeocodingService'

const DEBOUNCE_MS = 300
const MIN_QUERY_LENGTH = 2

/**
 * Поиск точки на карте через VK Maps Suggest (адрес и название здания).
 */
export function useVkMapsPlaceSearch(getLocation: () => VkMapsLatLon | null) {
  const query = ref('')
  const suggestions = ref<VkMapsSuggestItem[]>([])
  const loading = ref(false)
  const resolving = ref(false)
  const open = ref(false)
  const error = ref('')

  let timer: ReturnType<typeof setTimeout> | null = null
  let abort: AbortController | null = null
  let requestId = 0

  function close(): void {
    open.value = false
  }

  function reopen(): void {
    if (suggestions.value.length > 0 || loading.value) {
      open.value = true
    }
  }

  function clearSuggestions(): void {
    suggestions.value = []
    loading.value = false
    open.value = false
    error.value = ''
  }

  /**
   * Запускает отложенный Suggest; короткие строки подсказки не запрашивают.
   */
  function scheduleSuggest(): void {
    error.value = ''
    if (timer) {
      clearTimeout(timer)
      timer = null
    }

    const q = query.value.trim()
    if (q.length < MIN_QUERY_LENGTH) {
      abort?.abort()
      requestId += 1
      clearSuggestions()
      return
    }

    timer = setTimeout(() => {
      void runSuggest(q)
    }, DEBOUNCE_MS)
  }

  async function runSuggest(q: string): Promise<void> {
    const id = ++requestId
    abort?.abort()
    abort = new AbortController()
    open.value = true
    loading.value = true

    try {
      const results = await vkMapsGeocodingService.suggest(q, {
        location: getLocation(),
        signal: abort.signal,
      })
      if (id !== requestId) {
        return
      }
      suggestions.value = results
    } catch (err) {
      if (err instanceof DOMException && err.name === 'AbortError') {
        return
      }
      if (id !== requestId) {
        return
      }
      suggestions.value = []
    } finally {
      if (id === requestId) {
        loading.value = false
      }
    }
  }

  /**
   * Превращает выбранную подсказку в координаты и подпись точки.
   */
  async function resolve(
    item: VkMapsSuggestItem,
  ): Promise<(VkMapsLatLon & { address: string | null }) | null> {
    resolving.value = true
    error.value = ''
    try {
      const place = await vkMapsGeocodingService.resolveSuggest(item, {
        location: getLocation(),
      })
      if (!place) {
        error.value = 'Не удалось найти точку на карте. Выберите другое место или укажите точку вручную.'
        return null
      }
      query.value = place.address ?? item.name ?? item.address ?? ''
      close()
      return place
    } finally {
      resolving.value = false
    }
  }

  onBeforeUnmount(() => {
    if (timer) {
      clearTimeout(timer)
    }
    abort?.abort()
  })

  return {
    query,
    suggestions,
    loading,
    resolving,
    open,
    error,
    scheduleSuggest,
    resolve,
    close,
    reopen,
  }
}
