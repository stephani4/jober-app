<script setup lang="ts">
import OrderExecutorBadge from '@/components/orders/OrderExecutorBadge.vue'
import OrderRatingStars from '@/components/orders/OrderRatingStars.vue'
import PointAccessDetails from '@/components/orders/PointAccessDetails.vue'
import type { Order, OrderExecutor } from '@/schemas/order'

defineProps<{
  stepLabel: string
  description: string
  address: string | null
  /** Данные доступа в здание текущей точки исполнителя (подъезд, этаж, квартира, домофон). */
  accessPoint?: {
    entrance?: number | null
    floor?: number | null
    apartment?: string | null
    intercom?: number | null
  } | null
  error: string
  /** Заказ на этапе подтверждения: все точки пройдены, ждём код от автора. */
  awaitingConfirmation?: boolean
  /** Код подтверждения; показывается только автору на этапе confirmation. */
  confirmationNumber?: string | null
  /** Исполнитель, за выполнением которого наблюдает автор. */
  executor?: OrderExecutor | null
  orderId?: number | null
  rating?: number | null
  canRate?: boolean
}>()

const emit = defineEmits<{
  rated: [order: Order]
}>()
</script>

<template>
  <section
    class="rounded-t-3xl border border-border-subtle bg-surface-card px-4 pb-[max(1.25rem,env(safe-area-inset-bottom))] pt-4 shadow-[var(--shadow-card)] dark:border-white/10 dark:bg-zinc-900"
  >
    <!-- Кто выполняет заказ: аватар и имя исполнителя. -->
    <OrderExecutorBadge
      v-if="executor"
      :executor="executor"
      label="Исполнитель"
      class="mb-3 border-b border-border-subtle pb-3 dark:border-white/10"
    />
    <p class="text-sm text-text-secondary">{{ stepLabel }}</p>
    <p class="mt-1 text-base text-text-primary dark:text-zinc-100">
      {{ description }}
    </p>
    <p v-if="address" class="mt-1 text-sm text-text-secondary">
      {{ address }}
    </p>
    <PointAccessDetails :point="accessPoint" />
    <!-- Этап подтверждения: автору показываем код, который нужно передать исполнителю. -->
    <p
      v-if="confirmationNumber"
      class="mt-3 rounded-xl border border-accent-nav/30 bg-accent-nav/10 px-3 py-2 text-sm text-text-primary dark:border-accent-nav/40 dark:bg-accent-nav/20 dark:text-zinc-100"
    >
      Код подтверждения:
      <span class="font-semibold tracking-[0.3em]">{{ confirmationNumber }}</span>
      <span class="ml-1 text-xs text-text-secondary">— сообщите его исполнителю</span>
    </p>
    <OrderRatingStars
      v-if="orderId && (canRate || rating)"
      :order-id="orderId"
      :rating="rating"
      :can-rate="canRate"
      @rated="emit('rated', $event)"
    />
    <p
      v-if="!error && !awaitingConfirmation"
      class="mt-3 text-sm text-text-secondary"
    >
      Исполнитель движется по маршруту.
    </p>
    <p v-if="error" class="mt-3 text-sm text-accent-danger">
      {{ error }}
    </p>
  </section>
</template>
