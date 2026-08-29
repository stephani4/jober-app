<script setup lang="ts">
import { computed } from 'vue'
import { orderStatusLabel, type Order, type OrderStatus } from '@/schemas/order'

const props = defineProps<{
  order: Order
  startable?: boolean
  watchable?: boolean
  chatable?: boolean
}>()

const emit = defineEmits<{
  start: []
  watch: []
  chat: []
}>()

const status = computed<OrderStatus>(() => props.order.status ?? 'wait')

const statusClass = computed(() => {
  if (status.value === 'process') {
    return 'bg-accent-nav text-white'
  }
  if (status.value === 'complete') {
    return 'border border-border-subtle bg-surface-muted text-text-secondary dark:border-white/10 dark:bg-zinc-800'
  }
  if (status.value === 'moderate') {
    return 'border border-border-subtle bg-surface-muted text-text-primary dark:border-white/10 dark:bg-zinc-800 dark:text-zinc-200'
  }
  if (status.value === 'cancel') {
    return 'border border-accent-danger/40 bg-red-50 text-accent-danger dark:border-red-500/40 dark:bg-red-950/40'
  }
  return 'border border-border-subtle bg-white text-text-secondary dark:border-white/10 dark:bg-zinc-950 dark:text-zinc-300'
})

const pointsLabel = computed(() => {
  const count = props.order.points.length
  if (count === 1) {
    return '1 точка'
  }
  if (count >= 2 && count <= 4) {
    return `${count} точки`
  }
  return `${count} точек`
})

const costLabel = computed(() =>
  new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(
    props.order.cost,
  ),
)
</script>

<template>
  <article
    class="rounded-card border border-border-subtle bg-surface-card p-4 shadow-[var(--shadow-card)] dark:border-white/10 dark:bg-zinc-900"
  >
    <div class="flex items-start justify-between gap-3">
      <p class="text-base text-text-primary dark:text-zinc-100">
        {{ order.description || 'Заказ без примечания' }}
      </p>
      <p class="shrink-0 text-base text-text-primary dark:text-zinc-100">
        {{ costLabel }}
      </p>
    </div>
    <p class="mt-2 text-sm text-text-secondary">
      {{ pointsLabel }}
      <span v-if="order.user?.name"> · {{ order.user.name }}</span>
    </p>
    <p
      class="mt-3 inline-flex rounded-full px-3 py-1 text-xs"
      :class="statusClass"
    >
      {{ orderStatusLabel[status] }}
    </p>
    <p
      v-if="order.status === 'cancel' && order.reason"
      class="mt-3 text-sm text-accent-danger"
    >
      {{ order.reason }}
    </p>
    <ol v-if="order.points.length" class="mt-3 space-y-1 text-sm text-text-secondary">
      <li v-for="point in order.points" :key="point.id">
        {{ point.position }}. {{ point.description }}
      </li>
    </ol>
    <button
      v-if="startable"
      type="button"
      class="mt-4 w-full rounded-xl bg-accent-nav px-4 py-3 text-white"
      @click="emit('start')"
    >
      Приступить к выполнению
    </button>
    <button
      v-if="watchable"
      type="button"
      class="mt-4 w-full rounded-xl border border-border-subtle px-4 py-3 text-sm text-text-primary dark:border-white/10 dark:text-zinc-100"
      @click="emit('watch')"
    >
      Показать выполнение
    </button>
    <button
      v-if="chatable"
      type="button"
      class="mt-4 w-full rounded-xl border border-border-subtle px-4 py-3 text-sm text-text-primary dark:border-white/10 dark:text-zinc-100"
      @click="emit('chat')"
    >
      Чат
    </button>
  </article>
</template>
