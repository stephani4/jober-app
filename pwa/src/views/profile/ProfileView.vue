<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth, usePwaInstall, useWebPush } from '@/composables'

const router = useRouter()
const { logout } = useAuth()
const { canInstall, isStandalone, isIos, install } = usePwaInstall()
const { supported, subscribed, busy, error, permission, enable, disable } = useWebPush()

const showInstall = computed(() => canInstall.value || (isIos.value && !isStandalone.value))

const pushLabel = computed(() => {
  if (!supported.value) {
    return 'Браузер не поддерживает'
  }
  if (permission.value === 'denied') {
    return 'Запрещены в браузере'
  }
  return subscribed.value ? 'Включены на этом устройстве' : 'Выключены'
})

const menuItems = [
  { label: 'Редактировать профиль', icon: 'user' },
  { label: 'Настройки', icon: 'settings' },
  { label: 'Помощь', icon: 'help' },
] as const

async function onInstall(): Promise<void> {
  if (canInstall.value) {
    await install()
  }
}

async function onTogglePush(): Promise<void> {
  if (subscribed.value) {
    await disable()
    return
  }
  await enable()
}

async function onLogout(): Promise<void> {
  await logout()
  await router.replace({ name: 'login' })
}
</script>

<template>
  <section class="space-y-3">
    <button
      v-if="showInstall"
      type="button"
      class="flex w-full items-center justify-between rounded-card border border-border-subtle bg-surface-card px-4 py-4 text-left text-text-primary shadow-[var(--shadow-card)] transition hover:bg-surface-muted dark:border-white/10 dark:bg-zinc-900 dark:hover:bg-zinc-800"
      @click="onInstall"
    >
      <span class="min-w-0">
        <span class="block">Установить приложение</span>
        <span class="mt-0.5 block text-sm text-text-secondary">
          {{
            canInstall
              ? 'Ярлык на домашнем экране'
              : 'Поделиться → На экран «Домой»'
          }}
        </span>
      </span>
      <svg
        viewBox="0 0 24 24"
        class="h-5 w-5 shrink-0 text-text-secondary"
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
      v-if="supported"
      type="button"
      class="flex w-full items-center justify-between rounded-card border border-border-subtle bg-surface-card px-4 py-4 text-left text-text-primary shadow-[var(--shadow-card)] transition hover:bg-surface-muted disabled:opacity-60 dark:border-white/10 dark:bg-zinc-900 dark:hover:bg-zinc-800"
      :disabled="busy || permission === 'denied'"
      @click="onTogglePush"
    >
      <span class="min-w-0">
        <span class="block">Уведомления на телефон</span>
        <span class="mt-0.5 block text-sm text-text-secondary">{{ pushLabel }}</span>
        <span v-if="error" class="mt-1 block text-sm text-accent-danger">{{ error }}</span>
      </span>
      <span
        class="relative h-7 w-12 shrink-0 rounded-full transition"
        :class="subscribed ? 'bg-accent-nav' : 'bg-surface-muted dark:bg-zinc-700'"
        aria-hidden="true"
      >
        <span
          class="absolute top-0.5 h-6 w-6 rounded-full bg-white shadow transition"
          :class="subscribed ? 'left-5' : 'left-0.5'"
        />
      </span>
    </button>

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
