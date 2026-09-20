<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { isAxiosError } from 'axios'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import CancelReasonDialog from '@/components/CancelReasonDialog.vue'
import { useAdminOrders, useAuth } from '@/composables'
import { orderStatusLabel, type Order, type OrderStatus } from '@/schemas/order'
import { Permission } from '@/permissions'
import { PermissionDeniedError } from '@/middleware/permission'

const router = useRouter()
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
  id: null as number | null,
  order_type_id: null as number | null,
  cost_min: null as number | null,
  cost_max: null as number | null,
  status: DEFAULT_STATUS as OrderStatus | 'all',
})

/** Опции фильтра «Вид заказа» (первая опция — «все виды»). */
const typeOptions = computed(() => [
  { label: 'Все виды', value: null },
  ...types.value.map((type) => ({ label: type.name, value: type.id })),
])

/** Опции фильтра «Статус» (значения = ключи orderStatusLabel + all). */
const statusOptions: { label: string; value: OrderStatus | 'all' }[] = [
  { label: 'Все', value: 'all' },
  ...(Object.entries(orderStatusLabel) as [OrderStatus, string][]).map(([value, label]) => ({
    label,
    value,
  })),
]

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
      id: form.id ?? undefined,
      order_type_id: form.order_type_id ?? undefined,
      cost_min: form.cost_min ?? undefined,
      cost_max: form.cost_max ?? undefined,
      status: form.status,
    })
  } catch (err) {
    error.value = extractError(err, 'Не удалось загрузить заказы.')
  }
}

function onReset(): void {
  form.id = null
  form.order_type_id = null
  form.cost_min = null
  form.cost_max = null
  form.status = DEFAULT_STATUS
  void onApply()
}

function extractError(err: unknown, fallback: string): string {
  if (err instanceof PermissionDeniedError) {
    return err.message
  }
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

function onShow(order: Order): void {
  void router.push({ name: 'order-show', params: { id: String(order.id) } })
}

async function onApprove(order: Order): Promise<void> {
  error.value = ''
  busyId.value = order.id
  try {
    await approve(order.id)
  } catch (err) {
    error.value = extractError(err, 'Не удалось одобрить заказ.')
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
    error.value = extractError(err, 'Не удалось отклонить заказ.')
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
      <label class="flex w-36 flex-col gap-1 text-sm" for="filter-order-id">
        <span class="text-text-secondary">Номер заказа</span>
        <InputNumber
          v-model="form.id"
          input-id="filter-order-id"
          fluid
          :use-grouping="false"
          :min="1"
          placeholder="№"
        />
      </label>
      <label class="flex w-52 flex-col gap-1 text-sm" for="filter-order-type">
        <span class="text-text-secondary">Вид заказа</span>
        <Select
          v-model="form.order_type_id"
          input-id="filter-order-type"
          :options="typeOptions"
          option-label="label"
          option-value="value"
          placeholder="Все виды"
          fluid
        />
      </label>
      <label class="flex w-32 flex-col gap-1 text-sm" for="filter-cost-min">
        <span class="text-text-secondary">Стоимость от</span>
        <InputNumber
          v-model="form.cost_min"
          input-id="filter-cost-min"
          fluid
          :min="0"
          :max-fraction-digits="2"
          placeholder="0"
        />
      </label>
      <label class="flex w-32 flex-col gap-1 text-sm" for="filter-cost-max">
        <span class="text-text-secondary">Стоимость до</span>
        <InputNumber
          v-model="form.cost_max"
          input-id="filter-cost-max"
          fluid
          :min="0"
          :max-fraction-digits="2"
          placeholder="∞"
        />
      </label>
      <label class="flex w-52 flex-col gap-1 text-sm" for="filter-status">
        <span class="text-text-secondary">Статус</span>
        <Select
          v-model="form.status"
          input-id="filter-status"
          :options="statusOptions"
          option-label="label"
          option-value="value"
          placeholder="Статус"
          fluid
        />
      </label>
      <Button
        type="submit"
        label="Применить"
        :loading="loading"
      />
      <Button
        type="button"
        label="Сбросить"
        severity="secondary"
        variant="outlined"
        :disabled="loading"
        @click="onReset"
      />
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
            <th class="px-4 py-3 font-medium">Действия</th>
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
              {{ orderStatusLabel[order.status] }}
            </td>
            <td class="px-4 py-3">
              <div class="flex flex-col gap-2">
                <Button
                  size="small"
                  variant="outlined"
                  label="Открыть"
                  icon="pi pi-arrow-up-right"
                  @click="onShow(order)"
                />
                <template v-if="order.status === 'moderate'">
                  <Button
                    v-if="can(Permission.OrdersApprove)"
                    size="small"
                    label="Одобрить"
                    :loading="busyId === order.id"
                    @click="onApprove(order)"
                  />
                  <Button
                    v-if="can(Permission.OrdersCancel)"
                    size="small"
                    severity="danger"
                    variant="outlined"
                    label="Отклонить"
                    :disabled="busyId === order.id"
                    @click="cancelTarget = order"
                  />
                </template>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Button
      v-if="nextCursor"
      variant="outlined"
      label="Ещё"
      :loading="loadingMore"
      @click="loadMore"
    />

    <CancelReasonDialog
      :open="cancelTarget != null"
      @close="cancelTarget = null"
      @confirm="onCancel"
    />
  </section>
</template>
