import type { BottomNavIcon } from '@/config/bottomNav'
import type { RouteMiddleware } from '@/router/middleware/types'

/**
 * Расширение meta маршрутов PWA: страницы описывают chrome (hero, навигация)
 * и цепочку middleware в `meta.middleware`.
 */
declare module 'vue-router' {
  interface RouteMeta {
    /** Цепочка middleware маршрута: выполняется от родителя к потомку. */
    middleware?: RouteMiddleware[]
    /** Заголовок страницы в hero-зоне. */
    title?: string
    /** Ключ активного таба нижней навигации. */
    nav?: BottomNavIcon
    /** Скрыть нижнюю навигацию на странице. */
    hideNav?: boolean
    /** Показать кнопку «Назад» в hero-зоне. */
    showBack?: boolean
    /** Полноэкранная страница без внутренних отступов. */
    fullBleed?: boolean
    /** Показывать кнопку чата заказа в hero-зоне. */
    showChat?: boolean
  }
}