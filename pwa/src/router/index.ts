import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '@/composables'
import { authMiddleware, guestMiddleware, roleMiddleware } from '@/router/middleware'
import '@/router/types' // декларация meta.middleware / meta страниц (RouteMeta)

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: { name: 'orders' },
    },
    {
      path: '/',
      component: () => import('@/layouts/UnauthorizeLayout.vue'),
      meta: { middleware: [guestMiddleware] },
      children: [
        {
          path: 'login',
          name: 'login',
          component: () => import('@/views/auth/LoginView.vue'),
        },
        {
          path: 'register',
          name: 'register',
          component: () => import('@/views/auth/RegisterView.vue'),
        },
      ],
    },
    {
      path: '/',
      component: () => import('@/layouts/AuthLayout.vue'),
      meta: { middleware: [authMiddleware] },
      children: [
        {
          path: 'orders',
          name: 'orders',
          component: () => import('@/views/orders/OrdersView.vue'),
          meta: { title: 'Заказы', nav: 'orders' },
        },
        {
          path: 'orders/create',
          name: 'order-create',
          component: () => import('@/views/orders/OrderCreateView.vue'),
          meta: { title: 'Новый заказ', nav: 'create', showBack: true, hideNav: true },
        },
        {
          path: 'orders/execute/:orderId',
          name: 'order-execute',
          component: () => import('@/views/orders/OrderExecuteView.vue'),
          meta: {
            title: 'Выполнение',
            hideNav: true,
            fullBleed: true,
            showChat: true,
            middleware: [roleMiddleware('executor')],
          },
        },
        {
          path: 'orders/history',
          name: 'order-history',
          component: () => import('@/views/orders/HistoryOrdersView.vue'),
          meta: { title: 'История', nav: 'profile', showBack: true },
        },
        {
          path: 'orders/:orderId/chat',
          name: 'order-chat',
          component: () => import('@/views/orders/OrderChatView.vue'),
          meta: { title: 'Чат', showBack: true, hideNav: true },
        },
        {
          path: 'orders/:orderId/watching',
          name: 'order-watching',
          component: () => import('@/views/orders/OrderWatchingView.vue'),
          meta: { title: 'Наблюдение', showBack: true, hideNav: true, fullBleed: true, showChat: true },
        },
        {
          // Поиск заказов доступен только исполнителю.
          path: 'search',
          name: 'search',
          component: () => import('@/views/search/SearchView.vue'),
          meta: { title: 'Поиск', nav: 'search', middleware: [roleMiddleware('executor')] },
        },
        {
          // Отклики по заказам доступны только исполнителю.
          path: 'responses',
          name: 'responses',
          component: () => import('@/views/responses/ResponsesView.vue'),
          meta: { title: 'Отклики', nav: 'responses', middleware: [roleMiddleware('executor')] },
        },
        {
          path: 'profile',
          name: 'profile',
          component: () => import('@/views/profile/ProfileView.vue'),
          meta: { title: 'Профиль', nav: 'profile', showBack: true, hideNav: true },
        },
        {
          path: 'profile/edit',
          name: 'profile-edit',
          component: () => import('@/views/profile/EditProfileView.vue'),
          meta: { title: 'Редактирование профиля', showBack: true, hideNav: true },
        },
        {
          path: 'notifications',
          name: 'notifications',
          component: () => import('@/views/notifications/NotifiesView.vue'),
          meta: { title: 'Уведомления', nav: 'profile', showBack: true },
        },
      ],
    },
  ],
})

router.beforeEach(async (to, from) => {
  // Профиль пользователя нужен и auth-, и ролевым middleware.
  const { bootstrap } = useAuth()
  await bootstrap()

  const chain = to.matched.flatMap((record) => record.meta.middleware ?? [])

  for (const middleware of chain) {
    const result = await middleware({ to, from })

    if (result !== true) {
      return result
    }
  }

  return true
})

export default router
