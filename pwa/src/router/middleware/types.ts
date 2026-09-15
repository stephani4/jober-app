import type { RouteLocationNormalized, RouteLocationRaw } from 'vue-router'

/** Контекст навигации, который получает каждое middleware. */
export interface MiddlewareContext {
  /** Маршрут, на который идёт переход. */
  to: RouteLocationNormalized
  /** Маршрут, с которого идёт переход. */
  from: RouteLocationNormalized
}

/** `true` — пропустить переход, иначе — адрес редиректа. */
export type MiddlewareResult = true | RouteLocationRaw

/** Middleware маршрута: проверка доступа и редиректы. */
export type RouteMiddleware = (
  context: MiddlewareContext,
) => MiddlewareResult | Promise<MiddlewareResult>