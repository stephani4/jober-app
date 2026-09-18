<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { configureVkMapsSdk, defaultMapCenter, vkMapsStyle } from '@/config/vkMaps'
import type { LngLat } from '@/utils/geo'

// MMR GL подключается глобально из CDN в index.html.
declare const mmrgl: any


const props = defineProps<{
  /** Текущая точка маршрута исполнителя. */
  destination: { lat: number; lon: number } | null
  /** Координаты исполнителя. */
  position: { lat: number; lon: number } | null
  /** Оставшийся маршрут [lon, lat]. */
  route: LngLat[]
}>()

const container = ref<HTMLElement | null>(null)
let map: any = null
let executorMarker: any = null
let destinationMarker: any = null
let resizeObserver: ResizeObserver | null = null

const ROUTE_SOURCE = 'order-watch-route'
const ROUTE_LAYER = 'order-watch-route-line'

function emptyCollection() {
  return { type: 'FeatureCollection' as const, features: [] as never[] }
}

function routeCollection(coordinates: LngLat[]) {
  if (coordinates.length < 2) {
    return emptyCollection()
  }
  return {
    type: 'FeatureCollection' as const,
    features: [
      {
        type: 'Feature' as const,
        properties: {},
        geometry: { type: 'LineString' as const, coordinates },
      },
    ],
  }
}

function setRouteData(coordinates: LngLat[]): void {
  const source = map?.getSource(ROUTE_SOURCE) as { setData?: (data: unknown) => void } | undefined
  source?.setData?.(routeCollection(coordinates))
}

function ensureExecutorMarker(lngLat: LngLat): void {
  if (!map) {
    return
  }
  if (!executorMarker) {
    const el = document.createElement('div')
    el.className = 'flex h-8 w-8 items-center justify-center'
    el.title = 'Исполнитель'
    const dot = document.createElement('div')
    dot.className = 'h-3.5 w-3.5 rounded-full bg-sky-500 ring-4 ring-sky-500/35'
    el.appendChild(dot)

    executorMarker = new mmrgl.Marker({ element: el }).setLngLat(lngLat).addTo(map)
    return
  }
  executorMarker.setLngLat(lngLat)
}

function ensureDestinationMarker(lngLat: LngLat): void {
  if (!map) {
    return
  }
  if (!destinationMarker) {
    destinationMarker = new mmrgl.Marker({ color: '#141414' }).setLngLat(lngLat).addTo(map)
    return
  }
  destinationMarker.setLngLat(lngLat)
}

/** Подгоняет масштаб, чтобы исполнитель и точка были видны вместе. */
function fitBounds(): void {
  if (!map || !props.position) {
    return
  }

  const points: LngLat[] = [[props.position.lon, props.position.lat]]
  if (props.destination) {
    points.push([props.destination.lon, props.destination.lat])
  }

  const bounds = new mmrgl.LngLatBounds(points[0], points[0])
  points.forEach((point) => bounds.extend(point))
  map.fitBounds(bounds, { padding: 80, maxZoom: 15, duration: 0, essential: true })
}

function syncOverlays(): void {
  if (props.destination) {
    ensureDestinationMarker([props.destination.lon, props.destination.lat])
  }
  if (props.position) {
    ensureExecutorMarker([props.position.lon, props.position.lat])
  }
  setRouteData(props.route)
  fitBounds()
}

onMounted(async () => {
  await nextTick()
  if (!container.value || typeof mmrgl === 'undefined') {
    return
  }

  configureVkMapsSdk(mmrgl)

  map = new mmrgl.Map({
    container: container.value,
    center: defaultMapCenter,
    zoom: 12,
    style: vkMapsStyle,
    interactive: true,
    attributionControl: false,
  })

  map.on('load', () => {
    map?.addSource(ROUTE_SOURCE, {
      type: 'geojson',
      data: emptyCollection(),
    })
    map?.addLayer({
      id: ROUTE_LAYER,
      type: 'line',
      source: ROUTE_SOURCE,
      layout: {
        'line-cap': 'round',
        'line-join': 'round',
      },
      paint: {
        'line-color': '#141414',
        'line-width': 5,
        'line-opacity': 0.9,
      },
    })
    map?.resize()
    syncOverlays()
  })

  resizeObserver = new ResizeObserver(() => map?.resize())
  resizeObserver.observe(container.value)
})

watch(
  () => [props.destination, props.position, props.route] as const,
  () => syncOverlays(),
)

onBeforeUnmount(() => {
  resizeObserver?.disconnect()
  resizeObserver = null
  executorMarker = null
  destinationMarker = null
  map?.remove()
  map = null
})
</script>

<template>
  <div class="h-full min-h-[24rem] w-full overflow-hidden rounded-xl border border-border-subtle dark:border-white/10">
    <div ref="container" class="h-full w-full" />
  </div>
</template>