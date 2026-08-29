<script setup lang="ts">
import { RouterView } from 'vue-router'
import ThemeToggle from '@/components/ThemeToggle.vue'
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-surface-page px-4 dark:bg-zinc-950">
    <div class="absolute right-4 top-4">
      <ThemeToggle />
    </div>
    <RouterView />
  </div>
</template>
