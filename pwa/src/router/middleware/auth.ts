import { useAuth } from '@/composables'
import type { RouteMiddleware } from '@/router/middleware/types'

/** Пускает только авторизованных: гостя уводит на вход с возвратом на исходный адрес. */
export const authMiddleware: RouteMiddleware = ({ to }) => {
  const { isAuthenticated } = useAuth()

  if (isAuthenticated.value) {
    return true
  }

  return { name: 'login', query: { redirect: to.fullPath } }
}

/** Пускает только гостей: авторизованного уводит на главную. */
export const guestMiddleware: RouteMiddleware = () => {
  const { isAuthenticated } = useAuth()

  return isAuthenticated.value ? { name: 'orders' } : true
}