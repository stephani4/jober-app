import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useOrderExecuteStore } from '@/stores/orderExecute'

/**
 * Контракт UI для отказа исполнителя от выполнения заказа.
 */
export function useOrderDecline() {
  const store = useOrderExecuteStore()
  const router = useRouter()
  const { declineConfirm, declining } = storeToRefs(store)

  /** Открывает окно подтверждения отказа. */
  function request(): void {
    store.requestDecline()
  }

  /** Закрывает окно подтверждения отказа. */
  function cancel(): void {
    store.cancelDecline()
  }

  /** Подтверждает отказ: RPC, сброс экрана и переход к списку заказов. */
  async function confirm(): Promise<void> {
    const ok = await store.decline()
    if (ok) {
      await router.replace({ name: 'orders' })
    }
  }

  return {
    open: declineConfirm,
    loading: declining,
    request,
    cancel,
    confirm,
  }
}
