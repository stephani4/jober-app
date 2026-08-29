import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { notificationService } from '@/services/NotificationService'
import type { AppNotification, NotificationCreatedEvent, NotificationFilter } from '@/schemas/notification'

export const useNotificationsStore = defineStore('notifications', () => {
  const items = ref<AppNotification[]>([])
  const unreadCount = ref(0)
  const filter = ref<NotificationFilter>('all')
  const nextCursor = ref<string | null>(null)
  const loading = ref(false)
  const loadingMore = ref(false)
  const loaded = ref(false)

  const hasMore = computed(() => Boolean(nextCursor.value))

  function applyUnreadCount(count: number): void {
    unreadCount.value = count
  }

  function ingestCreated(event: NotificationCreatedEvent): void {
    applyUnreadCount(event.unread_count)
    if (items.value.some((item) => item.id === event.notification.id)) {
      return
    }
    if (filter.value === 'unread' && event.notification.read_at) {
      return
    }
    items.value = [event.notification, ...items.value]
  }

  async function fetchFirst(): Promise<void> {
    loading.value = true
    try {
      const page = await notificationService.list(filter.value)
      items.value = page.items
      nextCursor.value = page.next_cursor
      applyUnreadCount(page.unread_count)
      loaded.value = true
    } catch {
      loaded.value = true
    } finally {
      loading.value = false
    }
  }

  async function loadMore(): Promise<void> {
    if (!nextCursor.value || loadingMore.value || loading.value) {
      return
    }

    loadingMore.value = true
    try {
      const page = await notificationService.list(filter.value, nextCursor.value)
      const known = new Set(items.value.map((item) => item.id))
      items.value = [...items.value, ...page.items.filter((item) => !known.has(item.id))]
      nextCursor.value = page.next_cursor
      applyUnreadCount(page.unread_count)
    } catch {
      // оставляем уже загруженный список
    } finally {
      loadingMore.value = false
    }
  }

  async function setFilter(next: NotificationFilter): Promise<void> {
    if (filter.value === next && loaded.value) {
      return
    }
    filter.value = next
    nextCursor.value = null
    loaded.value = false
    await fetchFirst()
  }

  async function fetchUnreadCount(): Promise<void> {
    try {
      unreadCount.value = await notificationService.unreadCount()
    } catch {
      // счётчик обновится по realtime-событию
    }
  }

  async function markRead(ids: string[]): Promise<void> {
    const unreadIds = ids.filter((id) => {
      const item = items.value.find((row) => row.id === id)
      return item && !item.read_at
    })
    if (unreadIds.length === 0) {
      return
    }

    try {
      const result = await notificationService.markRead(unreadIds)
      const now = new Date().toISOString()
      items.value = items.value.map((item) =>
        unreadIds.includes(item.id) ? { ...item, read_at: item.read_at ?? now } : item,
      )
      applyUnreadCount(result.unread_count)
    } catch {
      // повторная попытка при следующем появлении в viewport
    }
  }

  function reset(): void {
    items.value = []
    unreadCount.value = 0
    filter.value = 'all'
    nextCursor.value = null
    loading.value = false
    loadingMore.value = false
    loaded.value = false
  }

  return {
    items,
    unreadCount,
    filter,
    nextCursor,
    loading,
    loadingMore,
    loaded,
    hasMore,
    ingestCreated,
    fetchFirst,
    loadMore,
    setFilter,
    fetchUnreadCount,
    markRead,
    reset,
  }
})
