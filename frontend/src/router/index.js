import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'dashboard',
      component: () => import('@/views/Dashboard.vue'),
      meta: { requiresAuth: true, title: 'Dashboard' }
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/Login.vue'),
      meta: { guest: true, title: 'Login' }
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/views/Register.vue'),
      meta: { guest: true, title: 'Register' }
    },
    {
      path: '/activities',
      name: 'activities',
      component: () => import('@/views/Activities.vue'),
      meta: { requiresAuth: true, title: 'Activities' }
    },
    {
      path: '/activities/create',
      name: 'create-activity',
      component: () => import('@/views/CreateActivity.vue'),
      meta: { requiresAuth: true, title: 'Create Activity' }
    }
  ]
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  
  if (authStore.token && !authStore.user) {
    await authStore.fetchUser()
  }

  if (to.meta.requiresAuth && !authStore.isLoggedIn) {
    next('/login')
  } else if (to.meta.guest && authStore.isLoggedIn) {
    next('/')
  } else {
      document.title = to.meta.title 
        ? `${to.meta.title} | OutdoorTask Scheduler` 
        : 'OutdoorTask Scheduler'
      next()
  }
})

export default router