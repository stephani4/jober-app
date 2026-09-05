import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { OrderType } from '@/schemas/order'
import { orderTypeService } from '@/services/OrderTypeService'

/**
 * Справочник видов заказа и состояние popup выбора перед формой.
 */
export const useOrderTypesStore = defineStore('orderTypes', () => {
  const types = ref<OrderType[]>([])
  const loading = ref(false)
  const error = ref('')
  const pickerOpen = ref(false)

  async function fetchTypes(): Promise<void> {
    if (types.value.length > 0 || loading.value) {
      return
    }

    loading.value = true
    error.value = ''
    try {
      types.value = await orderTypeService.list()
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Не удалось загрузить виды заказов.'
    } finally {
      loading.value = false
    }
  }

  function openPicker(): void {
    pickerOpen.value = true
    void fetchTypes()
  }

  function closePicker(): void {
    pickerOpen.value = false
  }

  return {
    types,
    loading,
    error,
    pickerOpen,
    fetchTypes,
    openPicker,
    closePicker,
  }
})
