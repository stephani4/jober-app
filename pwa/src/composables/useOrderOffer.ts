import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useOrderOfferStore } from '@/stores/orderOffer'

/**
 * 5-секундное окно нового заказа: принять и перейти к выполнению.
 */
export function useOrderOffer() {
  const store = useOrderOfferStore()
  const router = useRouter()
  const { order } = storeToRefs(store)

  async function accept(): Promise<void> {
    if (!order.value) {
      return
    }

    const orderId = order.value.id
    store.dismiss()
    await router.push({ name: 'order-execute', params: { orderId: String(orderId) } })
  }

  return {
    order,
    ttlMs: store.ttlMs,
    dismiss: store.dismiss,
    accept,
  }
}
