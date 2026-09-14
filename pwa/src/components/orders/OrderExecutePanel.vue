<script setup lang="ts">
import PointAccessDetails from '@/components/orders/PointAccessDetails.vue'

defineProps<{
  stepLabel: string
  description: string
  address: string | null
  /** Данные доступа в здание текущей точки (подъезд, этаж, квартира, домофон). */
  accessPoint?: {
    entrance?: number | null
    floor?: number | null
    apartment?: string | null
    intercom?: number | null
  } | null
  isLast: boolean
  submitting: boolean
  error: string
  geoError: string
}>()

const emit = defineEmits<{
  complete: []
}>()
</script>

<template>
  <section
    class="rounded-t-3xl border border-border-subtle bg-surface-card px-4 pb-[max(1.25rem,env(safe-area-inset-bottom))] pt-4 shadow-[var(--shadow-card)] dark:border-white/10 dark:bg-zinc-900"
  >
    <p class="text-sm text-text-secondary">{{ stepLabel }}</p>
    <p class="mt-1 text-base text-text-primary dark:text-zinc-100">
      {{ description }}
    </p>
    <p v-if="address" class="mt-1 text-sm text-text-secondary">
      {{ address }}
    </p>
    <PointAccessDetails :point="accessPoint" />
    <p v-if="geoError" class="mt-3 text-sm text-accent-danger">
      {{ geoError }}
    </p>
    <p
      v-if="error"
      class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:border-rose-400/20 dark:bg-rose-500/10 dark:text-rose-200"
    >
      {{ error }}
    </p>
    <button
      type="button"
      class="mt-4 w-full rounded-full bg-accent-nav px-4 py-3 text-white transition hover:bg-accent-nav-hover disabled:opacity-50"
      :disabled="submitting"
      @click="emit('complete')"
    >
      {{ submitting ? 'Сохраняем…' : isLast ? 'Завершить выполнение' : 'Выполнено' }}
    </button>
  </section>
</template>
