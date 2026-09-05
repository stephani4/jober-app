<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import BottomNavIcon from '@/components/BottomNavIcon.vue'
import NotificationBadge from '@/components/NotificationBadge.vue'
import { bottomNavItems } from '@/config/bottomNav'
import { useNotifications, useOrderTypes } from '@/composables'

const route = useRoute()
const { unreadCount } = useNotifications()
const { openPicker } = useOrderTypes()
const activeKey = computed(() => route.meta.nav as string | undefined)

function isActive(key: string): boolean {
  return activeKey.value === key
}

function isCreate(key: string): boolean {
  return key === 'create'
}

const itemClass =
  'relative flex shrink-0 flex-col items-center no-underline'
</script>

<template>
  <nav
    class="fixed inset-x-0 bottom-0 z-50 border-t border-border-subtle bg-surface-page pb-[env(safe-area-inset-bottom)] dark:border-white/10 dark:bg-zinc-900"
    aria-label="Основная навигация"
  >
    <div class="mx-auto flex h-[4.5rem] max-w-lg items-end justify-between px-4">
      <template v-for="item in bottomNavItems" :key="item.key">
        <button
          v-if="isCreate(item.key)"
          type="button"
          :class="itemClass"
          :aria-label="item.label"
          @click="openPicker()"
        >
          <span
            class="inline-flex items-center justify-center overflow-hidden rounded-full bg-accent-nav px-4 py-3 text-white shadow-[var(--shadow-nav-active)] -translate-y-3 gap-1 transition duration-300 ease-[cubic-bezier(0.4,0,0.2,1)] motion-reduce:transition-none"
          >
            <BottomNavIcon :name="item.key" class="h-5 w-5 shrink-0" />
            <span class="text-xs leading-none">Заказ+</span>
          </span>
        </button>
        <RouterLink
          v-else
          :to="item.to"
          :class="itemClass"
          :aria-label="item.label"
          :aria-current="isActive(item.key) ? 'page' : undefined"
        >
          <span
            class="inline-flex items-center justify-center overflow-hidden rounded-full transition duration-300 ease-[cubic-bezier(0.4,0,0.2,1)] motion-reduce:transition-none"
            :class="
              isActive(item.key)
                ? '-translate-y-2 bg-accent-nav p-3 text-white shadow-[var(--shadow-nav-active)]'
                : 'mb-1.5 bg-surface-muted p-3 text-text-secondary dark:bg-zinc-800 dark:text-zinc-400'
            "
          >
            <BottomNavIcon :name="item.key" class="h-5 w-5 shrink-0" />
          </span>
          <NotificationBadge
            v-if="item.key === 'profile'"
            class="absolute right-1 top-0 z-10"
            :count="unreadCount"
          />
        </RouterLink>
      </template>
    </div>
  </nav>
</template>
