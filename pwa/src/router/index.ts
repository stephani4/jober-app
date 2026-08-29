import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '@/composables'

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
      meta: { guest: true },
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
      meta: { requiresAuth: true },
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
          meta: { title: 'Выполнение', hideNav: true, fullBleed: true, showChat: true },
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
          path: 'search',
          name: 'search',
          component: () => import('@/views/search/SearchView.vue'),
          meta: { title: 'Поиск', nav: 'search' },
        },
        {
          path: 'responses',
          name: 'responses',
          component: () => import('@/views/responses/ResponsesView.vue'),
          meta: { title: 'Отклики', nav: 'responses' },
        },
        {
          path: 'profile',
          name: 'profile',
          component: () => import('@/views/profile/ProfileView.vue'),
          meta: { title: 'Профиль', nav: 'profile', hero: 'profile' },
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

router.beforeEach(async (to) => {
  const { isAuthenticated, bootstrap } = useAuth()
  await bootstrap()

  if (to.matched.some((record) => record.meta.requiresAuth) && !isAuthenticated.value) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.matched.some((record) => record.meta.guest) && isAuthenticated.value) {
    return { name: 'orders' }
  }

  return true
})

export default router
