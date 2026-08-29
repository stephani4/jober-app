<script setup lang="ts">
import { computed } from 'vue'
import type { DraftOrderPoint } from '@/stores/orderCreate'

const props = defineProps<{
  points: DraftOrderPoint[]
  cost: number | null
  description: string
}>()

const costLabel = computed(() =>
  new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(
    props.cost ?? 0,
  ),
)
</script>

<template>
  <div class="space-y-4">
    <section
      class="rounded-card border border-border-subtle bg-surface-card p-4 shadow-[var(--shadow-card)] dark:border-white/10 dark:bg-zinc-900"
    >
      <p class="text-sm text-text-secondary">Стоимость</p>
      <p class="mt-1 text-2xl text-text-primary dark:text-zinc-100">{{ costLabel }}</p>
      <p class="mt-3 text-sm text-text-secondary">Примечание</p>
      <p class="mt-1 text-text-primary dark:text-zinc-100">
        {{ description.trim() || 'Без примечания' }}
      </p>
    </section>

    <section class="space-y-2">
      <p class="text-sm text-text-secondary">Точки маршрута</p>
      <article
        v-for="(point, index) in points"
        :key="point.clientId"
        class="rounded-card border border-border-subtle bg-surface-card p-4 dark:border-white/10 dark:bg-zinc-900"
      >
        <p class="text-sm text-text-secondary">Точка {{ index + 1 }}</p>
        <p class="mt-1 text-text-primary dark:text-zinc-100">{{ point.description }}</p>
        <p class="mt-1 text-sm text-text-secondary">
          {{ point.address || `${point.lat}, ${point.lon}` }}
        </p>
      </article>
    </section>
  </div>
</template>
