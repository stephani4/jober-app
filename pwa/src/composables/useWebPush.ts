import { watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useAuth } from '@/composables/useAuth'
import { pushService } from '@/services/PushService'
import { useWebPushStore } from '@/stores/webPush'

let started = false

/**
 * Подписывает устройство на Web Push после входа и по действию пользователя.
 */
export function useWebPush() {
  const auth = useAuth()
  const store = useWebPushStore()
  const { permission, subscribed, busy, error, supported, showBanner } = storeToRefs(store)

  if (!started) {
    started = true
    watch(
      () => auth.isAuthenticated.value,
      async (authenticated) => {
        store.refreshPermission()
        if (!authenticated) {
          subscribed.value = false
          return
        }
        await syncIfGranted()
      },
      { immediate: true },
    )
  }

  /**
   * Если разрешение уже выдано — сохраняет подписку на сервер без повторного prompt.
   */
  async function syncIfGranted(): Promise<void> {
    store.refreshPermission()
    if (!auth.isAuthenticated.value || permission.value !== 'granted') {
      return
    }
    try {
      await persistSubscription()
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Не удалось включить уведомления.'
    }
  }

  /**
   * Запрашивает разрешение и подписывает устройство. Нужен жест пользователя.
   */
  async function enable(): Promise<boolean> {
    error.value = ''
    if (!supported.value) {
      error.value = 'Этот браузер не поддерживает push-уведомления.'
      return false
    }

    busy.value = true
    try {
      const result = await Notification.requestPermission()
      store.refreshPermission()
      if (result !== 'granted') {
        error.value = result === 'denied'
          ? 'Разрешите уведомления в настройках браузера.'
          : 'Нужно разрешить уведомления.'
        return false
      }
      await persistSubscription()
      store.dismissBanner()
      return true
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Не удалось включить уведомления.'
      return false
    } finally {
      busy.value = false
    }
  }

  async function disable(): Promise<void> {
    error.value = ''
    busy.value = true
    try {
      const registration = await navigator.serviceWorker?.ready
      const subscription = await registration?.pushManager.getSubscription()
      if (subscription) {
        const endpoint = subscription.endpoint
        await subscription.unsubscribe()
        if (auth.isAuthenticated.value) {
          await pushService.unsubscribe(endpoint)
        }
      }
      subscribed.value = false
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Не удалось отключить уведомления.'
    } finally {
      busy.value = false
    }
  }

  async function persistSubscription(): Promise<void> {
    const vapid = await pushService.vapid()
    if (!vapid.enabled || !vapid.public_key) {
      throw new Error('Сервер не настроен для push-уведомлений.')
    }

    const registration = await navigator.serviceWorker.ready
    let subscription = await registration.pushManager.getSubscription()
    if (!subscription) {
      subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapid.public_key),
      })
    }

    const json = subscription.toJSON()
    if (!json.endpoint || !json.keys?.p256dh || !json.keys?.auth) {
      throw new Error('Браузер не вернул ключи подписки.')
    }

    await pushService.subscribe(json)
    subscribed.value = true
  }

  return {
    permission,
    subscribed,
    busy,
    error,
    supported,
    showBanner,
    enable,
    disable,
    dismissBanner: store.dismissBanner,
    syncIfGranted,
  }
}

function urlBase64ToUint8Array(base64String: string): BufferSource {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
  const raw = atob(base64)
  const output = new Uint8Array(raw.length)
  for (let index = 0; index < raw.length; index += 1) {
    output[index] = raw.charCodeAt(index)
  }
  return output
}
