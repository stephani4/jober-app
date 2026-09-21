<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Message from 'primevue/message'
import Tab from 'primevue/tab'
import TabList from 'primevue/tablist'
import TabPanel from 'primevue/tabpanel'
import TabPanels from 'primevue/tabpanels'
import Tabs from 'primevue/tabs'
import OrderExecutorBadge from '@/components/orders/OrderExecutorBadge.vue'
import OrderPointFileList from '@/components/orders/OrderPointFileList.vue'
import OrderWatchMap from '@/components/map/OrderWatchMap.vue'
import { useOrderWatching } from '@/composables/useOrderWatching'
import { adminOrderService } from '@/services/AdminOrderService'
import {
  orderStatusLabel,
  orderExecutingStatusLabel,
  type Order,
  type OrderExecuting,
} from '@/schemas/order'

const router = useRouter()
const route = useRoute()

const orderId = computed(() => {
  const raw = route.params.id
  const value = Number(Array.isArray(raw) ? raw[0] : raw)
  return Number.isInteger(value) && value > 0 ? value : null
})

const order = ref<Order | null>(null)
const executingFromApi = ref<OrderExecuting | null>(null)
const loading = ref(false)
const error = ref('')
const activeTab = ref('info')

const {
  executing,
  executor,
  position,
  remainingRoute,
  currentPoint,
  destination,
  loading: watchingLoading,
  error: watchingError,
} = useOrderWatching(orderId)

/** Снимок выполнения: realtime-события или данные, загруженные со страницы. */
const execution = computed<OrderExecuting | null>(() => executing.value ?? executingFromApi.value)

/** Исполнитель: из realtime-канала или из данных заказа. */
const executorProfile = computed(() => executor.value ?? order.value?.executor ?? null)

const currentPointLabel = computed(() => {
  const points = execution.value?.points ?? []
  const index = currentPoint.value
    ? points.findIndex((point) => point.id === currentPoint.value?.id)
    : -1
  if (index < 0) {
    return null
  }
  return `Точка ${index + 1} из ${points.length}`
})

async function load(): Promise<void> {
  if (!orderId.value) {
    return
  }
  loading.value = true
  error.value = ''
  try {
    order.value = await adminOrderService.show(orderId.value)
    executingFromApi.value = await adminOrderService.executing(orderId.value)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Не удалось загрузить заказ.'
  } finally {
    loading.value = false
  }
}

watch(orderId, () => void load(), { immediate: true })

function onBack(): void {
  void router.push({ name: 'orders' })
}

function costLabel(cost: number): string {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    maximumFractionDigits: 0,
  }).format(cost)
}

function dateLabel(value: string | null | undefined): string {
  if (!value) {
    return '—'
  }
  return new Date(value).toLocaleString('ru-RU')
}

function pointCoords(lat: number | null | undefined, lon: number | null | undefined): string {
  if (lat == null || lon == null) {
    return '—'
  }
  return `${lat.toFixed(5)}, ${lon.toFixed(5)}`
}
</script>

<template>
  <section class="space-y-4">
    <div class="flex items-center gap-3">
      <Button
        icon="pi pi-arrow-left"
        severity="secondary"
        variant="outlined"
        rounded
        aria-label="К списку заказов"
        @click="onBack"
      />
      <h1 class="text-xl font-semibold">
        Заказ #{{ orderId }}
        <span v-if="order" class="ml-2 text-sm font-normal text-text-secondary">
          {{ orderStatusLabel[order.status] }}
        </span>
      </h1>
    </div>

    <p v-if="error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ error }}
    </p>
    <p v-if="loading && !order" class="text-sm text-text-secondary">Загружаем заказ…</p>

    <Tabs v-else-if="order" v-model:value="activeTab">
      <TabList>
        <Tab value="info">Информация</Tab>
        <Tab value="watching">Наблюдение</Tab>
      </TabList>
      <TabPanels>
        <TabPanel value="info">
          <div class="grid gap-4 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
              <div class="rounded-2xl border border-border-subtle bg-white p-4 dark:border-white/10 dark:bg-zinc-900">
                <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
                  <div>
                    <p class="text-xs text-text-secondary">Статус</p>
                    <p class="mt-1 text-sm font-medium">{{ orderStatusLabel[order.status] }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-text-secondary">Вид</p>
                    <p class="mt-1 text-sm font-medium">{{ order.order_type?.name || '—' }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-text-secondary">Стоимость</p>
                    <p class="mt-1 text-sm font-medium">{{ costLabel(order.cost) }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-text-secondary">Создан</p>
                    <p class="mt-1 text-sm font-medium">{{ dateLabel(order.created_at) }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-text-secondary">Завершен</p>
                    <p class="mt-1 text-sm font-medium">{{ dateLabel(order.complete_at) }}</p>
                  </div>
                </div>

                <div class="mt-4 border-t border-border-subtle pt-4 dark:border-white/10">
                  <p class="text-xs text-text-secondary">Автор</p>
                  <p class="mt-1 text-sm font-medium">{{ order.user?.name ?? '—' }}</p>
                  <p class="text-xs text-text-secondary">{{ order.user?.email }}</p>
                </div>

                <div class="mt-4 border-t border-border-subtle pt-4 dark:border-white/10">
                  <p class="text-xs text-text-secondary">Описание</p>
                  <p class="mt-1 whitespace-pre-line text-sm">
                    {{ order.description || 'Без примечания' }}
                  </p>
                  <p v-if="order.reason" class="mt-2 text-sm text-rose-600 dark:text-rose-300">
                    {{ order.reason }}
                  </p>
                </div>
              </div>

              <div class="rounded-2xl border border-border-subtle bg-white dark:border-white/10 dark:bg-zinc-900">
                <p class="border-b border-border-subtle px-4 py-3 text-sm font-medium dark:border-white/10">
                  Точки маршрута ({{ order.points.length }})
                </p>
                <div class="overflow-x-auto">
                  <table class="min-w-full text-left text-sm">
                    <thead class="text-text-secondary">
                      <tr>
                        <th class="px-4 py-2 font-medium">#</th>
                        <th class="px-4 py-2 font-medium">Что сделать</th>
                        <th class="px-4 py-2 font-medium">Адрес</th>
                        <th class="px-4 py-2 font-medium">Координаты</th>
                        <th class="px-4 py-2 font-medium">Доступ</th>
                        <th class="px-4 py-2 font-medium">Файлы</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="point in order.points"
                        :key="point.id"
                        class="border-t border-border-subtle align-top dark:border-white/10"
                      >
                        <td class="px-4 py-2">{{ point.position }}</td>
                        <td class="px-4 py-2">{{ point.description }}</td>
                        <td class="px-4 py-2">{{ point.address || '—' }}</td>
                        <td class="px-4 py-2">{{ pointCoords(point.lat, point.lon) }}</td>
                        <td class="px-4 py-2 text-xs text-text-secondary">
                          <template v-if="point.entrance != null || point.floor != null || point.apartment || point.intercom != null">
                            <span v-if="point.entrance != null">подъезд {{ point.entrance }};</span>
                            <span v-if="point.floor != null"> этаж {{ point.floor }};</span>
                            <span v-if="point.apartment"> кв. {{ point.apartment }};</span>
                            <span v-if="point.intercom != null"> домофон {{ point.intercom }}</span>
                          </template>
                          <template v-else>—</template>
                        </td>
                        <td class="px-4 py-2">
                          <OrderPointFileList :files="point.files ?? []" />
                        </td>
                      </tr>
                      <tr v-if="order.points.length === 0">
                        <td class="px-4 py-3 text-text-secondary" colspan="6">Точек нет.</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <div class="rounded-2xl border border-border-subtle bg-white p-4 dark:border-white/10 dark:bg-zinc-900">
              <p class="text-xs text-text-secondary">Исполнитель</p>
              <div class="mt-3">
                <OrderExecutorBadge
                  v-if="order.executor"
                  :executor="order.executor"
                  label="Принял заказ"
                />
                <p v-else class="text-sm text-text-secondary">Заказ ещё не взят в работу.</p>
              </div>
            </div>
          </div>
        </TabPanel>
        <TabPanel value="watching">
          <Message v-if="watchingError" severity="error" :closable="false">
            {{ watchingError }}
          </Message>

          <p v-if="watchingLoading && !execution" class="text-sm text-text-secondary">
            Загружаем выполнение…
          </p>

          <div v-else-if="!execution" class="py-10 text-center text-sm text-text-secondary">
            Заказ ещё не взят в работу — наблюдение появится, когда исполнитель приступит.
          </div>

          <div v-else class="grid gap-4 lg:grid-cols-3">
            <div class="lg:col-span-2">
              <div class="h-[28rem]">
                <OrderWatchMap :destination="destination" :position="position" :route="remainingRoute" />
              </div>
            </div>

            <div class="space-y-4">
              <div class="rounded-2xl border border-border-subtle bg-white p-4 dark:border-white/10 dark:bg-zinc-900">
                <p class="text-xs text-text-secondary">Исполнитель</p>
                <div class="mt-3">
                  <OrderExecutorBadge :executor="executorProfile" label="Выполняет заказ" />
                </div>
                <div class="mt-4 space-y-2 border-t border-border-subtle pt-4 text-sm dark:border-white/10">
                  <div class="flex items-center justify-between gap-2">
                    <span class="text-text-secondary">Этап</span>
                    <span class="font-medium">{{ orderExecutingStatusLabel[execution.status] }}</span>
                  </div>
                  <div class="flex items-center justify-between gap-2">
                    <span class="text-text-secondary">Точка</span>
                    <span class="font-medium">{{ currentPointLabel ?? '—' }}</span>
                  </div>
                  <div class="flex items-center justify-between gap-2">
                    <span class="text-text-secondary">Позиция</span>
                    <span class="font-medium">
                      {{ position ? pointCoords(position.lat, position.lon) : 'ожидаем координаты…' }}
                    </span>
                  </div>
                  <div class="flex items-center justify-between gap-2">
                    <span class="text-text-secondary">Обновлена</span>
                    <span class="font-medium">{{ dateLabel(execution.location_at) }}</span>
                  </div>
                </div>
              </div>

              <div
                v-if="currentPoint?.order_point"
                class="rounded-2xl border border-border-subtle bg-white p-4 dark:border-white/10 dark:bg-zinc-900"
              >
                <p class="text-xs text-text-secondary">Текущая задача исполнителя</p>
                <p class="mt-1 text-sm font-medium">{{ currentPoint.order_point.description }}</p>
                <p class="mt-1 text-sm text-text-secondary">
                  {{ currentPoint.order_point.address || 'Адрес не указан' }}
                </p>
              </div>

              <div class="rounded-2xl border border-border-subtle bg-white dark:border-white/10 dark:bg-zinc-900">
                <p class="border-b border-border-subtle px-4 py-3 text-sm font-medium dark:border-white/10">
                  Статусы точек
                </p>
                <ul class="divide-y divide-border-subtle text-sm dark:divide-white/10">
                  <li
                    v-for="point in execution.points"
                    :key="point.id"
                    class="flex items-center justify-between gap-2 px-4 py-2"
                  >
                    <span class="min-w-0 truncate">
                      {{ point.order_point?.position }}. {{ point.order_point?.description }}
                    </span>
                    <span
                      class="shrink-0 rounded-full px-2 py-0.5 text-xs"
                      :class="point.status === 'process'
                        ? 'bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-300'
                        : point.status === 'complete'
                          ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300'
                          : 'bg-surface-muted text-text-secondary dark:bg-zinc-800 dark:text-zinc-400'"
                    >
                      {{ orderExecutingStatusLabel[point.status] }}
                    </span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </TabPanel>
      </TabPanels>
    </Tabs>
  </section>
</template>

