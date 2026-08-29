import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Order } from '@/schemas/order'

const OFFER_TTL_MS = 5000

/**
 * Короткое realtime-предложение нового заказа исполнителю.
 */
export const useOrderOfferStore = defineStore('orderOffer', () => {
  const order = ref<Order | null>(null)
  let timer: ReturnType<typeof setTimeout> | null = null

  function clearTimer(): void {
    if (timer) {
      clearTimeout(timer)
      timer = null
    }
  }

  function dismiss(): void {
    clearTimer()
    order.value = null
  }

  function present(next: Order): void {
    dismiss()
    order.value = next
    timer = setTimeout(() => {
      order.value = null
      timer = null
    }, OFFER_TTL_MS)
  }

  function dismissIf(orderId: number): void {
    if (order.value?.id === orderId) {
      dismiss()
    }
  }

  function reset(): void {
    dismiss()
  }

  return {
    order,
    ttlMs: OFFER_TTL_MS,
    present,
    dismiss,
    dismissIf,
    reset,
  }
})
