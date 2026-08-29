<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import NotificationBadge from '@/components/NotificationBadge.vue'
import { useAuth, useNotifications, useOrderChat } from '@/composables'

const route = useRoute()
const router = useRouter()
const { user } = useAuth()
const { unreadCount } = useNotifications()
const { open: chatOpen, unreadCount: chatUnread, showChat, toggle: toggleChat, close: closeChat } = useOrderChat()

const title = computed(() => (route.meta.title as string | undefined) ?? 'Jober')
const variant = computed(() => route.meta.hero as string | undefined)
const showBack = computed(
  () => Boolean(route.meta.showBack) || (showChat.value && chatOpen.value),
)

function onBack(): void {
  if (chatOpen.value) {
    closeChat()
    return
  }
  router.back()
}

function goToNotifications(): void {
  void router.push({ name: 'notifications' })
}

function goToHistory(): void {
  void router.push({ name: 'order-history' })
}
</script>

<template>
  <header
    class="bg-surface-hero rounded-b-hero px-4 pb-6 pt-4 text-text-on-hero dark:bg-zinc-950"
  >
    <div class="flex items-center justify-between gap-3">
      <button
        v-if="showBack"
        type="button"
        class="flex h-11 w-11 items-center justify-center rounded-full border border-border-hero-control bg-white/5 text-text-on-hero transition hover:bg-white/10"
        :aria-label="chatOpen ? 'К заказу' : 'Назад'"
        @click="onBack"
      >
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
          <path d="m15 18-6-6 6-6" />
        </svg>
      </button>
      <div v-else class="h-11 w-11 shrink-0" aria-hidden="true" />

      <h1 class="truncate text-center text-lg text-text-on-hero">
        {{ title }}
      </h1>

      <button
        v-if="showChat"
        type="button"
        class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-border-hero-control bg-white/5 text-text-on-hero transition hover:bg-white/10"
        :aria-label="chatOpen ? 'Закрыть чат' : 'Чат заказа'"
        :aria-pressed="chatOpen"
        @click="toggleChat"
      >
        <svg
          v-if="chatOpen"
          viewBox="0 0 24 24"
          class="h-5 w-5"
          fill="none"
          stroke="currentColor"
          stroke-width="1.75"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <path d="M18 6 6 18M6 6l12 12" />
        </svg>
        <svg
          v-else
          viewBox="0 0 24 24"
          class="h-5 w-5"
          fill="none"
          stroke="currentColor"
          stroke-width="1.75"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <path d="M4 19V5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H8l-4 4Z" />
          <path d="M8 9h8M8 13h5" />
        </svg>
        <NotificationBadge
          class="absolute right-0 top-0"
          :count="chatUnread"
        />
      </button>
      <div v-else class="h-11 w-11 shrink-0" aria-hidden="true" />
    </div>

    <div v-if="variant === 'profile'" class="mt-6 text-center">
      <div
        class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/10 text-2xl text-text-on-hero"
        aria-hidden="true"
      >
        {{ user?.name?.charAt(0)?.toUpperCase() ?? '?' }}
      </div>
      <p class="mt-3 text-xl text-text-on-hero">
        {{ user?.name }}
      </p>
      <p class="mt-1 text-sm text-text-on-hero-muted">
        {{ user?.email }}
      </p>

      <div class="mt-6 grid grid-cols-3 gap-3">
        <button
          type="button"
          class="relative rounded-card border border-border-hero-control bg-white/5 px-3 py-4 text-sm text-text-on-hero transition hover:bg-white/10"
          @click="goToNotifications"
        >
          Уведомления
          <NotificationBadge
            class="absolute right-2 top-2"
            :count="unreadCount"
          />
        </button>
        <button
          type="button"
          class="rounded-card border border-border-hero-control bg-white/5 px-3 py-4 text-sm text-text-on-hero transition hover:bg-white/10"
        >
          Избранное
        </button>
        <button
          type="button"
          class="rounded-card border border-border-hero-control bg-white/5 px-3 py-4 text-sm text-text-on-hero transition hover:bg-white/10"
          @click="goToHistory"
        >
          История
        </button>
      </div>
    </div>
  </header>
</template>
