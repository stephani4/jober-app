import type { NavigationGuard, RouteLocationNormalized, RouteLocationRaw } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { Permission } from '@/permissions'

export class PermissionDeniedError extends Error {
  constructor(readonly permission: string) {
    super('Недостаточно прав для этого действия.')
    this.name = 'PermissionDeniedError'
  }
}

/**
 * Собирает permission из meta текущего маршрута и родителей.
 */
export function requiredPermissions(to: RouteLocationNormalized): string[] {
  return to.matched.flatMap((record) => {
    const { permission, permissions } = record.meta
    const list: string[] = []
    if (typeof permission === 'string' && permission.length > 0) {
      list.push(permission)
    }
    if (Array.isArray(permissions)) {
      list.push(...permissions.filter((item): item is string => typeof item === 'string'))
    }
    return list
  })
}

/**
 * Первая страница, на которую хватает прав после входа.
 */
export function firstAllowedRoute(): RouteLocationRaw {
  const { can } = useAuth()

  if (can(Permission.OrdersView)) {
    return { name: 'orders' }
  }
  if (can(Permission.UsersView)) {
    return { name: 'users' }
  }
  if (can(Permission.AdminsView)) {
    return { name: 'admins' }
  }

  return { name: 'working-areas' }
}

/**
 * Router middleware: редирект на 403, если нет нужных permissions.
 */
export const permissionMiddleware: NavigationGuard = (to) => {
  const { can } = useAuth()

  if (to.matched.some((record) => record.meta.landing)) {
    return firstAllowedRoute()
  }

  const required = requiredPermissions(to)
  if (required.length === 0) {
    return true
  }

  if (required.every((permission) => can(permission))) {
    return true
  }

  return { name: 'forbidden' }
}

/**
 * Проверка permission перед действием (кнопка, submit, удаление).
 */
export function ensurePermission(permission: string): void {
  const { can } = useAuth()
  if (!can(permission)) {
    throw new PermissionDeniedError(permission)
  }
}

/**
 * Оборачивает действие: без права оно не выполняется.
 */
export function withPermission<TArgs extends unknown[]>(
  permission: string,
  action: (...args: TArgs) => Promise<void> | void,
): (...args: TArgs) => Promise<void> {
  return async (...args: TArgs) => {
    ensurePermission(permission)
    await action(...args)
  }
}
