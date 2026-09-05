import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

export type PushPermission = 'unsupported' | 'default' | 'granted' | 'denied'

/**
 * Состояние Web Push на этом устройстве.
 */
export const useWebPushStore = defineStore('webPush', () => {
  const permission = ref<PushPermission>(detectPermission())
  const subscribed = ref(false)
  const busy = ref(false)
  const error = ref('')
  const bannerDismissed = ref(readBannerDismissed())

  const supported = computed(() => permission.value !== 'unsupported')
  const showBanner = computed(
    () =>
      supported.value &&
      permission.value === 'default' &&
      !bannerDismissed.value &&
      !subscribed.value,
  )

  function detectPermission(): PushPermission {
    if (typeof window === 'undefined' || !('Notification' in window) || !('serviceWorker' in navigator) || !('PushManager' in window)) {
      return 'unsupported'
    }
    return Notification.permission
  }

  function refreshPermission(): void {
    permission.value = detectPermission()
  }

  function dismissBanner(): void {
    bannerDismissed.value = true
    try {
      sessionStorage.setItem('jober_push_banner', '1')
    } catch {
      // private mode
    }
  }

  return {
    permission,
    subscribed,
    busy,
    error,
    bannerDismissed,
    supported,
    showBanner,
    refreshPermission,
    dismissBanner,
  }
})

function readBannerDismissed(): boolean {
  try {
    return sessionStorage.getItem('jober_push_banner') === '1'
  } catch {
    return false
  }
}
