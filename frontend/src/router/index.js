import { createRouter, createWebHistory } from 'vue-router'

import { useAuthStore } from '../stores/auth'

import Home from '../pages/Home.vue'
import Login from '../pages/Login.vue'
import Register from '../pages/Register.vue'
import ComingSoon from '../pages/ComingSoon.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', name: 'home', component: Home, meta: { requiresAuth: true } },
    { path: '/login', component: Login, meta: { guestOnly: true } },
    { path: '/register', component: Register, meta: { guestOnly: true } },
    { path: '/appointments', name: 'staff-appointments', component: ComingSoon, meta: { requiresAuth: true, requiresStaff: true, title: 'Appointments' } },
    { path: '/clients', name: 'staff-clients', component: ComingSoon, meta: { requiresAuth: true, requiresStaff: true, title: 'Clients' } },
    { path: '/services', name: 'staff-services', component: ComingSoon, meta: { requiresAuth: true, requiresStaff: true, title: 'Services' } },
    { path: '/book', name: 'client-book', component: ComingSoon, meta: { requiresAuth: true, requiresClient: true, title: 'Book an appointment' } },
    { path: '/client/appointments', name: 'client-appointments', component: ComingSoon, meta: { requiresAuth: true, requiresClient: true, title: 'My appointments' } },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (!auth.isInitialized) await auth.initialize()

  if (to.meta.guestOnly && auth.isAuthenticated) return { name: 'home' }
  if (to.meta.requiresAuth && !auth.isAuthenticated) return { path: '/login', query: { redirect: to.fullPath } }
  if (to.meta.requiresStaff && !auth.isStaff) return { name: 'home' }
  if (to.meta.requiresClient && !auth.isClient) return { name: 'home' }
})

export default router
