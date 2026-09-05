import { storeToRefs } from 'pinia'
import { orderService } from '@/services/OrderService'
import { createOrderPayloadSchema } from '@/schemas/order'
import { useOrderCreateStore } from '@/stores/orderCreate'
import { useOrdersStore } from '@/stores/orders'

/**
 * Driver мастера создания заказа: шаги, валидация перехода и отправка через Centrifugo.
 */
export function useOrderCreateDriver() {
  const draft = useOrderCreateStore()
  const orders = useOrdersStore()
  const {
    step,
    orderType,
    points,
    cost,
    description,
    submitting,
    error,
    isSinglePoint,
    canGoStep2,
    canGoStep3,
  } = storeToRefs(draft)

  function next(): boolean {
    if (step.value === 1 && !canGoStep2.value) {
      error.value = isSinglePoint.value
        ? 'Опишите, что нужно купить, и укажите точку доставки на карте.'
        : 'Заполните описание и выберите точку на карте для каждой позиции.'
      return false
    }

    if (step.value === 2 && !canGoStep3.value) {
      error.value = 'Укажите стоимость заказа.'
      return false
    }

    error.value = ''
    if (step.value < 3) {
      step.value += 1
    }
    return true
  }

  function back(): void {
    error.value = ''
    if (step.value > 1) {
      step.value -= 1
    }
  }

  async function submit(): Promise<boolean> {
    error.value = ''
    const parsed = createOrderPayloadSchema.safeParse(draft.payload())
    if (!parsed.success) {
      error.value = parsed.error.issues[0]?.message || 'Проверьте данные заказа.'
      return false
    }

    submitting.value = true
    try {
      const order = await orderService.create(parsed.data)
      orders.upsert(order)
      return true
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Не удалось создать заказ.'
      return false
    } finally {
      submitting.value = false
    }
  }

  return {
    step,
    orderType,
    points,
    cost,
    description,
    submitting,
    error,
    isSinglePoint,
    canGoStep2,
    canGoStep3,
    next,
    back,
    submit,
    addPoint: draft.addPoint,
    removePoint: draft.removePoint,
    setPoints: draft.setPoints,
    setPointLocation: draft.setPointLocation,
    reset: draft.reset,
  }
}
