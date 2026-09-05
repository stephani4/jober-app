import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

type BeforeInstallPromptEvent = Event & {
  prompt: () => Promise<void>
  userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>
}

/**
 * Установка PWA: нативный prompt браузера и признаки standalone/iOS.
 */
export const usePwaInstallStore = defineStore('pwaInstall', () => {
  const deferred = ref<BeforeInstallPromptEvent | null>(null)
  const installed = ref(false)

  const isStandalone = computed(() => {
    if (typeof window === 'undefined') {
      return false
    }
    return (
      window.matchMedia('(display-mode: standalone)').matches ||
      ('standalone' in window.navigator && Boolean((window.navigator as { standalone?: boolean }).standalone))
    )
  })

  const isIos = computed(() => {
    if (typeof navigator === 'undefined') {
      return false
    }
    return /iphone|ipad|ipod/i.test(navigator.userAgent)
  })

  const canInstall = computed(() => Boolean(deferred.value) && !isStandalone.value)

  function capturePrompt(event: Event): void {
    event.preventDefault()
    deferred.value = event as BeforeInstallPromptEvent
  }

  function markInstalled(): void {
    installed.value = true
    deferred.value = null
  }

  async function install(): Promise<boolean> {
    const promptEvent = deferred.value
    if (!promptEvent) {
      return false
    }
    await promptEvent.prompt()
    const choice = await promptEvent.userChoice
    deferred.value = null
    if (choice.outcome === 'accepted') {
      installed.value = true
      return true
    }
    return false
  }

  return {
    deferred,
    installed,
    isStandalone,
    isIos,
    canInstall,
    capturePrompt,
    markInstalled,
    install,
  }
})
