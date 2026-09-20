import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '@/composables'
import { Permission } from '@/permissions'
import { firstAllowedRoute, permissionMiddleware } from '@/middleware/permission'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
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
          path: '',
          name: 'home',
          meta: { landing: true },
          component: () => import('@/views/HomeRedirectView.vue'),
        },
        {
          path: 'forbidden',
          name: 'forbidden',
          component: () => import('@/views/ForbiddenView.vue'),
        },
        {
          path: 'orders',
          name: 'orders',
          meta: { permission: Permission.OrdersView },
          component: () => import('@/views/OrdersView.vue'),
        },
        {
          path: 'orders/:id/show',
          name: 'order-show',
          meta: { permission: Permission.OrdersView },
          component: () => import('@/views/OrderShowView.vue'),
        },
        {
          path: 'users',
          name: 'users',
          meta: { permission: Permission.UsersView },
          component: () => import('@/views/UsersView.vue'),
        },
        {
          path: 'admins',
          name: 'admins',
          meta: { permission: Permission.AdminsView },
          component: () => import('@/views/AdminsView.vue'),
        },
        {
          path: 'admins/create',
          name: 'admins-create',
          meta: { permission: Permission.AdminsCreate },
          component: () => import('@/views/AdminFormView.vue'),
        },
        {
          path: 'admins/edit/:id',
          name: 'admins-edit',
          meta: { permission: Permission.AdminsUpdate },
          component: () => import('@/views/AdminFormView.vue'),
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
    return firstAllowedRoute()
  }

  return permissionMiddleware(to)
})

export default router
