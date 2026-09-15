<script setup lang="ts">
import { onMounted, ref, nextTick, onBeforeUnmount } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { isAxiosError } from 'axios'
import AutoComplete from 'primevue/autocomplete'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import { workingAreaService } from '@/services/WorkingAreaService'
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

/** Опции типа зоны для Select. */
const typeOptions: { label: string; value: 'city' | 'other' }[] = [
  { label: 'Город', value: 'city' },
  { label: 'Другое', value: 'other' },
]

const {
  query,
  suggestions,
  loading,
  resolving,
  error: searchError,
  scheduleSuggest,
  resolve,
  // В композабле это close/reopen: уточняем имена, т.к. управляют списком подсказок поиска.
  close: closeSearch,
  reopen: reopenSearch,
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
  const data = {
    type: 'FeatureCollection',
    features: clickedPoints.value.map(p => ({
      type: 'Feature',
      geometry: { type: 'Point', coordinates: p },
      properties: {}
    }))
  }

  const source = map.getSource('temp-points-source')
  if (source) {
    // Источник уже создан: повторный addSource бросает ошибку, поэтому только обновляем данные.
    source.setData(data)
    return
  }

  map.addSource('temp-points-source', { type: 'geojson', data })

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

/**
 * Пишет в query только строки: при выборе подсказки AutoComplete
 * отдаёт моделью объект подсказки, который в query сохранять нельзя.
 */
function onSearchInput(value: unknown): void {
  if (typeof value === 'string') {
    query.value = value
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
      <Button
        severity="secondary"
        variant="outlined"
        label="Отмена"
        @click="router.back()"
      />
    </div>

    <p v-if="error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ error }}
    </p>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="space-y-4 rounded-2xl border border-border-subtle bg-white p-5 dark:border-white/10 dark:bg-zinc-900">
        <div class="space-y-2">
          <label class="text-sm font-medium" for="working-area-name">Название</label>
          <InputText
            v-model="form.name"
            id="working-area-name"
            fluid
            placeholder="Например: Центр Москвы"
          />
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium" for="working-area-type">Тип</label>
          <Select
            v-model="form.type"
            input-id="working-area-type"
            :options="typeOptions"
            option-label="label"
            option-value="value"
            fluid
          />
        </div>

        <div class="pt-4">
          <Button
            class="w-full"
            label="Сохранить зону"
            :loading="busy"
            @click="onSubmit"
          />
        </div>
      </div>

      <div class="lg:col-span-2 space-y-4">
        <div class="relative">
          <div class="absolute inset-x-0 top-3 z-10 px-3">
            <div class="relative max-w-md">
              <span class="pointer-events-none absolute inset-y-0 left-3 z-10 flex items-center text-text-secondary">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="11" cy="11" r="7" />
                  <path d="m20 20-3.5-3.5" />
                </svg>
              </span>
              <AutoComplete
                :model-value="query"
                input-id="working-area-search"
                class="w-full"
                input-class="w-full rounded-full border border-border-subtle bg-white py-2 pl-10 pr-4 text-sm shadow-sm dark:bg-zinc-800 dark:border-white/10"
                :suggestions="suggestions"
                option-label="name"
                placeholder="Поиск адреса или здания..."
                :min-length="2"
                :delay="0"
                :loading="loading || resolving"
                :complete-on-focus="true"
                @update:model-value="onSearchInput"
                @complete="scheduleSuggest"
                @item-select="onPickSuggestion($event.value)"
                @focus="reopenSearch"
                @keydown.escape.prevent="closeSearch"
              >
                <template #option="{ option }">
                  <div class="flex flex-col gap-0.5">
                    <span class="text-sm font-medium">{{ option.name || option.address }}</span>
                    <span
                      v-if="option.name && option.address && option.name !== option.address"
                      class="text-xs text-text-secondary"
                    >
                      {{ option.address }}
                    </span>
                  </div>
                </template>
              </AutoComplete>
              <p v-if="searchError" class="mt-1 text-xs text-rose-600">
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
            <Button
              size="small"
              severity="secondary"
              variant="outlined"
              label="Отменить точку"
              :disabled="clickedPoints.length === 0"
              @click="undoLastPoint"
            />
            <Button
              size="small"
              severity="danger"
              variant="outlined"
              label="Сбросить всё"
              @click="clearPoints"
            />
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
