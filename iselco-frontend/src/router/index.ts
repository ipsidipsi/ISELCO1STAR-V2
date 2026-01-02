import { createRouter, createWebHistory } from '@ionic/vue-router';
import { RouteRecordRaw } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import LoginPage from '../views/LoginPage.vue';

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'Login',
    component: LoginPage,
    meta: { requiresGuest: true }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('../views/DashboardPage.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/tickets',
    name: 'Tickets',
    component: () => import('../views/TicketListPage.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/tickets/:id',
    name: 'TicketDetail',
    component: () => import('../views/TicketDetailPage.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/change-password',
    name: 'ChangePassword',
    component: () => import('../views/ChangePasswordPage.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/admin/categories',
    name: 'CategoryManagement',
    component: () => import('../views/CategoryManagementPage.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/users',
    name: 'UserManagement',
    component: () => import('../views/UserManagementPage.vue'),
    meta: { requiresAuth: true }
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

// Navigation guard
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login')
  } else if (to.meta.requiresGuest && authStore.isAuthenticated) {
    next('/dashboard')
  } else if (to.meta.requiresAdmin) {
    // Check if user has admin role
    const userRoles = authStore.user?.roles || []
    const isAdmin = userRoles.some((role: any) =>
      role.slug === 'superadmin' || role.slug === 'department_admin'
    )

    if (!isAdmin) {
      // Redirect non-admins to dashboard
      next('/dashboard')
    } else {
      next()
    }
  } else {
    next()
  }
})

export default router

