import { storeToRefs } from 'pinia'
import { useThemeStore, type ThemeMode } from '@/stores/theme'

export function useTheme() {
  const store = useThemeStore()
  const { mode, isDark } = storeToRefs(store)

  return {
    mode,
    isDark,
    setTheme: (next: ThemeMode) => store.setTheme(next),
    toggle: () => store.toggle(),
  }
}
