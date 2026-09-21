import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

const STORAGE_KEY = 'jober_order_sound'

function readStoredEnabled(): boolean {
  return localStorage.getItem(STORAGE_KEY) !== '0'
}

/**
 * Настройка звукового сопровождения событий заказов.
 */
export const useSoundStore = defineStore('sound', () => {
  const enabled = ref(readStoredEnabled())

  function setEnabled(next: boolean): void {
    enabled.value = next
  }

  function toggle(): void {
    enabled.value = !enabled.value
  }

  watch(enabled, (value) => {
    localStorage.setItem(STORAGE_KEY, value ? '1' : '0')
  })

  return {
    enabled,
    setEnabled,
    toggle,
  }
})
