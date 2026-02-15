import { createRouter, createWebHistory } from 'vue-router'
import { getOrgSlug } from '@/utils/subdomain'

const mainRoutes = [
  {
    path: '/',
    name: 'home',
    component: () => import('@/views/HomeView.vue'),
    meta: { public: true },
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { public: true },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/views/RegisterView.vue'),
    meta: { public: true },
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/views/DashboardView.vue'),
  },
  {
    path: '/tickets',
    name: 'tickets',
    component: () => import('@/views/TicketListView.vue'),
  },
  {
    path: '/tickets/new',
    name: 'ticket-create',
    component: () => import('@/views/TicketCreateView.vue'),
  },
  {
    path: '/tickets/:id',
    name: 'ticket-detail',
    component: () => import('@/views/TicketDetailView.vue'),
  },
  {
    path: '/admin/users',
    name: 'admin-users',
    component: () => import('@/views/admin/UserListView.vue'),
    meta: { requiresAdmin: true },
  },
  {
    path: '/admin/categories',
    name: 'admin-categories',
    component: () => import('@/views/admin/CategoryListView.vue'),
    meta: { requiresAdmin: true },
  },
  {
    path: '/settings',
    name: 'settings',
    component: () => import('@/views/SettingsView.vue'),
    meta: { requiresAdmin: true },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },
]

const portalRoutes = [
  {
    path: '/',
    name: 'portal-home',
    component: () => import('@/views/portal/PortalHomeView.vue'),
    meta: { public: true, portal: true },
  },
  {
    path: '/submit',
    name: 'portal-submit',
    component: () => import('@/views/portal/TicketSubmitView.vue'),
    meta: { public: true, portal: true },
  },
  {
    path: '/submit/success',
    name: 'portal-submit-success',
    component: () => import('@/views/portal/TicketSubmitSuccessView.vue'),
    meta: { public: true, portal: true },
  },
  {
    path: '/track',
    name: 'portal-track',
    component: () => import('@/views/portal/TicketTrackView.vue'),
    meta: { public: true, portal: true },
  },
  {
    path: '/login',
    name: 'portal-login',
    component: () => import('@/views/LoginView.vue'),
    meta: { public: true, portal: true },
  },
  {
    path: '/dashboard',
    name: 'portal-dashboard',
    component: () => import('@/views/DashboardView.vue'),
    meta: { portal: true },
  },
  {
    path: '/tickets',
    name: 'portal-tickets',
    component: () => import('@/views/TicketListView.vue'),
    meta: { portal: true },
  },
  {
    path: '/tickets/:id',
    name: 'portal-ticket-detail',
    component: () => import('@/views/TicketDetailView.vue'),
    meta: { portal: true },
  },
  {
    path: '/admin/users',
    name: 'portal-admin-users',
    component: () => import('@/views/admin/UserListView.vue'),
    meta: { requiresAdmin: true, portal: true },
  },
  {
    path: '/admin/categories',
    name: 'portal-admin-categories',
    component: () => import('@/views/admin/CategoryListView.vue'),
    meta: { requiresAdmin: true, portal: true },
  },
  {
    path: '/settings',
    name: 'portal-settings',
    component: () => import('@/views/SettingsView.vue'),
    meta: { requiresAdmin: true, portal: true },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },
]

const orgSlug = getOrgSlug()

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: orgSlug ? portalRoutes : mainRoutes,
})

router.beforeEach(async (to) => {
  const token = localStorage.getItem('token')
  if (!to.meta.public && !token) {
    return '/login'
  }
})

export default router
