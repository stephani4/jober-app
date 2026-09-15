import { useAuth } from '@/composables'
import { userRoles, type UserRole } from '@/schemas/user'
import type { RouteMiddleware } from '@/router/middleware/types'

/**
 * Ограничивает маршрут ролями пользователя.
 *
 * В PWA всего две роли (`customer`, `executor`), поэтому роли из конфигурации маршрута
 * сначала проверяются на существование: опечатка в имени роли — ошибка разработчика,
 * а не «доступ запрещён» в рантайме.
 *
 * @param roles Роли, которым доступен маршрут (хотя бы одна).
 */
export function roleMiddleware(...roles: UserRole[]): RouteMiddleware {
  if (roles.length === 0) {
    throw new Error('[router] roleMiddleware требует хотя бы одну роль')
  }

  const unknown = roles.filter((role) => !userRoles.includes(role))
  if (unknown.length > 0) {
    throw new Error(
      `[router] Неизвестные роли: ${unknown.join(', ')}. Доступны: ${userRoles.join(', ')}`,
    )
  }

  return ({ to }) => {
    const { role } = useAuth()

    if (role.value !== null && roles.includes(role.value)) {
      return true
    }

    // Гостя до ролевой проверки доводит authMiddleware; здесь остаются только «недостаточные» роли.
    console.warn(`[router] Маршрут "${to.fullPath}" недоступен для роли "${role.value ?? 'guest'}"`)

    return { name: 'orders' }
  }
}