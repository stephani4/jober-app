<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import BottomNavIcon from '@/components/BottomNavIcon.vue'
import NotificationBadge from '@/components/NotificationBadge.vue'
import { bottomNavItems } from '@/config/bottomNav'
import { useAuth, useNotifications, useOrderTypes, useSearchOrders } from '@/composables'

const route = useRoute()
const { hasRole } = useAuth()
const { unreadCount } = useNotifications()
const { availableCount } = useSearchOrders()
const { openPicker } = useOrderTypes()
const activeKey = computed(() => route.meta.nav)

const items = computed(() => bottomNavItems.filter((item) => hasRole(item.roles)))

function isActive(key: string): boolean {
  return activeKey.value === key
}

function isCreate(key: string): boolean {
  return key === 'create'
}

function itemClass(key: string): string {
  return isActive(key)
    ? 'bg-white text-zinc-950'
    : 'bg-white/10 text-white hover:bg-white/15'
}
</script>

<template>
  <nav
    class="pointer-events-none fixed inset-x-0 bottom-0 z-50 flex justify-center pb-[max(0.75rem,env(safe-area-inset-bottom))]"
    aria-label="Основная навигация"
  >
    <div
      class="pointer-events-auto flex items-center gap-1.5 rounded-full bg-surface-nav px-2 py-2 shadow-[var(--shadow-nav-active)]"
    >
      <template v-for="item in items" :key="item.key">
        <button
          v-if="isCreate(item.key)"
          type="button"
          class="relative flex h-11 w-11 items-center justify-center rounded-full transition"
          :class="itemClass(item.key)"
          :aria-label="item.label"
          :aria-current="isActive(item.key) ? 'page' : undefined"
          @click="openPicker()"
        >
          <BottomNavIcon :name="item.key" class="h-5 w-5 shrink-0" />
        </button>

        <RouterLink
          v-else
          :to="item.to"
          class="relative flex h-11 w-11 items-center justify-center rounded-full transition"
          :class="itemClass(item.key)"
          :aria-label="item.label"
          :aria-current="isActive(item.key) ? 'page' : undefined"
        >
          <BottomNavIcon :name="item.key" class="h-5 w-5 shrink-0" />
          <NotificationBadge
            v-if="item.key === 'search'"
            class="absolute -right-0.5 -top-0.5 z-10"
            :count="availableCount"
            aria-label="Доступные заказы"
          />
          <NotificationBadge
            v-if="item.key === 'profile'"
            class="absolute -right-0.5 -top-0.5 z-10"
            :count="unreadCount"
          />
        </RouterLink>
      </template>
    </div>
  </nav>
</template>
