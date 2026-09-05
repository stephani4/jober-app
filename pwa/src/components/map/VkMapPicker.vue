<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { configureVkMapsSdk, defaultMapCenter, defaultMapLocation, vkMapsStyle } from '@/config/vkMaps'
import { requestCurrentPosition } from '@/composables/useGeolocation'
import { useVkMapsPlaceSearch } from '@/composables/useVkMapsPlaceSearch'
import {
  vkMapsGeocodingService,
  type VkMapsSuggestItem,
} from '@/services/VkMapsGeocodingService'

const props = withDefaults(
  defineProps<{
    lat?: number | null
    lon?: number | null
    title?: string
  }>(),
  {
    title: 'Выберите точку на карте',
  },
)

const emit = defineEmits<{
  select: [payload: { lat: number; lon: number; address: string | null }]
  close: []
}>()

const container = ref<HTMLElement | null>(null)
let map: InstanceType<typeof mmrgl.Map> | null = null
let marker: InstanceType<typeof mmrgl.Marker> | null = null
let userMarker: InstanceType<typeof mmrgl.Marker> | null = null
let disposed = false

function hasSelectedPoint(): boolean {
  return props.lon != null && props.lat != null
}

function currentLocation(): { lat: number; lon: number } | null {
  if (map) {
    const center = map.getCenter()
    return { lat: center.lat, lon: center.lng }
  }
  if (hasSelectedPoint()) {
    return { lat: props.lat as number, lon: props.lon as number }
  }
  return { lat: defaultMapLocation.lat, lon: defaultMapLocation.lon }
}

const {
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
} = useVkMapsPlaceSearch(currentLocation)

function createUserMarkerElement(): HTMLDivElement {
  const el = document.createElement('div')
  el.className = 'jober-map-user'
  el.title = 'Вы здесь'
  const pulse = document.createElement('span')
  pulse.className = 'jober-map-user-pulse'
  const dot = document.createElement('span')
  dot.className = 'jober-map-user-dot'
  el.appendChild(pulse)
  el.appendChild(dot)
  return el
}

function placeUserMarker(lngLat: [number, number]): void {
  if (!map) {
    return
  }
  if (!userMarker) {
    userMarker = new mmrgl.Marker({ element: createUserMarkerElement(), anchor: 'center' })
      .setLngLat(lngLat)
      .addTo(map)
    return
  }
  userMarker.setLngLat(lngLat)
}

function placeMarker(lngLat: [number, number]): void {
  if (!map) {
    return
  }
  if (!marker) {
    marker = new mmrgl.Marker({ color: '#141414' }).setLngLat(lngLat).addTo(map)
    return
  }
  marker.setLngLat(lngLat)
}

function applyPlace(lat: number, lon: number, address: string | null, fly: boolean): void {
  placeMarker([lon, lat])
  if (fly && map) {
    map.flyTo({ center: [lon, lat], zoom: 16 })
  }
  emit('select', { lat, lon, address })
}

async function onMapClick(event: { lngLat: { lng: number; lat: number } }): Promise<void> {
  close()
  const lon = event.lngLat.lng
  const lat = event.lngLat.lat
  placeMarker([lon, lat])
  const address = await vkMapsGeocodingService.reverseGeocode(lat, lon)
  query.value = address ?? ''
  emit('select', { lat, lon, address })
}

async function onPickSuggestion(item: VkMapsSuggestItem): Promise<void> {
  const place = await resolve(item)
  if (!place) {
    return
  }
  applyPlace(place.lat, place.lon, place.address, true)
}

onMounted(async () => {
  await nextTick()
  if (!container.value || disposed) {
    return
  }

  const userPosition = await requestCurrentPosition(2500)
  if (!container.value || disposed) {
    return
  }

  configureVkMapsSdk(mmrgl)
  const center: [number, number] = hasSelectedPoint()
    ? [props.lon as number, props.lat as number]
    : userPosition
      ? [userPosition.lon, userPosition.lat]
      : defaultMapCenter
  const zoom = hasSelectedPoint() || userPosition ? 15 : 10

  map = new mmrgl.Map({
    container: container.value,
    center,
    zoom,
    style: vkMapsStyle,
    interactive: true,
    attributionControl: false,
  })

  map.on('load', () => {
    map?.resize()
    if (hasSelectedPoint()) {
      placeMarker([props.lon as number, props.lat as number])
    }
    if (userPosition) {
      placeUserMarker([userPosition.lon, userPosition.lat])
    }
  })

  map.on('click', onMapClick)
})

onBeforeUnmount(() => {
  disposed = true
  map?.remove()
  map = null
  marker = null
  userMarker = null
})
</script>

<template>
  <div class="fixed inset-0 z-[80] flex flex-col bg-surface-hero">
    <div class="relative z-10 px-4 pb-3 text-text-on-hero">
      <div class="flex items-center justify-between py-3">
        <p class="text-base">{{ title }}</p>
        <button
          type="button"
          class="rounded-full border border-border-hero-control px-4 py-2 text-sm"
          @click="emit('close')"
        >
          Готово
        </button>
      </div>

      <div class="relative">
        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-text-on-hero-muted">
          <svg
            viewBox="0 0 24 24"
            class="h-5 w-5"
            fill="none"
            stroke="currentColor"
            stroke-width="1.75"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
          >
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-3.5-3.5" />
          </svg>
        </span>
        <input
          id="order-point-search"
          v-model="query"
          type="text"
          inputmode="search"
          autocomplete="off"
          autocorrect="off"
          spellcheck="false"
          aria-label="Поиск адреса или здания"
          placeholder="Адрес, магазин, ТЦ…"
          class="w-full rounded-full border border-border-hero-control bg-white/10 py-2.5 pl-10 pr-4 text-sm text-text-on-hero outline-none placeholder:text-text-on-hero-muted"
          :disabled="resolving"
          @input="scheduleSuggest"
          @focus="reopen"
          @keydown.escape.prevent="close"
        />

        <div
          v-if="open"
          class="absolute inset-x-0 top-[calc(100%+0.5rem)] overflow-hidden rounded-2xl border border-border-subtle bg-surface-card text-text-primary shadow-[var(--shadow-card)] dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-100"
        >
          <p
            v-if="loading && suggestions.length === 0"
            class="px-4 py-3 text-sm text-text-secondary"
          >
            Ищем…
          </p>
          <p
            v-else-if="!loading && suggestions.length === 0"
            class="px-4 py-3 text-sm text-text-secondary"
          >
            Ничего не найдено
          </p>
          <ul v-if="suggestions.length > 0" role="listbox" class="max-h-64 overflow-y-auto py-1">
            <li v-for="(item, index) in suggestions" :key="`${item.type}-${item.address}-${item.name}-${index}`">
              <button
                type="button"
                role="option"
                class="flex w-full flex-col items-start gap-0.5 px-4 py-3 text-left hover:bg-surface-muted dark:hover:bg-zinc-800"
                :disabled="resolving"
                @click="onPickSuggestion(item)"
              >
                <span class="text-sm text-text-primary dark:text-zinc-100">
                  {{ item.name || item.address }}
                </span>
                <span
                  v-if="item.name && item.address && item.name !== item.address"
                  class="text-xs text-text-secondary"
                >
                  {{ item.address }}
                </span>
              </button>
            </li>
          </ul>
        </div>
      </div>

      <p v-if="error" class="mt-2 text-sm text-rose-300">{{ error }}</p>
    </div>
    <div ref="container" class="min-h-0 flex-1" />
  </div>
</template>

<style>
.jober-map-user {
  position: relative;
  width: 22px;
  height: 22px;
  pointer-events: none;
}

.jober-map-user-dot {
  position: absolute;
  inset: 4px;
  border-radius: 9999px;
  background: #0ea5e9;
  border: 2px solid #ffffff;
  box-shadow: 0 0 0 2px rgb(14 165 233 / 0.35);
}

.jober-map-user-pulse {
  position: absolute;
  inset: 0;
  border-radius: 9999px;
  background: rgb(14 165 233 / 0.35);
  animation: jober-map-user-pulse 1.8s ease-out infinite;
}

@keyframes jober-map-user-pulse {
  0% {
    transform: scale(0.55);
    opacity: 0.8;
  }
  100% {
    transform: scale(1.8);
    opacity: 0;
  }
}
</style>
