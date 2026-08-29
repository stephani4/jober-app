import { storeToRefs } from 'pinia'
import { useNotificationsStore } from '@/stores/notifications'

/**
 * Счётчик непрочитанных для бейджей в навигации и профиле.
 */
export function useNotifications() {
  const store = useNotificationsStore()
  const { unreadCount } = storeToRefs(store)

  return {
    unreadCount,
  }
}
