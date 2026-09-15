import type { RouteLocationRaw } from 'vue-router'
import type { UserRole } from '@/schemas/user'

export type BottomNavIcon = 'orders' | 'search' | 'create' | 'responses' | 'profile'

export interface BottomNavItem {
  key: BottomNavIcon
  label: string
  to: RouteLocationRaw
  /** Роли, которым виден таб; без значения таб виден всем. */
  roles?: UserRole[]
}

/** Основные табы нижней навигации. */
export const bottomNavItems: BottomNavItem[] = [
  { key: 'orders', label: 'Мои заказы', to: { name: 'orders' } },
  // Поиск и отклики — только для исполнителя: заказчик не ищет заказы и не откликается.
  { key: 'search', label: 'Поиск', to: { name: 'search' }, roles: ['executor'] },
  { key: 'create', label: 'Заказ+', to: { name: 'order-create' } },
  { key: 'responses', label: 'Отклики', to: { name: 'responses' }, roles: ['executor'] },
  { key: 'profile', label: 'Профиль', to: { name: 'profile' } },
]
