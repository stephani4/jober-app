<script setup lang="ts">
import { RouterView } from 'vue-router'
import ThemeToggle from '@/components/ThemeToggle.vue'
import AuthBenefitsPanel from '@/components/auth/AuthBenefitsPanel.vue'
import AuthJoinCard from '@/components/auth/AuthJoinCard.vue'
</script>

<template>
  <div class="min-h-dvh bg-surface-muted text-text-primary dark:bg-zinc-950 dark:text-zinc-100">
    <div class="grid min-h-dvh lg:grid-cols-2">
      <!-- Левая часть: страницы входа и регистрации -->
      <section class="flex flex-col px-4 pb-6 pt-4 sm:px-6 lg:px-8 bg-[var(--color-surface-muted)] dark:bg-zinc-950">
        <div class="flex justify-end">
          <ThemeToggle />
        </div>

        <main class="flex min-h-0 flex-1 flex-col items-center justify-center">
          <RouterView />
          <AuthJoinCard class="hidden lg:flex" />
        </main>

        <footer class="pt-6 text-center text-xs text-text-secondary">
          Делег — доска заданий для заказчиков и исполнителей
        </footer>
      </section>

      <!-- Правая часть: слайдер с плюсами приложения. Видимость (hidden lg:flex) и центрирование заданы внутри панели -->
      <AuthBenefitsPanel />
    </div>
  </div>
</template>
