import { z } from 'zod'
import { http } from '@/services/HttpClient'

const vapidSchema = z.object({
  enabled: z.boolean(),
  public_key: z.string().nullable(),
})

/**
 * Web Push: VAPID-ключ и сохранение подписки устройства.
 */
export class PushService {
  async vapid(): Promise<{ enabled: boolean; public_key: string | null }> {
    const { data } = await http.client.get('/push/vapid')
    return vapidSchema.parse(data)
  }

  async subscribe(subscription: PushSubscriptionJSON): Promise<void> {
    await http.client.post('/push/subscriptions', {
      endpoint: subscription.endpoint,
      keys: subscription.keys,
      content_encoding: 'aes128gcm',
    })
  }

  async unsubscribe(endpoint: string): Promise<void> {
    await http.client.delete('/push/subscriptions', {
      data: { endpoint },
    })
  }

  /**
   * Снимает браузерную подписку и удаляет её на сервере (перед выходом).
   */
  async detach(): Promise<void> {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
      return
    }
    const registration = await Promise.race([
      navigator.serviceWorker.ready,
      new Promise<null>((resolve) => {
        window.setTimeout(() => resolve(null), 2000)
      }),
    ])
    if (!registration) {
      return
    }
    const subscription = await registration.pushManager.getSubscription()
    if (!subscription) {
      return
    }
    const endpoint = subscription.endpoint
    try {
      await this.unsubscribe(endpoint)
    } catch {
      // токен уже мог быть сброшен
    }
    await subscription.unsubscribe()
  }
}

export const pushService = new PushService()
