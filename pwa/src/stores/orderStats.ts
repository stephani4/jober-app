import { defineStore } from 'pinia'
import { ref } from 'vue'
import { orderService } from '@/services/OrderService'

/**
 * Статистика исполнителя для блока «Обзор» главной страницы.
 */
export const useOrderStatsStore = defineStore('orderStats', () => {
  /** Заказов выполнено сегодня. */
  const completedToday = ref(0)
  const loaded = ref(false)

  async function fetchCompletedToday(): Promise<void> {
    try {
      completedToday.value = await orderService.completedToday()
    } catch {
      // Счётчик некритичен: при сбое оставляем текущее значение.
    } finally {
      loaded.value = true
    }
  }

  /** Инкремент после успешного подтверждения завершения заказа. */
  function bumpCompletedToday(): void {
    completedToday.value += 1
  }

  function reset(): void {
    completedToday.value = 0
    loaded.value = false
  }

  return {
    completedToday,
    loaded,
    fetchCompletedToday,
    bumpCompletedToday,
    reset,
  }
})