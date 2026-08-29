import { defineStore } from 'pinia'
import { ref } from 'vue'
import { orderService } from '@/services/OrderService'
import { useOrderOfferStore } from '@/stores/orderOffer'
import type { OrderExecuting } from '@/schemas/order'

export const useOrderExecuteStore = defineStore('orderExecute', () => {
  const executing = ref<OrderExecuting | null>(null)
  const loading = ref(false)
  const submitting = ref(false)
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

  function reset(): void {
    executing.value = null
    loading.value = false
    submitting.value = false
    error.value = ''
  }

  return {
    executing,
    loading,
    submitting,
    error,
    start,
    completeCurrentPoint,
    reset,
  }
})
