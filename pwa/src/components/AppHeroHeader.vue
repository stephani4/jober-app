<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppBrandLogo from '@/components/AppBrandLogo.vue'
import NotificationBadge from '@/components/NotificationBadge.vue'
import { useAuth, useNotifications, useOrderChat, useOrderDecline, useOrders } from '@/composables'
import { givenNameFromFio } from '@/utils/name'

const route = useRoute()
const router = useRouter()
const { user, hasRole } = useAuth()
const { items, availableExecutorsCount } = useOrders()
const { unreadCount } = useNotifications()
const { open: chatOpen, unreadCount: chatUnread, showChat, toggle: toggleChat, close: closeChat } = useOrderChat()
const decline = useOrderDecline()

const title = computed(() => (route.meta.title as string | undefined) ?? 'Делег')
const isHome = computed(() => route.name === 'orders')

const firstName = computed(() => {
  const given = givenNameFromFio(user.value?.name)
  return given || 'Пользователь'
})

const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour < 6) {
    return 'Доброй ночи'
  }
  if (hour < 12) {
    return 'Доброе утро'
  }
  if (hour < 18) {
    return 'Добрый день'
  }
  return 'Добрый вечер'
})

const chatOnLeft = computed(() => route.name === 'order-execute')
const showDecline = computed(() => route.name === 'order-execute')
const canSearch = computed(() => hasRole(['executor']))
const showAvailableExecutors = computed(() => hasRole(['customer']))
const showBack = computed(() => Boolean(route.meta.showBack))
const myOrdersCount = computed(() => items.value.length)
const activeChatOrderId = computed(() => items.value.find((order) => order.status === 'process')?.id ?? null)

const leftAction = computed<'chat' | 'back' | 'none'>(() => {
  if (showChat.value && (chatOpen.value || chatOnLeft.value)) {
    return 'chat'
  }
  if (showBack.value) {
    return 'back'
  }
  return 'none'
})

const iconBtnClass =
  'flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white text-text-primary shadow-[var(--shadow-card)] transition hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700'

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

function goToProfile(): void {
  void router.push({ name: 'profile' })
}

function goToHomeChat(): void {
  if (!activeChatOrderId.value) {
    return
  }
  void router.push({ name: 'order-chat', params: { orderId: String(activeChatOrderId.value) } })
}
</script>

<template>
  <header v-if="isHome" class="px-4 pb-2 pt-4">
    <div class="flex items-center justify-between gap-3">
      <div class="flex min-w-0 items-center gap-3">
        <button
          type="button"
          :class="iconBtnClass"
          class="overflow-hidden"
          aria-label="Профиль"
          @click="goToProfile"
        >
          <img v-if="user?.avatar_url" :src="user.avatar_url" alt="" class="h-full w-full object-cover" />
          <template v-else>{{ user?.name?.charAt(0)?.toUpperCase() ?? '?' }}</template>
        </button>
        <div class="min-w-0">
          <p class="text-xs text-text-secondary">{{ greeting }}</p>
          <p class="truncate text-lg font-semibold leading-tight text-text-primary dark:text-zinc-50">
            {{ firstName }} 👋
          </p>
        </div>
      </div>

      <div class="flex shrink-0 items-center gap-2">
        <button
          v-if="activeChatOrderId"
          type="button"
          class="flex h-11 items-center gap-1.5 rounded-full bg-white px-3.5 text-sm font-medium text-text-primary shadow-[var(--shadow-card)] dark:bg-zinc-800 dark:text-zinc-100"
          @click="goToHomeChat"
        >
          Чат
        </button>
        <button
          type="button"
          :class="iconBtnClass"
          class="relative"
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
            <path d="M6 9a6 6 0 1 1 12 0c0 7 3 7 3 9H3c0-2 3-2 3-9" />
            <path d="M10 20a2 2 0 0 0 4 0" />
          </svg>
          <NotificationBadge class="absolute right-1 top-1" :count="unreadCount" />
        </button>
      </div>
    </div>

    <h1 class="mt-5">
      <span class="sr-only">{{ title }}</span>
      <span class="flex h-8 max-w-[13.5rem] items-center sm:h-9 sm:max-w-[16rem]">
        <AppBrandLogo />
      </span>
    </h1>
    <p class="mt-3 text-2xl font-semibold leading-tight tracking-tight text-text-primary dark:text-zinc-50">
      Статус заказов<br />
      на сегодня.
    </p>

    <button
      v-if="canSearch"
      type="button"
      class="mt-5 flex w-full items-center gap-3 rounded-full bg-white py-2 pl-4 pr-2 text-left shadow-[var(--shadow-card)] dark:bg-zinc-800"
      @click="goToSearch"
    >
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
        <circle cx="11" cy="11" r="6" />
        <path d="m20 20-3.5-3.5" />
      </svg>
      <span class="min-w-0 flex-1 truncate text-sm text-text-secondary">Найти заказ</span>
      <span
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-surface-nav text-white"
        aria-hidden="true"
      >
        <svg
          viewBox="0 0 24 24"
          class="h-4 w-4"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <circle cx="11" cy="11" r="6" />
          <path d="m20 20-3.5-3.5" />
        </svg>
      </span>
    </button>

    <div class="mt-6">
      <p class="text-sm font-semibold text-text-primary dark:text-zinc-100">Обзор</p>
      <div class="mt-3 grid grid-cols-2 gap-3">
        <div class="rounded-3xl bg-white p-4 shadow-[var(--shadow-card)] dark:bg-zinc-900">
          <div class="flex items-start justify-between gap-2">
            <span
              class="flex h-9 w-9 items-center justify-center rounded-xl bg-surface-muted text-text-primary dark:bg-zinc-800 dark:text-zinc-100"
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
                <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" />
                <rect x="9" y="3" width="6" height="4" rx="1" />
              </svg>
            </span>
            <span class="text-xs text-text-secondary">Сегодня</span>
          </div>
          <p class="mt-4 text-3xl font-semibold leading-none text-text-primary dark:text-zinc-50">
            {{ myOrdersCount }}
          </p>
          <p class="mt-1 text-xs text-text-secondary">Активных заказов</p>
        </div>

        <button
          v-if="canSearch"
          type="button"
          class="rounded-3xl bg-white p-4 text-left shadow-[var(--shadow-card)] dark:bg-zinc-900"
          @click="goToSearch"
        >
          <div class="flex items-start justify-between gap-2">
            <span
              class="flex h-9 w-9 items-center justify-center rounded-xl bg-accent-sand/60 text-text-primary"
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
            <span class="text-xs text-text-secondary">Поиск</span>
          </div>
          <p class="mt-4 text-sm font-semibold text-text-primary dark:text-zinc-50">Найти заказ</p>
          <p class="mt-1 text-xs text-text-secondary">Лента рядом с вами</p>
        </button>

        <div
          v-else-if="showAvailableExecutors"
          class="rounded-3xl bg-white p-4 shadow-[var(--shadow-card)] dark:bg-zinc-900"
          aria-label="Свободные исполнители, ожидающие заказ"
        >
          <div class="flex items-start justify-between gap-2">
            <span
              class="flex h-9 w-9 items-center justify-center rounded-xl bg-accent-sand/60 text-text-primary"
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
              </svg>
            </span>
            <span class="text-xs text-text-secondary">Сейчас</span>
          </div>
          <p class="mt-4 text-3xl font-semibold leading-none text-text-primary dark:text-zinc-50">
            {{ availableExecutorsCount }}
          </p>
          <p class="mt-1 text-xs text-text-secondary">Ждут заказ</p>
        </div>
      </div>
    </div>
  </header>

  <header v-else class="px-4 pb-4 pt-4">
    <div class="flex items-center justify-between gap-3">
      <button
        v-if="leftAction === 'chat'"
        type="button"
        :class="iconBtnClass"
        class="relative"
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
        <NotificationBadge class="absolute right-0 top-0" :count="chatUnread" />
      </button>
      <button
        v-else-if="leftAction === 'back'"
        type="button"
        :class="iconBtnClass"
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

      <h1 class="truncate text-center text-base font-semibold text-text-primary dark:text-zinc-50">
        {{ title }}
      </h1>

      <button
        v-if="showDecline && !chatOpen"
        type="button"
        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600 shadow-[var(--shadow-card)] transition hover:bg-red-100 dark:bg-red-500/20 dark:text-red-200"
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
        :class="iconBtnClass"
        class="relative"
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
        <NotificationBadge class="absolute right-0 top-0" :count="chatUnread" />
      </button>
      <div v-else class="h-11 w-11 shrink-0" aria-hidden="true" />
    </div>
  </header>
</template>
