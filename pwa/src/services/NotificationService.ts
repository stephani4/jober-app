import { realtimeService } from '@/services/RealtimeService'
import type { NotificationFilter, NotificationList } from '@/schemas/notification'

/**
 * Уведомления: список, прочтение и счётчик непрочитанных через Centrifugo RPC.
 */
export class NotificationService {
  list(filter: NotificationFilter, cursor?: string | null): Promise<NotificationList> {
    return realtimeService.listNotifications({ filter, cursor: cursor ?? null })
  }

  markRead(ids: string[]): Promise<{ unread_count: number }> {
    return realtimeService.markNotificationsRead(ids)
  }

  unreadCount(): Promise<number> {
    return realtimeService.unreadNotificationCount()
  }
}

export const notificationService = new NotificationService()
