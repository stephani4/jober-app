<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { isAxiosError } from 'axios'
import CancelReasonDialog from '@/components/CancelReasonDialog.vue'
import { useAdminOrders, useAuth } from '@/composables'
import { orderStatusLabel, type Order, type OrderStatus } from '@/schemas/order'

const { can } = useAuth()
const {
  items,
  loading,
  loadingMore,
  nextCursor,
  types,
  loadTypes,
  fetchFirst,
  loadMore,
  approve,
  cancel,
} = useAdminOrders()

const DEFAULT_STATUS: OrderStatus | 'all' = 'moderate'

const form = reactive({
  id: '',
  order_type_id: '',
  cost_min: '',
  cost_max: '',
  status: DEFAULT_STATUS,
})

const error = ref('')
const busyId = ref<number | null>(null)
const cancelTarget = ref<Order | null>(null)

onMounted(() => {
  void loadTypes()
  void fetchFirst({ status: DEFAULT_STATUS })
})

async function onApply(): Promise<void> {
  error.value = ''
  try {
    await fetchFirst({
      id: form.id.trim() || undefined,
      order_type_id: form.order_type_id || undefined,
      cost_min: form.cost_min || undefined,
      cost_max: form.cost_max || undefined,
      status: form.status,
    })
  } catch (err) {
    error.value = extractError(err, 'Не удалось загрузить заказы.')
  }
}

function onReset(): void {
  form.id = ''
  form.order_type_id = ''
  form.cost_min = ''
  form.cost_max = ''
  form.status = DEFAULT_STATUS
  void onApply()
}

function extractError(err: unknown, fallback: string): string {
  if (isAxiosError(err)) {
    return (
      err.response?.data?.message
      || err.response?.data?.errors?.id?.[0]
      || err.response?.data?.errors?.order_type_id?.[0]
      || err.response?.data?.errors?.cost_min?.[0]
      || err.response?.data?.errors?.cost_max?.[0]
      || err.response?.data?.errors?.status?.[0]
      || fallback
    )
  }
  return fallback
}

async function onApprove(order: Order): Promise<void> {
  error.value = ''
  busyId.value = order.id
  try {
    await approve(order.id)
  } catch (err) {
    error.value = isAxiosError(err)
      ? err.response?.data?.message || err.response?.data?.errors?.order?.[0] || 'Не удалось одобрить заказ.'
      : 'Не удалось одобрить заказ.'
  } finally {
    busyId.value = null
  }
}

async function onCancel(reason: string): Promise<void> {
  const order = cancelTarget.value
  if (!order) {
    return
  }
  error.value = ''
  busyId.value = order.id
  try {
    await cancel(order.id, reason)
    cancelTarget.value = null
  } catch (err) {
    error.value = isAxiosError(err)
      ? err.response?.data?.message || err.response?.data?.errors?.reason?.[0] || 'Не удалось отклонить заказ.'
      : 'Не удалось отклонить заказ.'
  } finally {
    busyId.value = null
  }
}

function costLabel(value: number): string {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    maximumFractionDigits: 0,
  }).format(value)
}
</script>

<template>
  <section class="space-y-5">
    <div>
      <h1 class="text-2xl font-semibold">Заказы</h1>
      <p class="mt-1 text-sm text-text-secondary">Ручная модерация. Автоочередь работает параллельно.</p>
    </div>

    <form
      class="flex flex-wrap items-end gap-3"
      @submit.prevent="onApply"
    >
      <label class="flex flex-col gap-1 text-sm">
        <span class="text-text-secondary">Номер заказа</span>
        <input
          v-model="form.id"
          type="number"
          min="1"
          placeholder="№"
          class="w-36 rounded-lg border border-border-subtle bg-white px-3 py-2 dark:border-white/10 dark:bg-zinc-900"
        >
      </label>
      <label class="flex flex-col gap-1 text-sm">
        <span class="text-text-secondary">Вид заказа</span>
        <select
          v-model="form.order_type_id"
          class="rounded-lg border border-border-subtle bg-white px-3 py-2 dark:border-white/10 dark:bg-zinc-900"
        >
          <option value="">Все виды</option>
          <option
            v-for="type in types"
            :key="type.id"
            :value="String(type.id)"
          >
            {{ type.name }}
          </option>
        </select>
      </label>
      <label class="flex flex-col gap-1 text-sm">
        <span class="text-text-secondary">Стоимость от</span>
        <input
          v-model="form.cost_min"
          type="number"
          min="0"
          placeholder="0"
          class="w-32 rounded-lg border border-border-subtle bg-white px-3 py-2 dark:border-white/10 dark:bg-zinc-900"
        >
      </label>
      <label class="flex flex-col gap-1 text-sm">
        <span class="text-text-secondary">Стоимость до</span>
        <input
          v-model="form.cost_max"
          type="number"
          min="0"
          placeholder="∞"
          class="w-32 rounded-lg border border-border-subtle bg-white px-3 py-2 dark:border-white/10 dark:bg-zinc-900"
        >
      </label>
      <label class="flex flex-col gap-1 text-sm">
        <span class="text-text-secondary">Статус</span>
        <select
          v-model="form.status"
          class="rounded-lg border border-border-subtle bg-white px-3 py-2 dark:border-white/10 dark:bg-zinc-900"
        >
          <option value="all">Все</option>
          <option value="moderate">На модерации</option>
          <option value="wait">Ожидают</option>
          <option value="process">Выполняется</option>
          <option value="complete">Исполнено</option>
          <option value="cancel">Отклонённые</option>
        </select>
      </label>
      <button
        type="submit"
        class="rounded-lg bg-zinc-900 px-4 py-2 text-sm text-white disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900"
        :disabled="loading"
      >
        Применить
      </button>
      <button
        type="button"
        class="rounded-lg border border-border-subtle px-4 py-2 text-sm dark:border-white/10"
        :disabled="loading"
        @click="onReset"
      >
        Сбросить
      </button>
    </form>

    <p v-if="error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ error }}
    </p>
    <p v-if="loading" class="text-sm text-text-secondary">Загружаем заказы…</p>
    <p v-else-if="items.length === 0" class="text-sm text-text-secondary">Нет заказов в этом фильтре.</p>

    <div v-else class="overflow-x-auto rounded-2xl border border-border-subtle bg-white dark:border-white/10 dark:bg-zinc-900">
      <table class="min-w-full text-left text-sm">
        <thead class="border-b border-border-subtle text-text-secondary dark:border-white/10">
          <tr>
            <th class="px-4 py-3 font-medium">ID</th>
            <th class="px-4 py-3 font-medium">Вид</th>
            <th class="px-4 py-3 font-medium">Описание</th>
            <th class="px-4 py-3 font-medium">Автор</th>
            <th class="px-4 py-3 font-medium">Стоимость</th>
            <th class="px-4 py-3 font-medium">Статус</th>
            <th class="px-4 py-3 font-medium" />
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="order in items"
            :key="order.id"
            class="border-b border-border-subtle align-top last:border-0 dark:border-white/10"
          >
            <td class="px-4 py-3 text-text-secondary">{{ order.id }}</td>
            <td class="px-4 py-3">{{ order.order_type?.name || '—' }}</td>
            <td class="px-4 py-3">
              <p>{{ order.description || 'Без примечания' }}</p>
              <ul v-if="order.points.length" class="mt-2 space-y-1 text-xs text-text-secondary">
                <li v-for="point in order.points" :key="point.id">
                  {{ point.position }}. {{ point.description }}
                </li>
              </ul>
              <p v-if="order.reason" class="mt-2 text-xs text-accent-danger">{{ order.reason }}</p>
            </td>
            <td class="px-4 py-3">
              <p>{{ order.user?.name }}</p>
              <p class="text-xs text-text-secondary">{{ order.user?.email }}</p>
            </td>
            <td class="px-4 py-3">{{ costLabel(order.cost) }}</td>
            <td class="px-4 py-3">{{ orderStatusLabel[order.status] }}</td>
            <td class="px-4 py-3">
              <div v-if="order.status === 'moderate'" class="flex flex-col gap-2">
                <button
                  v-if="can('orders.approve')"
                  type="button"
                  class="rounded-lg bg-zinc-900 px-3 py-2 text-white disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900"
                  :disabled="busyId === order.id"
                  @click="onApprove(order)"
                >
                  Одобрить
                </button>
                <button
                  v-if="can('orders.cancel')"
                  type="button"
                  class="rounded-lg border border-accent-danger px-3 py-2 text-accent-danger disabled:opacity-50"
                  :disabled="busyId === order.id"
                  @click="cancelTarget = order"
                >
                  Отклонить
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <button
      v-if="nextCursor"
      type="button"
      class="rounded-xl border border-border-subtle px-4 py-2 text-sm dark:border-white/10"
      :disabled="loadingMore"
      @click="loadMore"
    >
      {{ loadingMore ? 'Загружаем…' : 'Ещё' }}
    </button>

    <CancelReasonDialog
      :open="cancelTarget != null"
      @close="cancelTarget = null"
      @confirm="onCancel"
    />
  </section>
</template>
