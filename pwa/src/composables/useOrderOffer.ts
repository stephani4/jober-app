import { watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useOrderSound } from '@/composables/useOrderSound'
import { useOrderOfferStore } from '@/stores/orderOffer'

/**
 * 5-секундное окно нового заказа: принять и перейти к выполнению.
 */
export function useOrderOffer() {
  const store = useOrderOfferStore()
  const router = useRouter()
  const sound = useOrderSound()
  const { order } = storeToRefs(store)

  // Показ окна = момент появления нового заказа, поэтому сопровождаем его звуком.
  watch(order, (next) => {
    if (next) {
      void sound.playNewOrderOffer()
    }
  })

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
