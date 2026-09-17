<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth, usePwaInstall, useWebPush } from '@/composables'

const router = useRouter()
const { user, logout } = useAuth()
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

/** Имя в две строки по макету: «David» / «Brandon 👋». */
const nameParts = computed(() => {
  const name = user.value?.name?.trim() ?? ''
  const words = name.split(/\s+/).filter(Boolean)
  if (words.length >= 2) {
    return { first: words.slice(0, -1).join(' '), last: words[words.length - 1] }
  }
  return { first: '', last: name || 'Пользователь' }
})

const daysJoined = computed(() => {
  const created = user.value?.created_at
  if (!created) {
    return null
  }
  const elapsed = Date.now() - new Date(created).getTime()
  if (Number.isNaN(elapsed) || elapsed < 0) {
    return null
  }
  const days = Math.max(1, Math.floor(elapsed / 86_400_000))
  const word =
    days % 10 === 1 && days % 100 !== 11
      ? 'день'
      : days % 10 >= 2 && days % 10 <= 4 && (days % 100 < 10 || days % 100 >= 20)
        ? 'дня'
        : 'дней'
  return `${days} ${word}`
})

const navigationRows = [
  { label: 'Мои заказы', icon: 'orders', to: { name: 'orders' } },
  { label: 'Уведомления', icon: 'bell', to: { name: 'notifications' } },
  { label: 'История заказов', icon: 'history', to: { name: 'order-history' } },
  { label: 'Редактировать профиль', icon: 'user', to: { name: 'profile-edit' } },
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
  <section class="space-y-4 pb-10">
    <!-- Блок пользователя: аватар в акцентном кольце, имя в две строки, "Вы в системе" -->
    <div class="flex items-center gap-4">
      <img
        v-if="user?.avatar_url"
        :src="user.avatar_url"
        :alt="user?.name ?? 'Аватар'"
        class="h-20 w-20 shrink-0 rounded-full object-cover ring-4 ring-accent-nav/50"
      />
      <div
        v-else
        class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-surface-muted text-2xl font-semibold text-text-primary ring-4 ring-accent-nav/50 dark:bg-zinc-800 dark:text-zinc-100"
        aria-hidden="true"
      >
        {{ user?.name?.charAt(0)?.toUpperCase() ?? '?' }}
      </div>
      <div class="min-w-0 flex-1">
        <h2 class="text-xl text-text-primary dark:text-zinc-100">
          <template v-if="nameParts.first">{{ nameParts.first }} </template>
          <span class="font-bold">{{ nameParts.last }} 👋</span>
        </h2>
        <p class="mt-1 text-xs text-text-secondary">{{ user?.email }}</p>
        <p v-if="daysJoined" class="mt-2 text-xs leading-tight text-text-secondary">
          Вы в системе<br>
          <span class="text-sm font-semibold text-text-primary">{{ daysJoined }}</span>
        </p>
        <p v-else class="mt-1 text-sm font-medium text-text-primary capitalize dark:text-zinc-100">{{ user?.role === 'executor' ? 'Исполнитель' : 'Заказчик' }}</p>
      </div>
    </div>

    <!-- Card rows: тёмный квадрат иконки + label + светлый круг с шевроном -->
    <div class="space-y-3">
      <RouterLink
        v-for="item in navigationRows"
        :key="item.label"
        :to="item.to"
        class="flex items-center gap-3 rounded-2xl border border-border-subtle bg-surface-card px-4 py-3.5 shadow-[var(--shadow-card)] transition hover:bg-surface-muted dark:border-white/10 dark:bg-zinc-900 dark:hover:bg-zinc-800"
      >
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-text-primary text-white" aria-hidden="true">
          <svg v-if="item.icon === 'orders'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" /><rect x="9" y="3" width="6" height="4" rx="1" /><path d="M9 12h6M9 16h6" /></svg>
          <svg v-else-if="item.icon === 'bell'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M10 6h4M10 6v7M14 6v7M10 13h-3v3M14 13h3v3M7 16h10M12 16v4" /></svg>
          <svg v-else-if="item.icon === 'history'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="7" r="1.5" /><path d="M11 7h5" /><circle cx="8" cy="12" r="1.5" /><path d="M11 12h7" /><circle cx="8" cy="17" r="1.5" /><path d="M11 17h5" /></svg>
          <svg v-else viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4" /><path d="M5 20a7 7 0 0 1 14 0" /></svg>
        </span>
        <span class="min-w-0 flex-1 text-sm text-text-primary dark:text-zinc-100">{{ item.label }}</span>
        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-surface-muted text-text-secondary dark:bg-zinc-800 dark:text-zinc-400" aria-hidden="true">
          <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6" /></svg>
        </span>
      </RouterLink>

      <button v-if="showInstall" type="button" class="flex w-full items-center gap-3 rounded-2xl border border-border-subtle bg-surface-card px-4 py-3.5 text-left shadow-[var(--shadow-card)] transition hover:bg-surface-muted dark:border-white/10 dark:bg-zinc-900 dark:hover:bg-zinc-800" @click="onInstall">
        <span class="min-w-0 flex-1">
          <span class="block text-sm text-text-primary dark:text-zinc-100">Установить приложение</span>
          <span class="mt-0.5 block text-sm text-text-secondary">{{ canInstall ? 'Ярлык на домашнем экране' : 'Поделиться → На экран «Домой»' }}</span>
        </span>
        <svg viewBox="0 0 24 24" class="h-5 w-5 shrink-0 text-text-secondary" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
      </button>

      <button v-if="supported" type="button" class="flex w-full items-center gap-3 rounded-2xl border border-border-subtle bg-surface-card px-4 py-3.5 text-left shadow-[var(--shadow-card)] transition hover:bg-surface-muted disabled:opacity-60 dark:border-white/10 dark:bg-zinc-900 dark:hover:bg-zinc-800" :disabled="busy || permission === 'denied'" @click="onTogglePush">
        <span class="min-w-0 flex-1">
          <span class="block text-sm text-text-primary dark:text-zinc-100">Уведомления на телефон</span>
          <span class="mt-0.5 block text-sm text-text-secondary">{{ pushLabel }}</span>
          <span v-if="error" class="mt-1 block text-sm text-accent-danger">{{ error }}</span>
        </span>
        <span class="relative h-7 w-12 shrink-0 rounded-full transition" :class="subscribed ? 'bg-accent-nav' : 'bg-surface-muted dark:bg-zinc-700'" aria-hidden="true">
          <span class="absolute top-0.5 h-6 w-6 rounded-full bg-white shadow transition" :class="subscribed ? 'left-5' : 'left-0.5'" />
        </span>
      </button>
    </div>

    <!-- Выход: чёрная pill по макету profile-baseline -->
    <button type="button" class="flex w-full items-center justify-center gap-2 rounded-full bg-black py-3.5 text-sm font-medium text-white transition hover:bg-zinc-900 dark:bg-zinc-900 dark:hover:bg-zinc-800" @click="onLogout">
      <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" /><path d="m16 17 5-5-5-5" /><path d="M21 12H9" /></svg>
      Выйти
    </button>
  </section>
</template>
