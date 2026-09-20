<script setup lang="ts">
import { RouterLink, RouterView, useRouter } from 'vue-router'
import Button from 'primevue/button'
import ThemeToggle from '@/components/ThemeToggle.vue'
import { useAuth } from '@/composables'
import { Permission } from '@/permissions'

const router = useRouter()
const { admin, logout, can } = useAuth()

async function onLogout(): Promise<void> {
  await logout()
  await router.replace({ name: 'login' })
}
</script>

<template>
  <div class="flex min-h-screen bg-surface-page text-text-primary dark:bg-zinc-950 dark:text-zinc-100">
    <aside class="flex w-60 shrink-0 flex-col bg-surface-sidebar text-white">
      <div class="px-5 py-6">
        <p class="text-xs uppercase tracking-[0.2em] text-white/50">Jober</p>
        <p class="mt-1 text-lg font-semibold">Админка</p>
      </div>
      <nav class="flex-1 space-y-1 px-3">
        <RouterLink
          v-if="can(Permission.OrdersView)"
          :to="{ name: 'orders' }"
          class="block rounded-lg px-3 py-2 text-sm text-white/70 hover:bg-white/10 hover:text-white"
          active-class="bg-white/15 text-white"
        >
          Заказы
        </RouterLink>
        <RouterLink
          v-if="can(Permission.UsersView)"
          :to="{ name: 'users' }"
          class="block rounded-lg px-3 py-2 text-sm text-white/70 hover:bg-white/10 hover:text-white"
          active-class="bg-white/15 text-white"
        >
          Пользователи
        </RouterLink>
        <RouterLink
          v-if="can(Permission.AdminsView)"
          :to="{ name: 'admins' }"
          class="block rounded-lg px-3 py-2 text-sm text-white/70 hover:bg-white/10 hover:text-white"
          active-class="bg-white/15 text-white"
        >
          Администраторы
        </RouterLink>
        <RouterLink
          :to="{ name: 'working-areas' }"
          class="block rounded-lg px-3 py-2 text-sm text-white/70 hover:bg-white/10 hover:text-white"
          active-class="bg-white/15 text-white"
        >
          Рабочие зоны
        </RouterLink>
      </nav>
      <div class="border-t border-white/10 px-4 py-4">
        <p class="truncate text-sm">{{ admin?.name }}</p>
        <p class="truncate text-xs text-white/50">{{ admin?.email }}</p>
        <Button
          class="mt-3 !px-0 text-white/70 hover:!text-white"
          variant="text"
          severity="secondary"
          size="small"
          label="Выйти"
          @click="onLogout"
        />
      </div>
    </aside>
    <div class="flex min-w-0 flex-1 flex-col">
      <header class="flex items-center justify-end gap-3 border-b border-border-subtle bg-surface-card px-6 py-3 dark:border-white/10 dark:bg-zinc-900">
        <ThemeToggle />
      </header>
      <main class="flex-1 p-6">
        <RouterView />
      </main>
    </div>
  </div>
</template>
