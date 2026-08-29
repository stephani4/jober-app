<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import NotificationCard from '@/components/notifications/NotificationCard.vue'
import { useNotificationInbox } from '@/composables/useNotificationInbox'

const { items, filter, loading, loadingMore, loaded, hasMore, setFilter, loadMore, markVisible } =
  useNotificationInbox()

const sentinel = ref<HTMLElement | null>(null)
let moreObserver: IntersectionObserver | null = null

onMounted(() => {
  moreObserver = new IntersectionObserver(
    (entries) => {
      if (entries.some((entry) => entry.isIntersecting)) {
        void loadMore()
      }
    },
    { rootMargin: '120px' },
  )
  if (sentinel.value) {
    moreObserver.observe(sentinel.value)
  }
})

watch(sentinel, (el, prev) => {
  if (!moreObserver) {
    return
  }
  if (prev) {
    moreObserver.unobserve(prev)
  }
  if (el) {
    moreObserver.observe(el)
  }
})

onBeforeUnmount(() => {
  moreObserver?.disconnect()
  moreObserver = null
})
</script>

<template>
  <section class="space-y-4">
    <div class="grid grid-cols-2 gap-2">
      <button
        type="button"
        class="rounded-xl px-3 py-2 text-sm"
        :class="
          filter === 'all'
            ? 'bg-accent-nav text-white'
            : 'bg-surface-card text-text-primary dark:bg-zinc-900 dark:text-zinc-100'
        "
        @click="setFilter('all')"
      >
        Все
      </button>
      <button
        type="button"
        class="rounded-xl px-3 py-2 text-sm"
        :class="
          filter === 'unread'
            ? 'bg-accent-nav text-white'
            : 'bg-surface-card text-text-primary dark:bg-zinc-900 dark:text-zinc-100'
        "
        @click="setFilter('unread')"
      >
        Непрочитанные
      </button>
    </div>

    <p v-if="loading && !loaded" class="text-sm text-text-secondary">Загружаем уведомления…</p>
    <p v-else-if="loaded && items.length === 0" class="text-sm text-text-secondary">
      {{ filter === 'unread' ? 'Нет непрочитанных уведомлений.' : 'Пока нет уведомлений.' }}
    </p>

    <div class="space-y-3">
      <NotificationCard
        v-for="item in items"
        :key="item.id"
        :notification="item"
        @visible="markVisible"
      />
    </div>

    <p v-if="loadingMore" class="text-center text-sm text-text-secondary">Ещё уведомления…</p>
    <div v-if="hasMore" ref="sentinel" class="h-4" aria-hidden="true" />
  </section>
</template>
