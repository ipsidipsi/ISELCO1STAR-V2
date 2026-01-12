import { createRouter, createWebHistory } from '@ionic/vue-router';
import { RouteRecordRaw } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import LoginPage from '../views/LoginPage.vue';
import DashboardPage from '../views/DashboardPage.vue';
import TicketListPage from '../views/TicketListPage.vue';
import TicketDetailPage from '../views/TicketDetailPage.vue';
import ChangePasswordPage from '../views/ChangePasswordPage.vue';
import CategoryManagementPage from '../views/CategoryManagementPage.vue';
import ReportsPage from '@/views/ReportsPage.vue';
import UserManagementPage from '@/views/UserManagementPage.vue';

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
    component: DashboardPage,
    meta: { requiresAuth: true }
  },
  {
    path: '/tickets',
    name: 'Tickets',
    component: TicketListPage,
    meta: { requiresAuth: true }
  },
  {
    path: '/tickets/:id',
    name: 'TicketDetail',
    component: TicketDetailPage,
    meta: { requiresAuth: true }
  },
  {
    path: '/change-password',
    name: 'ChangePassword',
    component: ChangePasswordPage,
    meta: { requiresAuth: true }
  },
  {
    path: '/admin/categories',
    name: 'CategoryManagement',
    component: CategoryManagementPage,
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/reports',
    name: 'Reports',
    component: ReportsPage,
    meta: { requiresAuth: true, roles: ['superadmin', 'department_admin'] }
  },
  {
    path: '/admin/users',
    name: 'UserManagement',
    component: UserManagementPage,
    meta: { requiresAuth: true, requiresAdmin: true }
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

// Navigation guard
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  // 1. Force Password Change Guard
  // If user is logged in AND must change password, restrict navigation to only the Change Password page
  if (authStore.isAuthenticated && authStore.user?.must_change_password) {
    if (to.name !== 'ChangePassword' && to.name !== 'Login') {
      next({ name: 'ChangePassword' })
      return
    }
  }

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

