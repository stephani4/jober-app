<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import NotificationBadge from '@/components/NotificationBadge.vue'
import { useAuth, useNotifications, useOrderChat, useOrderDecline, useOrders } from '@/composables'

const route = useRoute()
const router = useRouter()
const { user, hasRole } = useAuth()
const { availableExecutorsCount } = useOrders()
const { unreadCount } = useNotifications()
const { open: chatOpen, unreadCount: chatUnread, showChat, toggle: toggleChat, close: closeChat } = useOrderChat()
const decline = useOrderDecline()

const title = computed(() => (route.meta.title as string | undefined) ?? 'Jober')

// Главная (/orders) — hero по home-baseline: аватар, колокольчик, приветствие, поиск, quick actions.
const isHome = computed(() => route.name === 'orders')

const firstName = computed(() => {
  const name = user.value?.name?.trim() ?? ''
  if (!name) {
    return 'Пользователь'
  }
  return name.split(/\s+/)[0]
})

// На экране выполнения чат-кнопка слева, а справа — отказ от выполнения.
const chatOnLeft = computed(() => route.name === 'order-execute')
const showDecline = computed(() => route.name === 'order-execute')

const canSearch = computed(() => hasRole(['executor']))

/** Счётчик свободных исполнителей виден только заказчику: исполнителю место занимает «Найти заказ». */
const showAvailableExecutors = computed(() => hasRole(['customer']))

const showBack = computed(() => Boolean(route.meta.showBack))

const leftAction = computed<'chat' | 'back' | 'none'>(() => {
  if (showChat.value && (chatOpen.value || chatOnLeft.value)) {
    return 'chat'
  }
  if (showBack.value) {
    return 'back'
  }
  return 'none'
})

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

function goToSearch(): void {
  void router.push({ name: 'search' })
}

function goToCreate(): void {
  void router.push({ name: 'order-create' })
}

function goToProfile(): void {
  void router.push({ name: 'profile' })
}
</script>

<template>
  <header
    v-if="isHome"
    class="relative overflow-hidden rounded-b-3xl bg-surface-hero px-4 pb-6 pt-4 text-text-on-hero dark:bg-zinc-950"
  >
    <div class="pointer-events-none absolute inset-0 texture-lines opacity-40" aria-hidden="true" />

    <div class="flex items-center justify-between gap-3">
      <button
        type="button"
        class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-full border border-border-hero-control bg-white/10 text-sm text-text-on-hero transition hover:bg-white/15"
        aria-label="Профиль"
        @click="goToProfile"
      >
        <img v-if="user?.avatar_url" :src="user.avatar_url" alt="" class="h-full w-full object-cover" />
        <template v-else>{{ user?.name?.charAt(0)?.toUpperCase() ?? '?' }}</template>
      </button>
      <h1 class="sr-only">{{ title }}</h1>
      <button
        type="button"
        class="relative flex h-11 w-11 items-center justify-center rounded-full border border-border-hero-control bg-white/5 text-text-on-hero transition hover:bg-white/10"
        aria-label="Уведомления"
        @click="goToNotifications"
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
          <path d="M10 6h4M10 6v7M14 6v7M10 13h-3v3M14 13h3v3M7 16h10M12 16v4" />
        </svg>
        <NotificationBadge
          class="absolute right-1 top-1"
          :count="unreadCount"
        />
      </button>
    </div>

    <p class="mt-5 text-sm text-text-on-hero-muted">
      Привет,
    </p>
    <p class="text-2xl font-semibold text-text-on-hero">
      {{ firstName }}! 👋
    </p>

    <div class="mt-3 grid grid-cols-2 gap-3">
      <button
        type="button"
        class="flex min-w-0 items-center gap-2 rounded-2xl bg-white px-3 py-3 text-left shadow-[var(--shadow-card)]"
        @click="goToCreate"
      >
        <span
          class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-accent-nav text-white"
          aria-hidden="true"
        >
          <svg
            viewBox="0 0 24 24"
            class="h-5 w-5"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path d="M12 5v14M5 12h14" />
          </svg>
        </span>
        <span class="truncate text-sm font-semibold text-black">Создать заказ</span>
      </button>
      <button
        v-if="canSearch"
        type="button"
        class="flex min-w-0 items-center gap-2 rounded-2xl bg-white px-3 py-3 text-left shadow-[var(--shadow-card)]"
        @click="goToSearch"
      >
        <span
          class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-accent-nav text-white"
          aria-hidden="true"
        >
          <svg
            viewBox="0 0 24 24"
            class="h-5 w-5"
            fill="none"
            stroke="currentColor"
            stroke-width="1.75"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <circle cx="11" cy="11" r="6" />
            <path d="m20 20-3.5-3.5" />
          </svg>
        </span>
        <span class="truncate text-sm font-semibold text-black">Найти заказ</span>
      </button>
      <div
        v-else-if="showAvailableExecutors"
        class="flex min-w-0 items-center gap-2 rounded-2xl bg-white px-3 py-3 shadow-[var(--shadow-card)]"
        aria-label="Свободные исполнители, ожидающие заказ"
      >
        <span
          class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-accent-nav text-white"
          aria-hidden="true"
        >
          <svg
            viewBox="0 0 24 24"
            class="h-5 w-5"
            fill="none"
            stroke="currentColor"
            stroke-width="1.75"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <circle cx="10" cy="8" r="3.2" />
            <path d="M4 19v-1.5A3.5 3.5 0 0 1 7.5 14h5a3.5 3.5 0 0 1 3.5 3.5V19" />
            <path d="M16 5.5a3.2 3.2 0 0 1 0 5M17.5 14h1A3.5 3.5 0 0 1 22 17.5V19" />
          </svg>
        </span>
        <span class="min-w-0">
          <span class="block text-lg font-semibold leading-none text-black">
            {{ availableExecutorsCount }}
          </span>
          <span class="mt-1 block truncate text-[10px] leading-none text-text-secondary">
            Ждут заказ
          </span>
        </span>
      </div>
    </div>
  </header>

  <header
    v-else
    class="rounded-b-3xl bg-surface-hero px-4 pb-6 pt-4 text-text-on-hero dark:bg-zinc-950"
  >
    <div class="flex items-center justify-between gap-3">
      <button
        v-if="leftAction === 'chat'"
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
      <button
        v-else-if="leftAction === 'back'"
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
        v-if="showDecline && !chatOpen"
        type="button"
        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-red-400/40 bg-red-500/15 text-red-100 transition hover:bg-red-500/25"
        aria-label="Отказаться от выполнения"
        @click="decline.request()"
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
          <path d="M18 6 6 18M6 6l12 12" />
        </svg>
      </button>
      <button
        v-else-if="showChat && !chatOnLeft"
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
  </header>
</template>

<style scoped>
.texture-lines {
  background-image: repeating-linear-gradient(
    135deg,
    rgb(255 255 255 / 0.06) 0px,
    rgb(255 255 255 / 0.06) 2px,
    transparent 2px,
    transparent 14px
  );
}
</style>
