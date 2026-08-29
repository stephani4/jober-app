<script setup lang="ts">
defineSlots<{
  default(): unknown
  hero?(): unknown
  header?(): unknown
  footer?(): unknown
}>()

defineProps<{
  viewport?: boolean
}>()
</script>

<template>
  <div
    class="bg-surface-muted text-text-primary dark:bg-zinc-950 dark:text-zinc-100"
    :class="viewport ? 'h-dvh overflow-hidden' : 'min-h-screen'"
  >
    <div
      class="mx-auto flex w-full max-w-lg flex-col"
      :class="viewport ? 'h-full' : 'min-h-screen'"
    >
      <slot name="hero" />
      <slot name="header" />
      <div class="flex min-h-0 flex-1 flex-col">
        <slot />
      </div>
      <slot name="footer" />
    </div>
  </div>
</template>
