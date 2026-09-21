<script setup lang="ts">
import { computed } from 'vue'
import OrderExecutorBadge from '@/components/orders/OrderExecutorBadge.vue'
import OrderRatingStars from '@/components/orders/OrderRatingStars.vue'
import type { Order } from '@/schemas/order'

const props = defineProps<{
  order: Order
  watchable?: boolean
  /** Код подтверждения; передаётся только автору заказа на этапе confirmation. */
  confirmationCode?: string | null
}>()

const emit = defineEmits<{
  watch: []
}>()

/** Метка маршрута: «от → до» по точкам либо количество точек. */
const routeLabel = computed(() => {
  const points = props.order.points
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
</script>

<template>
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
          ID: {{ order.id }}
          <span class="rounded-full bg-surface-muted px-2 py-0.5 text-[10px] font-medium text-text-secondary dark:bg-zinc-800 dark:text-zinc-400">
            Выполняется
          </span>
        </p>
        <p class="mt-1 line-clamp-2 text-sm text-text-primary dark:text-zinc-100">
          {{ order.description || 'Заказ без примечания' }}
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

    <!-- Исполнитель, выполняющий заказ. -->
    <div
      v-if="order.executor"
      class="mt-3 rounded-xl border border-border-subtle bg-surface-muted/60 px-3 py-2 dark:border-white/10 dark:bg-zinc-800/60"
    >
      <OrderExecutorBadge :executor="order.executor" label="Исполнитель" />
    </div>

    <!-- Этап подтверждения: автору показываем код, который нужно передать исполнителю. -->
    <p
      v-if="confirmationCode"
      class="mt-3 rounded-xl border border-accent-nav/30 bg-accent-nav/10 px-3 py-2 text-sm text-text-primary dark:border-accent-nav/40 dark:bg-accent-nav/20 dark:text-zinc-100"
    >
      Код подтверждения:
      <span class="font-semibold tracking-[0.3em]">{{ confirmationCode }}</span>
      <span class="ml-1 text-xs text-text-secondary">— сообщите его исполнителю</span>
    </p>

    <OrderRatingStars
      v-if="order.can_rate || order.rating"
      :order-id="order.id"
      :rating="order.rating"
      :can-rate="order.can_rate"
    />

    <button
      v-if="watchable"
      type="button"
      class="mt-4 w-full rounded-xl border border-border-subtle px-4 py-3 text-sm text-text-primary dark:border-white/10 dark:text-zinc-100"
      @click="emit('watch')"
    >
      Смотреть выполнение
    </button>
  </article>
</template>