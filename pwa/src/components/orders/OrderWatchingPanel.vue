<script setup lang="ts">
defineProps<{
  stepLabel: string
  description: string
  address: string | null
  error: string
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
    <p v-if="!error" class="mt-3 text-sm text-text-secondary">Исполнитель движется по маршруту.</p>
    <p v-if="error" class="mt-3 text-sm text-accent-danger">
      {{ error }}
    </p>
  </section>
</template>
