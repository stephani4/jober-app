import { onBeforeUnmount, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useNotificationsStore } from '@/stores/notifications'
import { useRealtimeStore } from '@/stores/realtime'
import type { NotificationFilter } from '@/schemas/notification'

const MARK_READ_DEBOUNCE_MS = 400

/**
 * Инбокс уведомлений: фильтры, догрузка и пометка видимых как прочитанных.
 */
export function useNotificationInbox() {
  const store = useNotificationsStore()
  const realtime = useRealtimeStore()
  const { items, filter, loading, loadingMore, loaded, hasMore } = storeToRefs(store)
  const { status } = storeToRefs(realtime)

  const pending = new Set<string>()
  let timer: ReturnType<typeof setTimeout> | null = null

  function clearTimer(): void {
    if (timer) {
      clearTimeout(timer)
      timer = null
    }
  }

  async function flushVisible(): Promise<void> {
    clearTimer()
    const ids = [...pending]
    pending.clear()
    if (ids.length > 0) {
      await store.markRead(ids)
    }
  }

  function markVisible(id: string): void {
    const item = store.items.find((row) => row.id === id)
    if (!item || item.read_at) {
      return
    }
    pending.add(id)
    clearTimer()
    timer = setTimeout(() => {
      void flushVisible()
    }, MARK_READ_DEBOUNCE_MS)
  }

  async function setFilter(next: NotificationFilter): Promise<void> {
    await flushVisible()
    await store.setFilter(next)
  }

  watch(
    status,
    (value) => {
      if (value === 'connected') {
        void store.fetchFirst()
      }
    },
    { immediate: true },
  )

  onBeforeUnmount(() => {
    void flushVisible()
  })

  return {
    items,
    filter,
    loading,
    loadingMore,
    loaded,
    hasMore,
    setFilter,
    loadMore: store.loadMore,
    markVisible,
  }
}
