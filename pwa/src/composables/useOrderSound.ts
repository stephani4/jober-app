import { storeToRefs } from 'pinia'
import { orderSoundService } from '@/services/OrderSoundService'
import { useSoundStore } from '@/stores/sound'

let unlockBound = false

/**
 * Звуковое сопровождение событий заказов: настройка, разблокировка и воспроизведение.
 */
export function useOrderSound() {
  const store = useSoundStore()
  const { enabled } = storeToRefs(store)

  if (!unlockBound) {
    unlockBound = true
    // Браузер разрешает звук только после жеста — ловим его заранее.
    orderSoundService.unlock()
  }

  /**
   * Проигрывает сигнал, если звуковое сопровождение включено в настройках.
   */
  async function play(signal: () => Promise<void>): Promise<void> {
    if (!enabled.value) {
      return
    }
    await signal()
  }

  /**
   * Сигнал исполнителю: появился новый заказ.
   */
  async function playNewOrderOffer(): Promise<void> {
    await play(() => orderSoundService.playNewOrderOffer())
  }

  /**
   * Сигнал заказчику: исполнитель откликнулся на заказ.
   */
  async function playOrderTaken(): Promise<void> {
    await play(() => orderSoundService.playOrderTaken())
  }

  /**
   * Переключает звук; при включении сразу подтверждает сигналом, что он работает.
   */
  function toggle(): void {
    store.toggle()
    if (enabled.value) {
      void playNewOrderOffer()
    }
  }

  return {
    enabled,
    setEnabled: (next: boolean) => store.setEnabled(next),
    toggle,
    playNewOrderOffer,
    playOrderTaken,
  }
}
