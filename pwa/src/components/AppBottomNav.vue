<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import BottomNavIcon from '@/components/BottomNavIcon.vue'
import NotificationBadge from '@/components/NotificationBadge.vue'
import { bottomNavItems } from '@/config/bottomNav'
import { useAuth, useNotifications, useOrderTypes } from '@/composables'

const route = useRoute()
const { hasRole } = useAuth()
const { unreadCount } = useNotifications()
const { openPicker } = useOrderTypes()
const activeKey = computed(() => route.meta.nav)

// Состав табов зависит от роли: у заказчика нет поиска и откликов.
const items = computed(() => bottomNavItems.filter((item) => hasRole(item.roles)))
/** Колонок столько, сколько доступных табов, — иначе они «жмутся» влево. */
const gridStyle = computed(() => ({
  gridTemplateColumns: `repeat(${items.value.length}, minmax(0, 1fr))`,
}))

function isActive(key: string): boolean {
  return activeKey.value === key
}

function isCreate(key: string): boolean {
  return key === 'create'
}
</script>

<template>
  <nav
    class="fixed bottom-0 left-1/2 z-50 w-full max-w-lg -translate-x-1/2 overflow-hidden rounded-t-2xl border border-border-subtle bg-surface-page shadow-[var(--shadow-card)] pb-[env(safe-area-inset-bottom)] dark:border-white/10 dark:bg-zinc-900"
    aria-label="Основная навигация"
  >
    <div class="grid h-16 items-center justify-items-stretch" :style="gridStyle">
      <template v-for="item in items" :key="item.key">
        <button
          v-if="isCreate(item.key)"
          type="button"
          class="relative flex flex-col items-center gap-1 py-1"
          :aria-label="item.label"
          @click="openPicker()"
        >
          <span
            class="flex h-11 w-11 items-center justify-center rounded-full bg-accent-nav text-white shadow-[var(--shadow-nav-active)]"
          >
            <BottomNavIcon :name="item.key" class="h-5 w-5 shrink-0" />
          </span>
          <span class="text-[10px] font-semibold leading-none text-text-primary">Заказ+</span>
        </button>

        <RouterLink
          v-else
          :to="item.to"
          class="relative flex flex-col items-center gap-1 py-1"
          :aria-label="item.label"
          :aria-current="isActive(item.key) ? 'page' : undefined"
        >
          <span
            class="flex h-11 items-center justify-center rounded-full"
            :class="
              isActive(item.key)
                ? 'w-11 bg-accent-nav text-white shadow-[var(--shadow-nav-active)]'
                : 'w-8 text-text-secondary dark:text-zinc-400'
            "
          >
            <BottomNavIcon :name="item.key" class="h-6 w-6 shrink-0" />
          </span>
          <span
            class="text-[10px] leading-none"
            :class="isActive(item.key) ? 'font-semibold text-text-primary' : 'text-text-secondary dark:text-zinc-400'"
          >
            {{ item.label }}
          </span>
          <NotificationBadge
            v-if="item.key === 'profile'"
            class="absolute right-1 top-0.5 z-10"
            :count="unreadCount"
          />
        </RouterLink>
      </template>
    </div>
  </nav>
</template>
