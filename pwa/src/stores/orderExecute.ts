import { defineStore } from 'pinia'
import { ref } from 'vue'
import { orderService } from '@/services/OrderService'
import { useOrderOfferStore } from '@/stores/orderOffer'
import type { OrderExecuting } from '@/schemas/order'

export const useOrderExecuteStore = defineStore('orderExecute', () => {
  const executing = ref<OrderExecuting | null>(null)
  const loading = ref(false)
  const submitting = ref(false)
  const declining = ref(false)
  const declineConfirm = ref(false)
  const error = ref('')

  async function start(orderId: number): Promise<void> {
    loading.value = true
    error.value = ''
    try {
      executing.value = await orderService.start(orderId)
      useOrderOfferStore().dismiss()
    } catch (err) {
      executing.value = null
      error.value = err instanceof Error ? err.message : 'Не удалось начать выполнение.'
    } finally {
      loading.value = false
    }
  }

  async function completeCurrentPoint(): Promise<boolean> {
    const current = executing.value?.points.find((point) => point.status === 'process')
    if (!executing.value || !current) {
      return false
    }

    submitting.value = true
    error.value = ''
    try {
      executing.value = await orderService.completePoint(executing.value.order_id, current.order_point_id)
      return true
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Не удалось отметить точку.'
      return false
    } finally {
      submitting.value = false
    }
  }

  /**
   * Подтверждает завершение заказа кодом от автора на этапе confirmation.
   */
  async function confirmCompletion(code: string): Promise<boolean> {
    const current = executing.value
    if (!current || current.status !== 'confirmation') {
      return false
    }

    submitting.value = true
    error.value = ''
    try {
      executing.value = await orderService.confirmCompletion(current.order_id, code)
      return true
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Не удалось подтвердить завершение.'
      return false
    } finally {
      submitting.value = false
    }
  }

  /** Открывает окно подтверждения отказа от выполнения. */
  function requestDecline(): void {
    if (!executing.value) {
      return
    }
    declineConfirm.value = true
  }

  /** Закрывает окно подтверждения отказа. */
  function cancelDecline(): void {
    declineConfirm.value = false
  }

  /**
   * Отменяет выполнение заказа по RPC и сбрасывает состояние экрана.
   */
  async function decline(): Promise<boolean> {
    const current = executing.value
    if (!current) {
      return false
    }

    declining.value = true
    error.value = ''
    try {
      await orderService.decline(current.order_id)
      declineConfirm.value = false
      reset()
      return true
    } catch (err) {
      declineConfirm.value = false
      error.value = err instanceof Error ? err.message : 'Не удалось отказаться от выполнения.'
      return false
    } finally {
      declining.value = false
    }
  }

  function reset(): void {
    executing.value = null
    loading.value = false
    submitting.value = false
    declining.value = false
    declineConfirm.value = false
    error.value = ''
  }

  return {
    executing,
    loading,
    submitting,
    declining,
    declineConfirm,
    error,
    start,
    completeCurrentPoint,
    confirmCompletion,
    requestDecline,
    cancelDecline,
    decline,
    reset,
  }
})
