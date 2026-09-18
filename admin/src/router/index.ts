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
      component: () => import('@/layouts/GuestLayout.vue'),
      meta: { guest: true },
      children: [
        {
          path: 'login',
          name: 'login',
          component: () => import('@/views/LoginView.vue'),
        },
      ],
    },
    {
      path: '/',
      component: () => import('@/layouts/AdminLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: 'orders',
          name: 'orders',
          component: () => import('@/views/OrdersView.vue'),
        },
        {
          path: 'orders/:id/show',
          name: 'order-show',
          component: () => import('@/views/OrderShowView.vue'),
        },
        {
          path: 'users',
          name: 'users',
          component: () => import('@/views/UsersView.vue'),
        },
        {
          path: 'working-areas',
          name: 'working-areas',
          component: () => import('@/views/WorkingAreasView.vue'),
        },
        {
          path: 'working-areas/create',
          name: 'working-areas-create',
          component: () => import('@/views/WorkingAreaFormView.vue'),
        },
        {
          path: 'working-areas/edit/:id',
          name: 'working-areas-edit',
          component: () => import('@/views/WorkingAreaFormView.vue'),
        },
      ],
    },
  ],
})

router.beforeEach(async (to) => {
  const { isAuthenticated, bootstrap } = useAuth()
  await bootstrap()

  if (to.matched.some((record) => record.meta.requiresAuth) && !isAuthenticated.value) {
    return { name: 'login' }
  }

  if (to.matched.some((record) => record.meta.guest) && isAuthenticated.value) {
    return { name: 'orders' }
  }

  return true
})

export default router
