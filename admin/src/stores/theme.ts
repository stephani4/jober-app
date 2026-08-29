import { defineStore } from 'pinia'
import { computed, ref, watch } from 'vue'

export type ThemeMode = 'light' | 'dark'

const STORAGE_KEY = 'jober_admin_theme'

function readStoredTheme(): ThemeMode {
  const value = localStorage.getItem(STORAGE_KEY)
  return value === 'dark' ? 'dark' : 'light'
}

function applyTheme(mode: ThemeMode): void {
  document.documentElement.classList.toggle('dark', mode === 'dark')
}

export const useThemeStore = defineStore('theme', () => {
  const mode = ref<ThemeMode>(readStoredTheme())
  const isDark = computed(() => mode.value === 'dark')

  function setTheme(next: ThemeMode): void {
    mode.value = next
  }

  function toggle(): void {
    mode.value = mode.value === 'dark' ? 'light' : 'dark'
  }

  watch(
    mode,
    (value) => {
      localStorage.setItem(STORAGE_KEY, value)
      applyTheme(value)
    },
    { immediate: true },
  )

  return {
    mode,
    isDark,
    setTheme,
    toggle,
  }
})
