import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/pages/auth/LoginPage.vue'),
    meta: { requiresAuth: false, layout: 'blank' },
  },
  {
    path: '/admin',
    name: 'dashboard',
    component: () => import('@/pages/dashboard/DashboardPage.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/clients',
    name: 'clients',
    component: () => import('@/pages/clients/ClientsPage.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/clients/:id',
    name: 'client-detail',
    component: () => import('@/pages/clients/ClientsPage.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/services/categories',
    name: 'service-categories',
    component: () => import('@/pages/services/CategoriesPage.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/services',
    name: 'services',
    component: () => import('@/pages/services/ServicesPage.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/appointments',
    name: 'appointments',
    component: () => import('@/pages/appointments/AppointmentsPage.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/pages/errors/NotFoundPage.vue'),
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'login', query: { redirect: to.fullPath } })
  } else if (to.name === 'login' && authStore.isAuthenticated) {
    next({ name: 'dashboard' })
  } else {
    next()
  }
})

export default router
