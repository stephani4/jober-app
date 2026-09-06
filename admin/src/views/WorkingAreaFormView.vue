<script setup lang="ts">
import { onMounted, ref, nextTick, onBeforeUnmount } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { isAxiosError } from 'axios'
import { workingAreaService, type WorkingArea } from '@/services/WorkingAreaService'
import { configureVkMapsSdk, vkMapsStyle } from '@/config/vkMaps'
import { useVkMapsPlaceSearch } from '@/composables/useVkMapsPlaceSearch'

// Объявляем mmrgl как глобальную переменную
declare const mmrgl: any

const router = useRouter()
const route = useRoute()

const id = route.params.id as string
const isEdit = !!id

const form = ref({
  name: '',
  type: 'city' as 'city' | 'other',
  points: '',
  geometry: '',
})

const {
  query,
  suggestions,
  loading,
  resolving,
  open,
  error: searchError,
  scheduleSuggest,
  resolve,
  closeSearch,
  reopenSearch,
} = useVkMapsPlaceSearch(() => {
  if (map) {
    const center = map.getCenter()
    return { lat: center.lat, lon: center.lng }
  }
  return null
})

const error = ref('')
const busy = ref(false)
const mapRef = ref<HTMLElement | null>(null)
let map: any = null
let currentPolygon: any = null
const clickedPoints = ref<[number, number][]>([])
const isDrawing = ref(false)

onMounted(async () => {
  if (isEdit) {
    try {
      const area = await workingAreaService.getById(Number(id))
      form.value = {
        name: area.name,
        type: area.type,
        points: area.points || '',
        geometry: area.geometry || '',
      }
      
      if (area.points) {
        try {
          const coords = JSON.parse(area.points)
          clickedPoints.value = coords
        } catch (e) {
          console.error('Ошибка парсинга точек:', e)
        }
      }
    } catch (err) {
      error.value = 'Не удалось загрузить данные зоны.'
    }
  }

  await nextTick()
  initMap()
})

onBeforeUnmount(() => {
  if (map) {
    map.remove()
    map = null
  }
})

function initMap() {
  if (typeof mmrgl === 'undefined') {
    console.error('Библиотека mmrgl не загружена. Проверьте подключение скрипта карт в index.html')
    return
  }

  configureVkMapsSdk(mmrgl)

  map = new mmrgl.Map({
    container: mapRef.value,
    center: [37.6173, 55.7558], // Москва [lon, lat]
    zoom: 10,
    style: vkMapsStyle,
    interactive: true,
    attributionControl: false,
  })

  if (form.value.points) {
    drawExistingGeometry()
  }

  map.on('click', (event: any) => {
    const { lng, lat } = event.lngLat
    addPoint(lng, lat)
  })
}

function addPoint(lng: number, lat: number) {
  clickedPoints.value.push([lng, lat])
  
  // To form a polygon, we need at least 3 points (closing it automatically)
  if (clickedPoints.value.length >= 3) {
    updatePolygonLayer()
  } else {
    drawTempPoints()
  }
}

function drawTempPoints() {
  // Clear previous markers/layers if any
  if (map.getLayer('temp-points')) {
    map.removeLayer('temp-points')
  }

  map.addSource('temp-points-source', {
    type: 'geojson',
    data: {
      type: 'FeatureCollection',
      features: clickedPoints.value.map(p => ({
        type: 'Feature',
        geometry: { type: 'Point', coordinates: p },
        properties: {}
      }))
    }
  })

  map.addLayer({
    id: 'temp-points',
    type: 'circle',
    source: 'temp-points-source',
    paint: {
      'circle-radius': 4,
      'circle-color': '#2563eb'
    }
  })
}

function updatePolygonLayer() {
  // Close the polygon by adding the first point to the end
  const polygonCoords = [...clickedPoints.value, clickedPoints.value[0]]

  if (map.getLayer('area-layer')) {
    map.removeLayer('area-layer')
  }
  if (map.getSource('area-source')) {
    map.removeSource('area-source')
  }
  if (map.getLayer('temp-points')) {
    map.removeLayer('temp-points')
  }
  if (map.getSource('temp-points-source')) {
    map.removeSource('temp-points-source')
  }

  map.addSource('area-source', {
    type: 'geojson',
    data: {
      type: 'Feature',
      geometry: {
        type: 'Polygon',
        coordinates: [polygonCoords],
      },
      properties: {},
    },
  })

  map.addLayer({
    id: 'area-layer',
    type: 'fill',
    source: 'area-source',
    paint: {
      'fill-color': '#3b82f6',
      'fill-opacity': 0.5,
      'fill-outline-color': '#2563eb',
    },
  })

  // WKT for the 'geometry' column
  const wkt = `POLYGON((${polygonCoords.map(p => `${p[0]} ${p[1]}`).join(', ')}))`
  form.value.geometry = wkt
  
  // JSON array of points for the 'points' column
  form.value.points = JSON.stringify(clickedPoints.value)
}

function undoLastPoint() {
  if (clickedPoints.value.length === 0) return

  clickedPoints.value.pop()

  if (clickedPoints.value.length >= 3) {
    updatePolygonLayer()
  } else if (clickedPoints.value.length > 0) {
    drawTempPoints()
  } else {
    clearPoints()
  }
}

async function onPickSuggestion(item: any) {
  const place = await resolve(item)
  if (!place) return
  
  addPoint(place.lon, place.lat)
  
  if (map) {
    map.flyTo({ center: [place.lon, place.lat], zoom: 15 })
  }
}

function clearPoints() {
  clickedPoints.value = []
  if (map.getLayer('area-layer')) map.removeLayer('area-layer')
  if (map.getSource('area-source')) map.removeSource('area-source')
  if (map.getLayer('temp-points')) map.removeLayer('temp-points')
  if (map.getSource('temp-points-source')) map.removeSource('temp-points-source')
  form.value.points = ''
}

function drawExistingGeometry() {
  try {
    // Use points for rendering to keep track of clickedPoints
    if (form.value.points) {
      const coords = JSON.parse(form.value.points)
      clickedPoints.value = coords
      
      const polygonCoords = [...coords, coords[0]]
      
      map.addSource('area-source', {
        type: 'geojson',
        data: {
          type: 'Feature',
          geometry: {
            type: 'Polygon',
            coordinates: [polygonCoords],
          },
          properties: {},
        },
      })

      map.addLayer({
        id: 'area-layer',
        type: 'fill',
        source: 'area-source',
        paint: {
          'fill-color': '#3b82f6',
          'fill-opacity': 0.5,
          'fill-outline-color': '#2563eb',
        },
      })
    }
  } catch (e) {
    console.error('Ошибка отрисовки геометрии:', e)
  }
}

async function onSubmit() {
  error.value = ''
  busy.value = true
  try {
    if (isEdit) {
      await workingAreaService.update(Number(id), form.value)
    } else {
      await workingAreaService.create(form.value)
    }
    router.push({ name: 'working-areas' })
  } catch (err) {
    error.value = isAxiosError(err)
      ? err.response?.data?.message || 'Ошибка при сохранении.'
      : 'Произошла ошибка.'
  } finally {
    busy.value = false
  }
}
</script>
<template>
  <section class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold">
          {{ isEdit ? 'Редактирование' : 'Создание' }} рабочей зоны
        </h1>
        <p class="mt-1 text-sm text-text-secondary">Задайте название и границы зоны на карте.</p>
      </div>
      <button
        type="button"
        class="rounded-lg border border-border-subtle px-4 py-2 text-sm hover:bg-zinc-50 dark:border-white/10 dark:hover:bg-zinc-800"
        @click="router.back()"
      >
        Отмена
      </button>
    </div>

    <p v-if="error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ error }}
    </p>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="space-y-4 rounded-2xl border border-border-subtle bg-white p-5 dark:border-white/10 dark:bg-zinc-900">
        <div class="space-y-2">
          <label class="text-sm font-medium">Название</label>
          <input
            v-model="form.name"
            type="text"
            class="w-full rounded-lg border border-border-subtle bg-transparent px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-white/10"
            placeholder="Например: Центр Москвы"
          />
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium">Тип</label>
          <select
            v-model="form.type"
            class="w-full rounded-lg border border-border-subtle bg-transparent px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-white/10"
          >
            <option value="city">Город</option>
            <option value="other">Другое</option>
          </select>
        </div>

        <div class="pt-4">
          <button
            type="button"
            class="w-full rounded-lg bg-zinc-900 py-2 text-sm text-white transition hover:bg-zinc-800 disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900"
            :disabled="busy"
            @click="onSubmit"
          >
            {{ busy ? 'Сохранение…' : 'Сохранить зону' }}
          </button>
        </div>
      </div>

      <div class="lg:col-span-2 space-y-4">
        <div class="relative">
          <div class="absolute inset-x-0 top-3 z-10 px-3">
            <div class="relative max-w-md">
              <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-text-secondary">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="11" cy="11" r="7" />
                  <path d="m20 20-3.5-3.5" />
                </svg>
              </span>
              <input
                v-model="query"
                type="text"
                class="w-full rounded-full border border-border-subtle bg-white py-2 pl-10 pr-4 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:bg-zinc-800 dark:border-white/10"
                placeholder="Поиск адреса или здания..."
                :disabled="resolving"
                @input="scheduleSuggest"
                @focus="reopenSearch"
                @keydown.escape.prevent="closeSearch"
              />

              <div
                v-if="open"
                class="absolute inset-x-0 top-[calc(100%+0.5rem)] z-20 overflow-hidden rounded-2xl border border-border-subtle bg-white text-text-primary shadow-lg dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-100"
              >
                <p v-if="loading && suggestions.length === 0" class="px-4 py-3 text-sm text-text-secondary">
                  Ищем…
                </p>
                <p v-else-if="!loading && suggestions.length === 0" class="px-4 py-3 text-sm text-text-secondary">
                  Ничего не найдено
                </p>
                <ul v-if="suggestions.length > 0" role="listbox" class="max-h-64 overflow-y-auto py-1">
                  <li v-for="(item, index) in suggestions" :key="index">
                    <button
                      type="button"
                      role="option"
                      class="flex w-full flex-col items-start gap-0.5 px-4 py-3 text-left hover:bg-zinc-100 dark:hover:bg-zinc-800"
                      :disabled="resolving"
                      @click="onPickSuggestion(item)"
                    >
                      <span class="text-sm font-medium">{{ item.name || item.address }}</span>
                      <span v-if="item.name && item.address && item.name !== item.address" class="text-xs text-text-secondary">
                        {{ item.address }}
                      </span>
                    </button>
                  </li>
                </ul>
              </div>
              <p v-if="searchError" class="absolute top-full mt-1 text-xs text-rose-600">
                {{ searchError }}
              </p>
            </div>
          </div>
          <div
            ref="mapRef"
            class="h-[500px] w-full rounded-2xl border border-border-subtle bg-zinc-100 dark:border-white/10 dark:bg-zinc-800"
          ></div>
        </div>
        <div class="flex items-center justify-between gap-3">
          <p class="text-xs text-text-secondary">
            * Кликните по карте, чтобы расставить точки зоны. Минимум 3 точки для создания области.
          </p>
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="rounded-md border border-border-subtle px-2 py-1 text-xs hover:bg-zinc-50 dark:border-white/10 dark:hover:bg-zinc-800"
              @click="undoLastPoint"
              :disabled="clickedPoints.length === 0"
            >
              Отменить точку
            </button>
            <button
              type="button"
              class="rounded-md border border-rose-200 px-2 py-1 text-xs text-rose-600 hover:bg-rose-50 dark:border-rose-900/30 dark:hover:bg-rose-900/20"
              @click="clearPoints"
            >
              Сбросить всё
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
