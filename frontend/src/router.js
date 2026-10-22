import { createRouter, createWebHistory } from 'vue-router'
import { auth, hasRole, clearAuth } from '@/stores/auth'
import { setUnauthorizedHandler } from '@/api'
import { MENU, ALL_ROLES } from '@/utils/constants'
import LoginView from '@/views/LoginView.vue'
import DashboardView from '@/views/DashboardView.vue'
import PlaceholderView from '@/views/PlaceholderView.vue'

// Halaman yang belum dikerjakan memakai PlaceholderView.
// Saat tahapnya dikerjakan, ganti "component" dengan view yang sebenarnya, contoh Tahap 2:
//   { path: '/kendaraan', component: () => import('@/views/MotorListView.vue'), meta: {...} },
//   { path: '/kendaraan/:id/:tab?', component: () => import('@/views/MotorDetailView.vue'), meta: {...} },
const placeholderRoutes = MENU.filter((m) => m.stage > 1).map((m) => ({
  path: m.path,
  name: m.key,
  component: PlaceholderView,
  props: { menuKey: m.key },
  meta: { title: m.label, roles: m.roles }
}))

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', name: 'login', component: LoginView, meta: { public: true, title: 'Masuk' } },
    { path: '/', redirect: '/dashboard' },
    { path: '/dashboard', name: 'dashboard', component: DashboardView, meta: { title: 'Dashboard', roles: ALL_ROLES } },
    ...placeholderRoutes,
    { path: '/:pathMatch(.*)*', redirect: '/dashboard' }
  ]
})

router.beforeEach((to) => {
  if (to.meta.public) {
    if (to.name === 'login' && auth.token) return { name: 'dashboard' }
    return true
  }
  if (!auth.token) return { name: 'login', query: { redirect: to.fullPath } }
  if (!hasRole(to.meta.roles)) return { name: 'dashboard' }
  return true
})

router.afterEach((to) => {
  document.title = `${to.meta.title ?? 'NEXA'} – NEXA`
})

setUnauthorizedHandler(() => {
  clearAuth()
  router.push({ name: 'login', query: { expired: '1' } })
})

export default router
