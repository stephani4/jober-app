import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useOrderCreateStore } from '@/stores/orderCreate'
import { useOrderTypesStore } from '@/stores/orderTypes'
import type { OrderType } from '@/schemas/order'

/**
 * Справочник видов заказа и popup перед формой публикации.
 */
export function useOrderTypes() {
  const store = useOrderTypesStore()
  const draft = useOrderCreateStore()
  const router = useRouter()
  const { types, loading, error, pickerOpen } = storeToRefs(store)

  /**
   * Сбрасывает черновик, фиксирует вид и открывает форму заказа.
   */
  async function selectType(type: OrderType): Promise<void> {
    draft.begin(type)
    store.closePicker()
    if (router.currentRoute.value.name !== 'order-create') {
      await router.push({ name: 'order-create' })
    }
  }

  return {
    types,
    loading,
    error,
    pickerOpen,
    openPicker: store.openPicker,
    closePicker: store.closePicker,
    selectType,
  }
}
