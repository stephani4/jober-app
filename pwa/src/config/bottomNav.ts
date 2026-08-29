import type { RouteLocationRaw } from 'vue-router'

export type BottomNavIcon = 'orders' | 'search' | 'create' | 'responses' | 'profile'

export interface BottomNavItem {
  key: BottomNavIcon
  label: string
  to: RouteLocationRaw
}

/** Основные табы нижней навигации. */
export const bottomNavItems: BottomNavItem[] = [
  { key: 'orders', label: 'Заказы', to: { name: 'orders' } },
  { key: 'search', label: 'Поиск', to: { name: 'search' } },
  { key: 'create', label: 'Заказ+', to: { name: 'order-create' } },
  { key: 'responses', label: 'Отклики', to: { name: 'responses' } },
  { key: 'profile', label: 'Профиль', to: { name: 'profile' } },
]
