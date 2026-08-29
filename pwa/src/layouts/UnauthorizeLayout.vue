<script setup lang="ts">
import { RouterView } from 'vue-router'
import Layout from '@/layouts/Layout.vue'
import ThemeToggle from '@/components/ThemeToggle.vue'
</script>

<template>
  <Layout>
    <template #header>
      <div class="flex justify-end px-4 pt-4">
        <ThemeToggle />
      </div>
    </template>

    <main class="flex flex-1 items-center justify-center px-4 py-6">
      <RouterView />
    </main>

    <template #footer>
      <footer class="px-4 pb-6 text-center text-xs text-text-secondary">
        Jober — доска заданий для заказчиков и исполнителей
      </footer>
    </template>
  </Layout>
</template>
