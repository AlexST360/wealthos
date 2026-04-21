import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  // Autenticación (públicas)
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/Login.vue'),
    meta: { public: true },
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('@/views/Register.vue'),
    meta: { public: true },
  },
  // App (protegidas)
  {
    path: '/',
    component: () => import('@/components/shared/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '',         name: 'Dashboard',    component: () => import('@/views/Dashboard.vue') },
      { path: 'portfolio',name: 'Portfolio',    component: () => import('@/views/Portfolio.vue') },
      { path: 'transactions', name: 'Transactions', component: () => import('@/views/Transactions.vue') },
      { path: 'goals',    name: 'Goals',        component: () => import('@/views/Goals.vue') },
      { path: 'simulator',name: 'Simulator',    component: () => import('@/views/Simulator.vue') },
      { path: 'advisor',  name: 'Advisor',      component: () => import('@/views/Advisor.vue') },
      { path: 'shared',   name: 'Shared',       component: () => import('@/views/Shared.vue') },
    ],
  },
  // Catch-all
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Guard de navegación: redirigir según auth
router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'Login' }
  }
  if (to.meta.public && auth.isAuthenticated) {
    return { name: 'Dashboard' }
  }
})

export default router
