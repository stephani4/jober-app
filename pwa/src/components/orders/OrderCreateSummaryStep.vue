<script setup lang="ts">
import { computed } from 'vue'
import type { DraftOrderPoint } from '@/stores/orderCreate'

const props = defineProps<{
  points: DraftOrderPoint[]
  cost: number | null
  description: string
  orderTypeName?: string | null
  singlePoint?: boolean
}>()

const costLabel = computed(() =>
  new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(
    props.cost ?? 0,
  ),
)

/** Короткая строка данных доступа в здание, например «Подъезд 2 · Этаж 5 · Кв. 12». */
function accessParts(point: DraftOrderPoint): string[] {
  const parts: string[] = []
  if (point.entrance != null) {
    parts.push(`Подъезд ${point.entrance}`)
  }
  if (point.floor != null) {
    parts.push(`Этаж ${point.floor}`)
  }
  if (point.apartment) {
    parts.push(`Кв. ${point.apartment}`)
  }
  if (point.intercom != null) {
    parts.push(`Домофон ${point.intercom}`)
  }
  return parts
}
</script>

<template>
  <div class="space-y-4">
    <section
      class="rounded-card border border-border-subtle bg-surface-card p-4 shadow-[var(--shadow-card)] dark:border-white/10 dark:bg-zinc-900"
    >
      <p v-if="orderTypeName" class="text-sm text-text-secondary">{{ orderTypeName }}</p>
      <p class="text-sm text-text-secondary" :class="orderTypeName ? 'mt-3' : ''">Стоимость</p>
      <p class="mt-1 text-2xl text-text-primary dark:text-zinc-100">{{ costLabel }}</p>
      <p class="mt-3 text-sm text-text-secondary">Примечание</p>
      <p class="mt-1 text-text-primary dark:text-zinc-100">
        {{ description.trim() || 'Без примечания' }}
      </p>
    </section>

    <section class="space-y-2">
      <p class="text-sm text-text-secondary">{{ singlePoint ? 'Точка доставки' : 'Точки маршрута' }}</p>
      <article
        v-for="(point, index) in points"
        :key="point.clientId"
        class="rounded-card border border-border-subtle bg-surface-card p-4 dark:border-white/10 dark:bg-zinc-900"
      >
        <p class="text-sm text-text-secondary">{{ singlePoint ? 'Куда доставить' : `Точка ${index + 1}` }}</p>
        <p class="mt-1 text-text-primary dark:text-zinc-100">{{ point.description }}</p>
        <p class="mt-1 text-sm text-text-secondary">
          {{ point.address || `${point.lat}, ${point.lon}` }}
        </p>
        <p v-if="accessParts(point).length" class="mt-2 text-xs text-text-secondary">
          {{ accessParts(point).join(' · ') }}
        </p>
        <ul v-if="point.files.length" class="mt-2 space-y-1">
          <li
            v-for="file in point.files"
            :key="file.id"
            class="truncate text-xs text-text-secondary"
          >
            {{ file.name }}
          </li>
        </ul>
      </article>
    </section>
  </div>
</template>
