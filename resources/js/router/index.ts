import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

/**
 * Cada ruta declara en `meta.permission` el permiso que exige. Es el mismo
 * nombre que protege el endpoint en routes/api.php, de modo que el frontend no
 * inventa su propia noción de acceso: solo evita mostrar una pantalla que el
 * backend rechazaría igualmente (§29).
 */
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
    meta: { requiresAuth: true, permission: 'clients.view' },
  },
  {
    path: '/admin/clients/:id',
    name: 'client-detail',
    component: () => import('@/pages/clients/ClientsPage.vue'),
    meta: { requiresAuth: true, permission: 'clients.view' },
  },
  {
    path: '/admin/appointments',
    name: 'appointments',
    component: () => import('@/pages/appointments/AppointmentsPage.vue'),
    meta: { requiresAuth: true, permission: 'appointments.view' },
  },
  {
    path: '/admin/services',
    name: 'services',
    component: () => import('@/pages/services/ServicesPage.vue'),
    meta: { requiresAuth: true, permission: 'services.view' },
  },
  {
    path: '/admin/services/categories',
    name: 'service-categories',
    component: () => import('@/pages/services/CategoriesPage.vue'),
    meta: { requiresAuth: true, permission: 'services.manage' },
  },
  {
    path: '/admin/staff',
    name: 'staff',
    component: () => import('@/pages/staff/StaffPage.vue'),
    meta: { requiresAuth: true, permission: 'employees.view' },
  },
  {
    path: '/admin/inventory',
    name: 'inventory',
    component: () => import('@/pages/inventory/InventoryPage.vue'),
    meta: { requiresAuth: true, permission: 'inventory.view' },
  },
  {
    path: '/admin/packages',
    name: 'packages',
    component: () => import('@/pages/packages/PackagesPage.vue'),
    meta: { requiresAuth: true, permission: 'packages.view' },
  },
  {
    path: '/admin/attendances',
    name: 'attendances',
    component: () => import('@/pages/attendances/AttendancesPage.vue'),
    meta: { requiresAuth: true, permission: 'attendances.view' },
  },
  {
    path: '/admin/sales',
    name: 'sales',
    component: () => import('@/pages/sales/SalesPage.vue'),
    meta: { requiresAuth: true, permission: 'sales.view' },
  },
  {
    path: '/admin/cash',
    name: 'cash',
    component: () => import('@/pages/cash/CashPage.vue'),
    meta: { requiresAuth: true, permission: 'cash.view' },
  },
  {
    path: '/admin/commissions',
    name: 'commissions',
    component: () => import('@/pages/commissions/CommissionsPage.vue'),
    meta: { requiresAuth: true, permission: 'commissions.view' },
  },
  {
    path: '/admin/reports',
    name: 'reports',
    component: () => import('@/pages/reports/ReportsPage.vue'),
    meta: { requiresAuth: true, permission: 'reports.view' },
  },
  {
    path: '/admin/forbidden',
    name: 'forbidden',
    component: () => import('@/pages/errors/ForbiddenPage.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/pages/errors/NotFoundPage.vue'),
    meta: { requiresAuth: false, layout: 'blank' },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // Con un token guardado hay que resolver la sesión antes de decidir:
  // de lo contrario un refresh redirige al login pese a estar autenticado.
  if (authStore.token && !authStore.user) {
    await authStore.ensureSession()
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next({ name: 'login', query: { redirect: to.fullPath } })
  }

  if (to.name === 'login' && authStore.isAuthenticated) {
    return next({ name: 'dashboard' })
  }

  const permission = to.meta.permission as string | undefined

  if (permission && !authStore.hasPermission(permission)) {
    return next({ name: 'forbidden' })
  }

  return next()
})

export default router
