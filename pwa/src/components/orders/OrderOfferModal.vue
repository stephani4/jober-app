<script setup lang="ts">
import { computed } from 'vue'
import { useOrderOffer } from '@/composables/useOrderOffer'

const { order, ttlMs, accept, dismiss } = useOrderOffer()

const costLabel = computed(() => {
  if (!order.value) {
    return ''
  }
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    maximumFractionDigits: 0,
  }).format(order.value.cost)
})
</script>

<template>
  <Teleport to="body">
    <div
      v-if="order"
      class="fixed inset-0 z-[90] flex items-end justify-center bg-black/40 px-4 pb-8 pt-16 sm:items-center"
    >
      <article
        class="w-full max-w-lg overflow-hidden rounded-3xl bg-surface-card shadow-[var(--shadow-card)] dark:bg-zinc-900"
        role="dialog"
        aria-modal="true"
        aria-labelledby="order-offer-title"
      >
        <div :key="order.id" class="h-1 bg-zinc-100 dark:bg-zinc-800">
          <div
            class="offer-timer h-full bg-accent-nav"
            :style="{ animationDuration: `${ttlMs}ms` }"
          />
        </div>

        <div class="px-5 pb-5 pt-4">
          <p class="text-sm text-text-secondary">
            Новый заказ
            <span v-if="order.order_type?.name"> · {{ order.order_type.name }}</span>
            · {{ costLabel }}
          </p>
          <h2 id="order-offer-title" class="mt-1 text-lg text-text-primary dark:text-zinc-100">
            {{ order.description || 'Заказ без примечания' }}
          </h2>
          <ol v-if="order.points.length" class="mt-3 space-y-1 text-sm text-text-secondary">
            <li v-for="point in order.points.slice(0, 3)" :key="point.id">
              {{ point.position }}. {{ point.description }}
            </li>
            <li v-if="order.points.length > 3">ещё {{ order.points.length - 3 }}…</li>
          </ol>

          <div class="mt-5 flex gap-3">
            <button
              type="button"
              class="flex-1 rounded-xl border border-border-subtle px-4 py-3 text-text-primary dark:border-white/10 dark:text-zinc-100"
              @click="dismiss()"
            >
              Позже
            </button>
            <button
              type="button"
              class="flex-1 rounded-xl bg-accent-nav px-4 py-3 text-white"
              @click="accept()"
            >
              Приступить к выполнению
            </button>
          </div>
        </div>
      </article>
    </div>
  </Teleport>
</template>

<style scoped>
.offer-timer {
  width: 100%;
  animation-name: offer-shrink;
  animation-timing-function: linear;
  animation-fill-mode: forwards;
}

@keyframes offer-shrink {
  from {
    width: 100%;
  }
  to {
    width: 0%;
  }
}
</style>
