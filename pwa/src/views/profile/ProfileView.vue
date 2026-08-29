<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables'

const router = useRouter()
const { logout } = useAuth()

const menuItems = [
  { label: 'Редактировать профиль', icon: 'user' },
  { label: 'Настройки', icon: 'settings' },
  { label: 'Помощь', icon: 'help' },
] as const

async function onLogout(): Promise<void> {
  await logout()
  await router.replace({ name: 'login' })
}
</script>

<template>
  <section class="space-y-3">
    <button
      v-for="item in menuItems"
      :key="item.label"
      type="button"
      class="flex w-full items-center justify-between rounded-card border border-border-subtle bg-surface-card px-4 py-4 text-left text-text-primary shadow-[var(--shadow-card)] transition hover:bg-surface-muted dark:border-white/10 dark:bg-zinc-900 dark:hover:bg-zinc-800"
    >
      <span>{{ item.label }}</span>
      <svg
        viewBox="0 0 24 24"
        class="h-5 w-5 text-text-secondary"
        fill="none"
        stroke="currentColor"
        stroke-width="1.75"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
      >
        <path d="m9 18 6-6-6-6" />
      </svg>
    </button>

    <button
      type="button"
      class="flex w-full items-center justify-between rounded-card border border-border-subtle bg-surface-card px-4 py-4 text-left text-accent-danger shadow-[var(--shadow-card)] transition hover:bg-red-50 dark:border-white/10 dark:bg-zinc-900 dark:hover:bg-red-950/30"
      @click="onLogout"
    >
      <span>Выйти</span>
      <svg
        viewBox="0 0 24 24"
        class="h-5 w-5"
        fill="none"
        stroke="currentColor"
        stroke-width="1.75"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
      >
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
        <path d="m16 17 5-5-5-5" />
        <path d="M21 12H9" />
      </svg>
    </button>
  </section>
</template>
