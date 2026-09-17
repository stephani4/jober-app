<script setup lang="ts">
import { computed } from 'vue'
import type { OrderExecutor } from '@/schemas/order'

/**
 * Исполнитель, принявший заказ: аватар (или буква имени) и users.name.
 */
const props = defineProps<{
  executor: OrderExecutor | null
  /** Подпись слева от имени: исполнитель, в пути и т.п. */
  label?: string
}>()

const initial = computed(() => props.executor?.name?.trim().charAt(0).toUpperCase() || '?')
const name = computed(() => props.executor?.name?.trim() || 'Исполнитель')
</script>

<template>
  <div v-if="executor" class="flex items-center gap-2.5">
    <img
      v-if="executor.avatar_url"
      :src="executor.avatar_url"
      :alt="name"
      class="h-10 w-10 shrink-0 rounded-full object-cover ring-2 ring-accent-nav/40"
    />
    <span
      v-else
      class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-accent-nav/10 text-sm font-semibold text-text-primary ring-2 ring-accent-nav/40 dark:text-zinc-100"
      aria-hidden="true"
    >
      {{ initial }}
    </span>
    <span class="min-w-0">
      <span v-if="label" class="block text-xs text-text-secondary">{{ label }}</span>
      <span class="block truncate text-sm font-semibold text-text-primary dark:text-zinc-100">
        {{ name }}
      </span>
    </span>
  </div>
</template>