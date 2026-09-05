<script setup lang="ts">
import { useAuth, usePwaInstall, useWebPush } from '@/composables'

const { isAuthenticated } = useAuth()
const { canInstall, install } = usePwaInstall()
const { showBanner, busy, enable, dismissBanner } = useWebPush()
</script>

<template>
  <div
    v-if="isAuthenticated && (canInstall || showBanner)"
    class="space-y-2"
  >
    <div
      v-if="canInstall"
      class="flex items-center justify-between gap-3 rounded-2xl border border-border-subtle bg-surface-card px-4 py-3 shadow-[var(--shadow-card)] dark:border-white/10 dark:bg-zinc-900"
    >
      <p class="text-sm text-text-primary dark:text-zinc-100">Установите Jober на устройство</p>
      <button
        type="button"
        class="shrink-0 rounded-full bg-accent-nav px-3 py-1.5 text-sm text-white"
        @click="install()"
      >
        Установить
      </button>
    </div>

    <div
      v-if="showBanner"
      class="flex items-start justify-between gap-3 rounded-2xl border border-border-subtle bg-surface-card px-4 py-3 shadow-[var(--shadow-card)] dark:border-white/10 dark:bg-zinc-900"
    >
      <p class="text-sm text-text-primary dark:text-zinc-100">
        Включите уведомления, чтобы не пропустить заказы на телефоне
      </p>
      <div class="flex shrink-0 items-center gap-2">
        <button
          type="button"
          class="text-sm text-text-secondary"
          @click="dismissBanner()"
        >
          Позже
        </button>
        <button
          type="button"
          class="rounded-full bg-accent-nav px-3 py-1.5 text-sm text-white disabled:opacity-50"
          :disabled="busy"
          @click="enable()"
        >
          Включить
        </button>
      </div>
    </div>
  </div>
</template>
