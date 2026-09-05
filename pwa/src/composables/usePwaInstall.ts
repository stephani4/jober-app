import { storeToRefs } from 'pinia'
import { usePwaInstallStore } from '@/stores/pwaInstall'

let bound = false

/**
 * Ловит beforeinstallprompt и даёт установить PWA с кнопки.
 */
export function usePwaInstall() {
  const store = usePwaInstallStore()
  const { canInstall, isStandalone, isIos, installed } = storeToRefs(store)

  if (!bound && typeof window !== 'undefined') {
    bound = true
    window.addEventListener('beforeinstallprompt', (event) => {
      store.capturePrompt(event)
    })
    window.addEventListener('appinstalled', () => {
      store.markInstalled()
    })
  }

  return {
    canInstall,
    isStandalone,
    isIos,
    installed,
    install: store.install,
  }
}
