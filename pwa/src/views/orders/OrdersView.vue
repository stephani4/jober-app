<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import OrderCancelConfirmModal from '@/components/orders/OrderCancelConfirmModal.vue'
import OrderCard from '@/components/orders/OrderCard.vue'
import { useAuth, useOrders, useOrderTypes } from '@/composables'
import { useOrderTypesStore } from '@/stores/orderTypes'
import type { Order, OrderStatus } from '@/schemas/order'

const router = useRouter()
const toast = useToast()
const { user } = useAuth()
const { items, loaded, cancel, cancellingId } = useOrders()
const orderTypesLoading = useOrderTypesStore().loading
const orderTypesError = useOrderTypesStore().error
const { types, selectType } = useOrderTypes()

onMounted(() => {
  void useOrderTypesStore().fetchTypes()
})

const cancellableStatuses: OrderStatus[] = ['moderate', 'wait', 'process']
const orderToCancel = ref<Order | null>(null)

/** Активный заказ — первый в статусе process; рендерится карточкой с таймлайном. */
const activeOrder = computed(() => items.value.find((order) => order.status === 'process') ?? null)

const otherOrders = computed(() => items.value.filter((order) => order !== activeOrder.value))

const routeLabel = computed(() => {
  const points = activeOrder.value?.points ?? []
  if (points.length === 0) {
    return null
  }
  const from = points[0].address || points[0].description
  const to = points[points.length - 1].address || points[points.length - 1].description
  if (!from || !to) {
    return `${points.length} ${points.length === 1 ? 'точка' : points.length <= 4 ? 'точки' : 'точек'} маршрута`
  }
  return `${from} → ${to}`
})

function canWatch(order: Order): boolean {
  return order.status === 'process' && order.user_id === user.value?.id
}

function canCancel(order: Order): boolean {
  return order.user_id === user.value?.id && cancellableStatuses.includes(order.status)
}

/** Код подтверждения виден только автору заказа на этапе confirmation. */
function confirmationCode(order: Order): string | null {
  return order.user_id === user.value?.id ? order.confirmation_number ?? null : null
}

function onWatch(order: Order): void {
  void router.push({ name: 'order-watching', params: { orderId: String(order.id) } })
}

function onAskCancel(order: Order): void {
  orderToCancel.value = order
}

async function onConfirmCancel(): Promise<void> {
  const order = orderToCancel.value
  if (!order) {
    return
  }

  try {
    await cancel(order.id)
    toast.add({
      severity: 'success',
      summary: 'Заказ отменён',
      detail: 'Заказ перемещён в историю.',
      life: 4500,
    })
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Не удалось отменить заказ',
      detail: err instanceof Error ? err.message : 'Попробуйте ещё раз.',
      life: 6000,
    })
  } finally {
    orderToCancel.value = null
  }
}

async function openCreateForType(typeId: number): Promise<void> {
  const type = types.value.find((t) => t.id === typeId)
  if (!type) {
    return
  }
  await selectType(type)
}
</script>


<template>
  <section class="space-y-4">
    <div class="mt-3 space-y-3">
      <p class="text-sm font-semibold text-text-primary dark:text-zinc-100">
        Какой заказ?
      </p>
      <div v-if="orderTypesLoading" class="grid grid-cols-1 gap-3">
        <div class="h-10 rounded-2xl border border-border-subtle bg-surface-muted animate-pulse" />
        <div class="h-10 rounded-2xl border border-border-subtle bg-surface-muted animate-pulse hidden sm:block" />
        <div class="h-10 rounded-2xl border border-border-subtle bg-surface-muted animate-pulse hidden sm:block" />
      </div>

      <div v-else-if="orderTypesError" class="rounded-2xl border border-accent-danger/30 bg-accent-danger/5 p-4 text-sm text-accent-danger">
        {{ orderTypesError }}
      </div>

      <div v-else-if="types.length === 0" class="rounded-2xl border border-border-subtle bg-surface-card p-4 text-sm text-text-secondary">
        Виды заказов не найдены.
      </div>

      <div v-else class="grid grid-cols-1 gap-2">
        <button
            v-for="type in types"
            :key="type.id"
            type="button"
            class="flex w-full items-center gap-3 rounded-2xl border border-border-subtle bg-surface-card px-4 py-3.5 text-left transition hover:bg-surface-muted focus:outline-none focus:ring-2 focus:ring-accent-primary/50 dark:border-white/10 dark:bg-zinc-900 dark:hover:bg-zinc-800"
            @click="openCreateForType(type.id)"
        >
          <span
              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-surface-muted text-text-primary dark:bg-zinc-800 dark:text-zinc-100"
              aria-hidden="true"
          >
            <svg
                v-if="type.max_points === 1"
                viewBox="0 0 24 24"
                class="h-5 w-5"
                fill="none"
                stroke="currentColor"
                stroke-width="1.75"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
              <path d="M6 7h15l-1.5 9h-12z" />
              <path d="M6 7 5 4H2" />
              <circle cx="9" cy="20" r="1" />
              <circle cx="18" cy="20" r="1" />
            </svg>
            <svg
                v-else-if="type.id === 2"
                viewBox="0 0 24 24"
                class="h-5 w-5"
                fill="none"
                stroke="currentColor"
                stroke-width="1.75"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
              <rect x="3" y="7" width="13" height="10" rx="1.5" />
              <path d="M16 10h3l2 3v4h-5" />
              <path d="M8 12h5" />
            </svg>
            <svg
                v-else
                viewBox="0 0 24 24"
                class="h-5 w-5"
                fill="none"
                stroke="currentColor"
                stroke-width="1.75"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
              <path d="M7 3h8l4 4v14H7z" />
              <path d="M15 3v4h4" />
              <path d="M10 13h6M10 17h4" />
            </svg>
          </span>
          <span class="min-w-0 flex-1">
            <span class="block text-base text-text-primary dark:text-zinc-100">{{ type.name }}</span>
            <span class="mt-0.5 block text-sm text-text-secondary">{{ type.description }}</span>
          </span>
          <svg
              viewBox="0 0 24 24"
              class="h-5 w-5 shrink-0 text-text-secondary"
              fill="none"
              stroke="currentColor"
              stroke-width="1.75"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
          >
            <path d="m9 6 6 6-6 6" />
          </svg>
        </button>
      </div>

      <p v-if="loaded && items.length === 0" class="text-sm text-text-secondary font-semibold">
        Тут будут отображаться ваши активные заказы.
      </p>
    </div>

    <template v-if="activeOrder">
      <h2 class="text-base font-semibold text-text-primary dark:text-zinc-100">Активный заказ</h2>

      <article
          class="rounded-2xl border border-border-subtle bg-surface-card p-4 shadow-[var(--shadow-card)] dark:border-white/10 dark:bg-zinc-900"
      >
        <div class="flex items-start gap-3">
          <span
              class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-accent-nav/10 text-xl"
              aria-hidden="true"
          >
            📦
          </span>
          <div class="min-w-0 flex-1">
            <p class="flex flex-wrap items-center gap-1.5 text-sm font-semibold text-text-primary dark:text-zinc-100">
              ID: {{ activeOrder.id }}
              <span class="rounded-full bg-surface-muted px-2 py-0.5 text-[10px] font-medium text-text-secondary dark:bg-zinc-800 dark:text-zinc-400">
                Выполняется
              </span>
            </p>
            <p class="mt-1 line-clamp-2 text-sm text-text-primary dark:text-zinc-100">
              {{ activeOrder.description || 'Заказ без примечания' }}
            </p>
          </div>
        </div>

        <div class="mt-4 flex items-center gap-2">
          <span
              class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-accent-nav text-white"
              aria-hidden="true"
          >
            <svg
                viewBox="0 0 24 24"
                class="h-3.5 w-3.5"
                fill="none"
                stroke="currentColor"
                stroke-width="3"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
              <path d="M20 6 9 16L6 12l9 15Z" />
            </svg>
          </span>
          <span class="h-0.5 w-6 shrink-0 bg-accent-nav" aria-hidden="true" />
          <span class="rounded-lg bg-text-primary px-2.5 py-1 text-xs font-medium text-white">
            Выполняется
          </span>
          <span class="h-0.5 w-6 shrink-0 bg-border-subtle" aria-hidden="true" />
          <span
              class="h-5 w-5 shrink-0 rounded-full border-2 border-border-subtle"
              aria-hidden="true"
          />
        </div>

        <p v-if="routeLabel" class="mt-3 flex items-center gap-1.5 text-xs text-text-secondary">
          <span aria-hidden="true">📍</span>
          <span class="truncate">{{ routeLabel }}</span>
        </p>

        <button
            v-if="canWatch(activeOrder)"
            type="button"
            class="mt-4 w-full rounded-xl border border-border-subtle px-4 py-3 text-sm text-text-primary dark:border-white/10 dark:text-zinc-100"
            @click="onWatch(activeOrder)"
        >
          Смотреть выполнение
        </button>
      </article>
    </template>

    <template v-if="otherOrders.length">
      <h2 class="text-base font-semibold text-text-primary dark:text-zinc-100">Мои заказы</h2>
      <div class="space-y-3">
        <OrderCard
            v-for="order in otherOrders"
            :key="order.id"
            :order="order"
            :watchable="canWatch(order)"
            :cancellable="canCancel(order)"
            :cancelling="cancellingId === order.id"
            :confirmation-code="confirmationCode(order)"
            @watch="onWatch(order)"
            @cancel="onAskCancel(order)"
        />
      </div>
    </template>

    <OrderCancelConfirmModal
        :order="orderToCancel"
        :loading="cancellingId !== null"
        @confirm="onConfirmCancel"
        @close="orderToCancel = null"
    />
  </section>
</template>
