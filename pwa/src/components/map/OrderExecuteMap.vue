<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { configureVkMapsSdk, defaultMapCenter, vkMapsStyle } from '@/config/vkMaps'
import type { GeoPosition } from '@/composables/useGeolocation'
import type { LngLat } from '@/utils/geo'

const props = defineProps<{
  destination: { lat: number; lon: number } | null
  position: GeoPosition | null
  route: LngLat[]
  draggablePosition?: boolean
}>()

const emit = defineEmits<{
  simulate: [payload: GeoPosition]
}>()

const container = ref<HTMLElement | null>(null)
let map: InstanceType<typeof mmrgl.Map> | null = null
let userMarker: InstanceType<typeof mmrgl.Marker> | null = null
let destMarker: InstanceType<typeof mmrgl.Marker> | null = null
let resizeObserver: ResizeObserver | null = null
let draggingUser = false
let followPaused = false
let followTimer: ReturnType<typeof setTimeout> | null = null
let programmaticMove = false
let programmaticSeq = 0

const FOLLOW_RESUME_MS = 2000
const ROUTE_SOURCE = 'execute-route'
const ROUTE_LAYER = 'execute-route-line'

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

function emitSimulatedPosition(): void {
  if (!userMarker) {
    return
  }
  const lngLat = userMarker.getLngLat()
  emit('simulate', { lat: lngLat.lat, lon: lngLat.lng, accuracy: 8 })
}

function ensureUserMarker(lngLat: LngLat): void {
  if (!map || draggingUser) {
    return
  }
  if (!userMarker) {
    const el = document.createElement('div')
    el.className = 'flex h-8 w-8 items-center justify-center'
    el.style.cursor = props.draggablePosition ? 'grab' : 'default'
    el.title = props.draggablePosition
      ? 'Перетащите, чтобы имитировать движение'
      : 'Ваша позиция'
    const dot = document.createElement('div')
    dot.className = 'h-3.5 w-3.5 rounded-full bg-sky-500 ring-4 ring-sky-500/35'
    el.appendChild(dot)

    userMarker = new mmrgl.Marker({ element: el, draggable: Boolean(props.draggablePosition) })
      .setLngLat(lngLat)
      .addTo(map)

    if (props.draggablePosition) {
      userMarker.on('dragstart', () => {
        draggingUser = true
        el.style.cursor = 'grabbing'
      })
      userMarker.on('drag', emitSimulatedPosition)
      userMarker.on('dragend', () => {
        el.style.cursor = 'grab'
        emitSimulatedPosition()
        draggingUser = false
        if (!followPaused) {
          centerOnFollow(false)
        }
      })
    }
    return
  }
  userMarker.setLngLat(lngLat)
}

function ensureDestMarker(lngLat: LngLat): void {
  if (!map) {
    return
  }
  if (!destMarker) {
    destMarker = new mmrgl.Marker({ color: '#141414' }).setLngLat(lngLat).addTo(map)
    return
  }
  destMarker.setLngLat(lngLat)
}

function followTarget(): LngLat | null {
  if (props.position) {
    return [props.position.lon, props.position.lat]
  }
  if (props.destination) {
    return [props.destination.lon, props.destination.lat]
  }
  return null
}

/**
 * Держит камеру на текущей позиции. Не двигает карту, пока её скроллят.
 */
function centerOnFollow(animate = true): void {
  if (!map || followPaused || draggingUser) {
    return
  }
  const target = followTarget()
  if (!target) {
    return
  }

  const seq = ++programmaticSeq
  programmaticMove = true
  const zoom = map.getZoom() < 14 ? 15 : undefined
  map.easeTo({
    center: target,
    ...(zoom != null ? { zoom } : {}),
    duration: animate ? 450 : 0,
    essential: true,
  })
  window.setTimeout(() => {
    if (seq === programmaticSeq) {
      programmaticMove = false
    }
  }, animate ? 500 : 0)
}

function pauseFollow(): void {
  followPaused = true
  if (followTimer) {
    clearTimeout(followTimer)
    followTimer = null
  }
}

function scheduleResumeFollow(): void {
  if (followTimer) {
    clearTimeout(followTimer)
  }
  followTimer = setTimeout(() => {
    followTimer = null
    followPaused = false
    centerOnFollow(true)
  }, FOLLOW_RESUME_MS)
}

function onUserMapStart(event: { originalEvent?: unknown }): void {
  if (draggingUser || programmaticMove || !event.originalEvent) {
    return
  }
  pauseFollow()
}

function onUserMapEnd(): void {
  if (draggingUser || programmaticMove || !followPaused) {
    return
  }
  scheduleResumeFollow()
}

function bindFollowGestures(): void {
  if (!map) {
    return
  }
  map.on('dragstart', onUserMapStart)
  map.on('zoomstart', onUserMapStart)
  map.on('rotatestart', onUserMapStart)
  map.on('pitchstart', onUserMapStart)
  map.on('dragend', onUserMapEnd)
  map.on('zoomend', onUserMapEnd)
  map.on('rotateend', onUserMapEnd)
  map.on('pitchend', onUserMapEnd)
}

function syncOverlays(): void {
  if (props.destination) {
    ensureDestMarker([props.destination.lon, props.destination.lat])
  }
  if (props.position) {
    ensureUserMarker([props.position.lon, props.position.lat])
  }
  setRouteData(props.route)
  centerOnFollow(true)
}

onMounted(async () => {
  await nextTick()
  if (!container.value) {
    return
  }

  configureVkMapsSdk(mmrgl)
  const center: LngLat = followTarget() ?? defaultMapCenter

  map = new mmrgl.Map({
    container: container.value,
    center,
    zoom: 15,
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
    bindFollowGestures()
    syncOverlays()
  })

  resizeObserver = new ResizeObserver(() => map?.resize())
  resizeObserver.observe(container.value)
})

watch(
  () => [props.destination, props.position, props.route] as const,
  () => {
    syncOverlays()
  },
)

onBeforeUnmount(() => {
  if (followTimer) {
    clearTimeout(followTimer)
    followTimer = null
  }
  resizeObserver?.disconnect()
  resizeObserver = null
  userMarker = null
  destMarker = null
  map?.remove()
  map = null
})
</script>

<template>
  <div class="relative h-full min-h-[16rem] w-full">
    <div ref="container" class="h-full w-full" />
    <p
      v-if="draggablePosition"
      class="pointer-events-none absolute left-3 top-3 rounded-full bg-surface-hero/80 px-3 py-1.5 text-xs text-text-on-hero"
    >
      Перетащите синюю точку, чтобы имитировать движение
    </p>
  </div>
</template>
